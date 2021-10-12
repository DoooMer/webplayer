<?php

namespace App\Model;

class Track
{
    public bool $current = false;

    public string $path;

    public function __construct(public string $name)
    {
        $this->path = urlencode($this->name);
    }

    public function is(string $name): bool
    {
        return $this->name === $name;
    }

    public function current(): void
    {
        $this->current = true;
    }
}