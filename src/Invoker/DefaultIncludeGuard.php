<?php

namespace Joby\ContextInjection\Invoker;

use InvalidArgumentException;

/**
 * Default include guard implementation. Does basic checks to allow and deny includes by directory or full directory.
 * File rules take precedence over directory rules, and after that deny rules take precedence over allow rules. This
 * means that you can allow a directory, but deny files or subdirectories within it. It also means that you can deny a
 * directory, but allow specific files within it.
 */
class DefaultIncludeGuard implements IncludeGuard
{
    protected array $allowed_directories = [];
    protected array $denied_directories = [];
    protected array $allowed_files = [];
    protected array $denied_files = [];

    public function check(string $filename): bool
    {
        $path = realpath($filename);
        if ($path === false) return false;
        return $this->checkFile($path)
            ?? $this->checkDirectory(dirname($path))
            ?? false;
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
}