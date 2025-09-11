<?php

namespace App\Modules\Files;

use App\Modules\Files\Dto\NameUrlDto;
use GuzzleHttp\Exception\GuzzleException;
use Iterator;
use GuzzleHttp\Client;

readonly class FilesDownloader
{
    public function __construct(private Client $client)
    {
    }

    /**
     * @param Iterator<NameUrlDto> $list
     * @param string $folder
     * @param callable $afterDownload
     *
     * @return void
     * @throws GuzzleException
     */
    public function downloadFiles(Iterator $list, string $folder, callable $afterDownload): void
    {
        /** @var NameUrlDto $dto */
        foreach ($list as $dto) {
            $this->client->get($dto->url, [
                'sink' => sprintf('%s/%s.pdf', $folder, $dto->name),
            ]);

            // Ждем какое-то время, чтобы не заблокировали
            usleep(mt_rand(10, 200) * 1000);
            $afterDownload();
        }
    }
}
