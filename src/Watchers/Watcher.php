<?php

namespace Hashcrypttech\HashGuardian\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Hashcrypttech\HashGuardian\IncomingEntry;
use Hashcrypttech\HashGuardian\HashGuardian;

abstract class Watcher
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    abstract public function register(Application $app): void;

    protected function isEnabled(): bool
    {
        return ($this->options['enabled'] ?? true) === true;
    }

    protected function record(IncomingEntry $entry): void
    {
        app(HashGuardian::class)->record($entry);
    }

    protected function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
}
