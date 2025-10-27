<?php

namespace App\Modules\Common\Files;

use Exception;

readonly class FileNamer
{
    public function __construct(
        private int $maxAttempts = 1000
    ) {
    }

    /**
     * @throws Exception
     */
    public function getUniqueName(string $directory, string $filename): string
    {
        $filepath = sprintf('%s/%s.pdf', $directory, $filename);

        // Сразу возвращаем путь, если файл не существует
        if (! file_exists($filepath)) {
            return $filepath;
        }

        // Генерируем уникальное имя с добавлением постфикса
        for ($i = 1; $i <= $this->maxAttempts; $i++) {
            $newFilepath = sprintf('%s/%s_%d.pdf', $directory, $filename, $i);

            if (! file_exists($newFilepath)) {
                return $newFilepath;
            }
        }

        throw new Exception(
            sprintf('Cannot generate unique filename for: `%s`', $filepath)
        );
    }
}
