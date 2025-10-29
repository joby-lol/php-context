<?php
/*
 * Context Injection
 * https://github.com/joby-lol/php-context
 * (c) 2024-2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\ContextInjection\PathGuard;

/**
 * Interface for a path guard that can be used to prevent certain files or directories from being included. Internally
 * this is used by the Invoker to allow an extra layer of security when including files.
 */
interface IncludeGuard extends PathGuard
{
}