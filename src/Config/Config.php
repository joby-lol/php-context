<?php

namespace Joby\ContextInjection\Config;

interface Config
{
    /**
     * Determine whether a config option exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get a value from config using its string key.
     * Throws an exception if it doesn't exist.
     *
     * @param string $key
     * @return mixed
     * @throws ConfigException
     */
    public function get(string $key): mixed;

    /**
     * Sets a config value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Unsets a config value.
     *
     * @param string $key
     * @return void
     */
    public function unset(string $key): void;

    /**
     * Interpolates the given string value by processing any placeholders or variables within it.
     * Placeholders should be in the form `${config_key}`.
     * If any placeholders do not exist or are non-scalar an exception will be thrown.
     *
     * @param string $value The input string containing placeholders or variables to be interpolated.
     * @return string The resulting string after interpolation.
     * @throws ConfigException
     */
    public function interpolate(string $value): string;
}