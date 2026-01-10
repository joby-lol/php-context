<?php

/*
 * smolContext
 * https://github.com/joby-lol/smol-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\Context\Config;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class LazyValueTest extends TestCase
{

    public function testDoesNotRunUntilRequested(): void
    {
        $calls = 0;
        $lazy = new LazyValue(function () use (&$calls) {
            $calls++;
            return 'value';
        });

        $this->assertSame(0, $calls);

        $config = new DefaultConfig();
        $this->assertSame('value', $lazy->value($config));
        $this->assertSame(1, $calls);

        // Calling again should not re-run the callback.
        $this->assertSame('value', $lazy->value($config));
        $this->assertSame(1, $calls);
    }

    public function testReceivesConfigInstance(): void
    {
        $config = new DefaultConfig();
        $config->set('x', 'y');

        $lazy = new LazyValue(function (Config $cfg) {
            return $cfg->get('x');
        });

        $this->assertSame('y', $lazy->value($config));
    }

    public function testCachesNull(): void
    {
        $calls = 0;
        $lazy = new LazyValue(function () use (&$calls) {
            $calls++;
            return null;
        });

        $config = new DefaultConfig();
        $this->assertNull($lazy->value($config));
        $this->assertNull($lazy->value($config));
        $this->assertSame(1, $calls);
    }

    public function testRetriesAfterException(): void
    {
        $calls = 0;
        $lazy = new LazyValue(function () use (&$calls) {
            $calls++;
            throw new RuntimeException('boom');
        });

        $config = new DefaultConfig();

        try {
            $lazy->value($config);
            $this->fail('Expected exception was not thrown');
        }
        catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        try {
            $lazy->value($config);
            $this->fail('Expected exception was not thrown');
        }
        catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(2, $calls);
    }

    public function testDefaultConfigUnwrapsLazyValue(): void
    {
        $calls = 0;
        $config = new DefaultConfig();
        $config->set('lazy.key', new LazyValue(function () use (&$calls) {
            $calls++;
            return 'computed';
        }));

        $this->assertSame('computed', $config->get('lazy.key'));
        $this->assertSame('computed', $config->get('lazy.key'));
        $this->assertSame(1, $calls);

        // Replacing the value should cause a fresh LazyValue to run.
        $config->set('lazy.key', new LazyValue(function () use (&$calls) {
            $calls++;
            return 'computed2';
        }));

        $this->assertSame('computed2', $config->get('lazy.key'));
        $this->assertSame(2, $calls);
    }

}
