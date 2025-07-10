<?php

namespace Joby\ContextInjection\Config;

interface Config
{
    public function has(string $key): bool;

    public function get(string $key): mixed;

    public function set(string $key, mixed $value): void;

    public function unset(string $key): void;
}