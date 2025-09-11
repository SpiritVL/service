<?php

namespace App\Modules\Files\Subscribers;

use App\Modules\Files\Events\DownloadErrorEvent;
use App\Modules\Files\Events\DownloadSuccessEvent;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DownloadProgressSubscriber implements EventSubscriberInterface
{
    private ProgressBar $progressBar;
    private int $successCount = 0;
    private array $errors = [];

    public function __construct(ProgressBar $progressBar)
    {
        $this->progressBar = $progressBar;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DownloadSuccessEvent::class => 'onDownloadSuccess',
            DownloadErrorEvent::class => 'onDownloadError',
        ];
    }

    public function onDownloadSuccess(DownloadSuccessEvent $event): void
    {
        $this->successCount++;
        $this->progressBar->advance();
    }

    public function onDownloadError(DownloadErrorEvent $event): void
    {
        $this->errors[] = $event;
        $this->progressBar->advance();
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
