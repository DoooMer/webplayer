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

class AudioController extends AbstractController
{
    /**
     * Главная: плеер, список файлов.
     *
     * @param string|null $name
     * @return Response
     */
    #[Route('/audio/{name<.*>?}', name: 'audio')]
    public function index(?string $name = null): Response
    {
        $track = null; // файл для проигрывания
        $name = empty($name) ? null : urldecode($name); // выбранный файл

        // TODO отдельная индексация (страница с заглушкой + фоновый процесс)
        // TODO хранение индекса: файл, redis?

        // индексация mp3 файлов
        $finder = new Finder();
        $content = $finder->in($this->getParameter('files'));
        /** @var Track[] $files */
        $files = [];

        foreach ($content->files()->sortByName()->name('*.mp3') as $file) {
            $trackFile = new Track(
                $file->getRelativePathname(),
                urlencode($file->getRelativePathname())
            );

            $files[] = $trackFile;

            // выбор файла для проигрывания
            if ($track === null && $name !== null && $trackFile->is($name)) {
                $track = $trackFile;
                $trackFile->current();
            }

        }

        if (empty($files)) {
            throw new NotFoundHttpException("Нет файлов для воспроизведения.");
        }

        // когда трек не выбран - начинаем с начала
        if ($track === null) {
            $track = $files[0];
            $files[0]->current();
        }

        return $this->render('audio/index.html.twig', [
            'files' => $files,
            'track' => $track->path,
        ]);
    }

    /**
     * Стрим файла для проигрывания.
     *
     * @param string $name
     * @return BinaryFileResponse
     */
    #[Route('/download/{name<.*>}', name: 'download')]
    public function download(string $name): BinaryFileResponse
    {
        $filePath = $this->getParameter('files') . urldecode($name);

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new NotFoundHttpException("Файл не найден.");
        }

        // TODO обновление индекса если запрошенный файл не найден

        return $this->file($filePath);
    }

    /**
     * Выбор следующего файла.
     *
     * @param string $name
     * @return RedirectResponse
     */
    #[Route('/next/{name<.*>}', name: 'next')]
    public function next(string $name): RedirectResponse
    {
        $name = urldecode($name); // выбранный файл

        // индексация mp3 файлов
        $finder = new Finder();
        $content = $finder->in($this->getParameter('files'));
        /** @var Track[] $files */
        $files = [];
        $currentIndex = -1;

        foreach ($content->files()->sortByName()->name('*.mp3') as $file) {
            $trackFile = new Track(
                $file->getRelativePathname(),
                urlencode($file->getRelativePathname())
            );

            $files[] = $trackFile;

            if ($currentIndex >= 0) {
                // чтобы получить следующий файл
                break;
            }

            if ($name !== null && $trackFile->is($name)) {
                // получить индекс выбранного файла
                $currentIndex = array_key_last($files);
                $trackFile->current();
            }

        }

        if (empty($files)) {
            throw new NotFoundHttpException("Нет файлов для воспроизведения.");
        }

        // берем последний потому что выбираем до текущий + 1
        // значит последним всегда будет следующий файл
        // кроме конца списка
        $next = array_pop($files);

        // если конец списка - возвращаемся в начало
        if ($next && $next->current) {
            $next = $files[0];
        }

        return $this->redirectToRoute('audio', ['name' => $next->path ?? null]);
    }
}
