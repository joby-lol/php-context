<?php

namespace Joby\ContextInjection;

use Psr\Container\ContainerInterface;

/**
 * A simple wrapper around Context that can be passed into third party tools that expect a PSR-11 container.
 */
class GlobalContainer implements ContainerInterface
{

    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        return Context::get($id);
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return Context::has($id);
    }
}