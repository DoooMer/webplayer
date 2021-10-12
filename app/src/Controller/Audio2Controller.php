<?php

namespace App\Controller;

use App\Model\Track;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class Audio2Controller extends AbstractController
{
    private const INDEX = 'tracks_index';

    public function __construct(private \Redis $redis)
    {
    }

    #[Route('/audio2/{track<.*>?}', name: 'audio2')]
    public function index(?string $track = null): Response
    {
        $tracksIndexed = $this->redis->zCard(self::INDEX);

        if ($tracksIndexed <= 0) {
            $tracksIndexed = $this->scan();
        }

        $tracks = $this->redis->zRange(self::INDEX, 0, -1, ['withscores' => true]);

        if ($track === null) {
            $play = array_key_first($tracks);
        } else {
            $play = urldecode($track);
        }

        $tracks = array_map(static function (int $index, string $file) use ($play) {
            $track = new Track(
                $file,
                urlencode($file)
            );

            if ($track->is($play)) {
                $track->current();
            }

            return $track;
        }, $tracks, array_keys($tracks));

        return $this->render('audio2/index.html.twig', [
            'tracks' => $tracks,
            'total_tracks' => $tracksIndexed,
            'play' => $play,
        ]);
    }

    #[Route('/download2/{track<.*>}', name: 'download2')]
    public function download(string $track): BinaryFileResponse
    {
        $filePath = $this->getParameter('files') . urldecode($track);

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->scan();
            throw new NotFoundHttpException("Файл не найден.");
        }

        return $this->file($filePath);
    }

    #[Route('/next2/{track<.*>}', name: 'next2')]
    public function next(string $track): RedirectResponse
    {
        $start = $this->redis->zScore(self::INDEX, urldecode($track));
        $tracks = $this->redis->zRangeByScore(self::INDEX, $start, $start + 1);

        $file = array_pop($tracks);

        return $this->redirectToRoute('audio2', ['track' => urlencode($file)]);
    }

    /**
     * @return int Количество файлов
     */
    private function scan(): int
    {
        $finder = new Finder();
        $content = $finder->in($this->getParameter('files'));
        $counter = 1;

        foreach ($content->files()->sortByName()->name('*.mp3') as $file) {
            $this->redis->zAdd(self::INDEX, $counter, $file->getRelativePathname());
            $counter++;
        }

        unset($content, $finder);
        $this->redis->save();

        return $counter - 1;
    }
}
