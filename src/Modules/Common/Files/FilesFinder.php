<?php

namespace App\Modules\Common\Files;

use Exception;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FilesFinder
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    public function find(string $moduleName): array
    {
        $results = (new Finder())->files()->in(
            sprintf('%s/var/%s/', $this->projectDir, $moduleName)
        );

        $files = [];

        /** @var SplFileInfo $file */
        foreach ($results as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
    }

    /**
     * @throws Exception
     */
    public function getFilePath(string $filename, string $moduleName): string
    {
        $results = (new Finder())
            ->files()
            ->in($this->getProjectPath($moduleName))
            ->filter(function (SplFileInfo $file) use ($filename) {
                return $file->getRelativePathname() === $filename;
            });

        if ($results->hasResults()) {
            $result = iterator_to_array($results);
            $key = array_key_first($result);

            return $result[$key]->getRealPath();
        }

        throw new FileNotFoundException(
            sprintf('File `%s` not found in folder `%s`', $filename, $this->getProjectPath($moduleName))
        );
    }

    public function getProjectPath(string $moduleName): string
    {
        return sprintf('%s/var/%s/', $this->projectDir, $moduleName);
    }
}
