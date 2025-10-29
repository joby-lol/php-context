<?php

namespace Joby\ContextInjection\PathGuard;

/**
 * Context Injection: https://codeberg.org/joby/php-context
 * MIT License: Copyright (c) 2025 Joby Elliott
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use InvalidArgumentException;

/**
 * Default implementation of the PathGuard interface. Implemented as a trait so that it can be used by multiple classes
 * that do not inherit from each other.
 */
trait PathGuardTrait
{
    /** @var array<string> $allowed_directories */
    protected array $allowed_directories = [];
    /** @var array<string> $denied_directories */
    protected array $denied_directories = [];
    /** @var array<string> $allowed_files */
    protected array $allowed_files = [];
    /** @var array<string> $denied_files */
    protected array $denied_files = [];

    public function check(string $filename): bool
    {
        $path = realpath($filename);
        if ($path === false) return false;
        return $this->checkFile($path)
            ?? $this->checkDirectory(dirname($path))
            ?? false;
    }

    /**
     * @param string $directory
     *
     * @return void
     */
    public function allowDirectory(string $directory): void
    {
        $directory = realpath($directory);
        if ($directory === false) throw new InvalidArgumentException("Invalid directory");
        if (!is_dir($directory)) throw new InvalidArgumentException("Directory does not exist");
        $this->allowed_directories[] = $directory;
        $this->denied_directories = array_diff($this->denied_directories, [$directory]);
    }

    public function denyDirectory(string $directory): void
    {
        $directory = realpath($directory);
        if ($directory === false) throw new InvalidArgumentException("Invalid directory");
        if (!is_dir($directory)) throw new InvalidArgumentException("Directory does not exist");
        $this->denied_directories[] = $directory;
        $this->allowed_directories = array_diff($this->allowed_directories, [$directory]);
    }

    public function allowFile(string $file): void
    {
        $file = realpath($file);
        if ($file === false) throw new InvalidArgumentException("Invalid file");
        if (!is_file($file)) throw new InvalidArgumentException("File does not exist");
        $this->allowed_files[] = $file;
        $this->denied_files = array_diff($this->denied_files, [$file]);
    }

    public function denyFile(string $file): void
    {
        $file = realpath($file);
        if ($file === false) throw new InvalidArgumentException("Invalid file");
        if (!is_file($file)) throw new InvalidArgumentException("File does not exist");
        $this->denied_files[] = $file;
        $this->allowed_files = array_diff($this->allowed_files, [$file]);
    }

    protected function checkFile(string $filename): bool|null
    {
        if (in_array($filename, $this->denied_files)) return false;
        elseif (in_array($filename, $this->allowed_files)) return true;
        else return null;
    }

    protected function checkDirectory(string $directory): bool|null
    {
        foreach ($this->denied_directories as $denied_directory) {
            if (str_starts_with($directory, $denied_directory)) return false;
        }
        foreach ($this->allowed_directories as $allowed_directory) {
            if (str_starts_with($directory, $allowed_directory)) return true;
        }
        return null;
    }
}