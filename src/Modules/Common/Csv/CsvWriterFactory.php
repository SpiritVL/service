<?php

namespace App\Modules\Common\Csv;

use League\Csv\Writer;
use League\Csv\UnavailableStream;

/**
 * @codeCoverageIgnore
 */
class CsvWriterFactory
{
    /**
     * @throws UnavailableStream
     */
    public function createFromPath(string $path, string $open_mode = 'r', $context = null): Writer
    {
        return Writer::createFromPath($path, $open_mode, $context);
    }
}
