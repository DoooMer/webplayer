<?php

namespace App\Service;

use App\Model\Track;
use Redis;
use Symfony\Component\Finder\Finder;

class AudioService
{
    public function __construct(private AudioIndexSettings $indexSettings, private Redis $redis)
    {
    }

    public function getTotalIndexed(): int
    {
        return $this->redis->zCard($this->indexSettings->getTracksIndex());
    }

    public function isNeedToScan(): bool
    {
        return $this->getTotalIndexed() <= 0;
    }

    public function scan(): int
    {
        $finder = new Finder();
        $content = $finder->in($this->indexSettings->getDirectory());
        $counter = 1;

        foreach ($content->files()->sortByName()->name('*.mp3') as $file) {
            $this->redis->zAdd($this->indexSettings->getTracksIndex(), $counter, $file->getRelativePathname());
            $counter++;
        }

        unset($content);
        $this->redis->save();

        return $counter - 1;
    }

    public function getAll(): array
    {
        return $this->redis->zRange($this->indexSettings->getTracksIndex(), 0, -1);
    }

    /**
     * @param string|null $selected
     * @return Track[]
     */
    public function getAllAsTrack(?string $selected = null): array
    {
        $tracks = $this->getAll();

        $play = $selected ?? $tracks[0] ?? null;

        return array_map(static function (string $file) use ($play) {
            $track = new Track($file);

            if ($track->is($play)) {
                $track->current();
            }

            return $track;
        }, $tracks);
    }

    public function searchNext(string $selected): Track
    {
        $start = $this->redis->zScore($this->indexSettings->getTracksIndex(), $selected);
        $tracks = $this->redis->zRangeByScore($this->indexSettings->getTracksIndex(), $start, $start + 1);

        $file = array_pop($tracks);

        return new Track($file);
    }
}