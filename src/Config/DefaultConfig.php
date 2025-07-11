<?php

namespace Joby\ContextInjection\Config;

use Throwable;

class DefaultConfig implements Config
{
    /**
     * Default values
     *
     * @var array<string,mixed>
     */
    protected array $defaults = [];
    /**
     * Explicitly set value
     *
     * @var array<string,mixed>
     */
    protected array $values = [];
    /**
     * Values located by locators
     *
     * @var array<string,mixed>
     */
    protected array $located = [];
    /**
     * Cache to save values and save lookups/comparisons
     *
     * @var array<string,mixed>
     */
    protected array $cache = [];
    /**
     * Callbacks that may be used to locate config values that haven't been explicitly set.
     * Global locators run on any key.
     *
     * @var array<callable(string):mixed>
     */
    protected array $global_locators = [];
    /**
     * Callbacks that can locate config values, but only if they match a given prefix.
     * The locator callback will only be passed the key after the given prefix.
     * Prefix locators are higher-priority than global locators.
     *
     * @var array<string,callable(string):mixed>
     */
    protected array $prefix_locators = [];

    public function __construct(
        array $defaults = [],
        array $values = [],
        array $global_locators = [],
        array $prefix_locators = [],
    )
    {
        $this->defaults = $defaults;
        $this->values = $values;
        $this->global_locators = $global_locators;
        $this->prefix_locators = $prefix_locators;
    }

    public function unset(string $key): void
    {
        unset($this->values[$key]);
        unset($this->cache[$key]);
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
        unset($this->cache[$key]);
    }

    public function interpolate(string $value): string
    {
        return preg_replace_callback('/\${([^}]+)}/', function ($matches) {
            $key = $matches[1];
            if (!$this->has($key)) {
                throw new ConfigKeyNotFoundException("Config key '$key' not found, and cannot be interpolated.");
            }
            $replacement = $this->get($key);
            if (!is_scalar($replacement)) {
                throw new ConfigTypeException("Config key '$key' is not a scalar value, and cannot be interpolated.");
            }
            return (string)$replacement;
        }, $value);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->cache)
            || array_key_exists($key, $this->values)
            || array_key_exists($key, $this->defaults)
            || $this->locate($key);
    }

    protected function locate(string $key): bool
    {
        if (array_key_exists($key, $this->located)) {
            return true;
        }
        foreach ($this->prefix_locators as $prefix => $locator) {
            if (str_starts_with($key, $prefix)) {
                $value = $locator(substr($key, strlen($prefix)));
                if ($value !== null) {
                    $this->located[$key] = $value;
                    return true;
                }
            }
        }
        foreach ($this->global_locators as $locator) {
            $value = $locator($key);
            if ($value !== null) {
                $this->located[$key] = $value;
                return true;
            }
        }
        return false;
    }

    public function get(string $key): mixed
    {
        try {
            return $this->cache[$key] ??= $this->doGet($key);
        } catch (ConfigException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ConfigException("Error retrieving config key '$key'", 0, $e);
        }
    }

    protected function doGet(string $key): mixed
    {
        if (array_key_exists($key, $this->values)) {
            $value = $this->values[$key];
        } elseif ($this->locate($key)) {
            $value = $this->located[$key];
        } elseif (array_key_exists($key, $this->defaults)) {
            $value = $this->defaults[$key];
        } else {
            throw new ConfigKeyNotFoundException("Config key '$key' not found.");
        }
        while ($value instanceof ConfigValue) {
            $value = $value->value($this);
        }
        return $value;
    }

}