<?php
/*
* smolContext
* https://github.com/joby-lol/smol-context
* (c) 2024-2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\Context;

use Psr\Container\ContainerInterface;

/**
 * A simple wrapper around Context that can be passed into third party tools that expect a PSR-11 container.
 */
class GlobalContainer implements ContainerInterface
{

    /**
     * @inheritDoc
     * @param class-string $class
     */
    public function get(string $class)
    {
        return Context::get($class);
    }

    /**
     * @inheritDoc
     * @param class-string $class
     */
    public function has(string $class): bool
    {
        return Context::has($class);
    }
}