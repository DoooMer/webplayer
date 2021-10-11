<?php

namespace App\Model;

class Track
{
    public bool $current = false;

    public function __construct(public string $name, public string $path)
    {

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