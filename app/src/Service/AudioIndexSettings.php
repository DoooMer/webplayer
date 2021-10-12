<?php

namespace App\Service;

class AudioIndexSettings
{
    public function __construct(private string $directory, private string $tracksIndex)
    {
    }

    public function getTracksIndex(): string
    {
        return $this->tracksIndex;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }
}