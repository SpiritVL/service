<?php

namespace App\Modules\Files;

use App\Modules\Common\Files\FileNamer;
use App\Modules\Files\Dto\NameUrlDto;
use App\Modules\Files\Events\DownloadErrorEvent;
use App\Modules\Files\Events\DownloadSuccessEvent;
use Iterator;
use GuzzleHttp\Client;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

readonly class FilesDownloader
{
    public function __construct(
        private Client $client,
        private EventDispatcherInterface $eventDispatcher,
        private FileNamer $fileNamer
    )
    {
    }

    /**
     * @param Iterator<NameUrlDto> $list
     * @param string $folder
     *
     * @return void
     */
    public function downloadFiles(Iterator $list, string $folder): void
    {
        /** @var NameUrlDto $dto */
        foreach ($list as $dto) {
            try {
                $this->client->get($dto->url, [
                    'sink' => $this->fileNamer->getUniqueName($folder, $dto->name),
                ]);

                // Ждем какое-то время, чтобы не заблокировали
                usleep(mt_rand(10, 500) * 1000);

                // Уведомляем об успехе
                $this->eventDispatcher->dispatch(new DownloadSuccessEvent(
                    $dto->name, $dto->url
                ));
            } catch (Throwable $exception) {
                // Уведомляем об ошибке
                $this->eventDispatcher->dispatch(new DownloadErrorEvent(
                    $dto->name, $dto->url, $exception->getMessage()
                ));

                continue;
            }
        }
    }
}
