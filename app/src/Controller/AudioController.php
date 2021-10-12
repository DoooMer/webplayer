<?php

namespace App\Controller;

use App\Service\AudioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AudioController extends AbstractController
{
    public function __construct(private AudioService $audioService)
    {
    }

    /**
     * Главная: плеер, список файлов.
     *
     * @param string|null $track
     * @return Response
     */
    #[Route('/audio/{track<.*>?}', name: 'audio')]
    public function index(?string $track = null): Response
    {
        if ($this->audioService->isNeedToScan()) {
            $this->audioService->scan();
        }

        $tracks = $this->audioService->getAllAsTrack($track ? urldecode($track) : null);

        if ($track === null) {
            $track = $tracks[0]->path;
        }

        return $this->render('audio/index.html.twig', [
            'tracks' => $tracks,
            'total_tracks' => $this->audioService->getTotalIndexed(),
            'play' => $track,
            'track_name' => urldecode($track),
        ]);
    }

    /**
     * Стрим файла для проигрывания.
     *
     * @param string $track
     * @return BinaryFileResponse
     */
    #[Route('/download/{track<.*>}', name: 'download')]
    public function download(string $track): BinaryFileResponse
    {
        $filePath = $this->getParameter('files') . urldecode($track);

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->audioService->scan();
            throw new NotFoundHttpException("Файл не найден.");
        }

        return $this->file($filePath);
    }

    /**
     * Выбор следующего файла.
     *
     * @param string $track
     * @return RedirectResponse
     */
    #[Route('/next/{track<.*>}', name: 'next')]
    public function next(string $track): RedirectResponse
    {
        return $this->redirectToRoute('audio', [
            'track' => $this->audioService->searchNext(urldecode($track))->path,
        ]);
    }
}
