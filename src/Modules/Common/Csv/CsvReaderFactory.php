<?php

namespace App\Modules\Common\Csv;

use League\Csv\Reader;
use League\Csv\UnavailableStream;

/**
 * @codeCoverageIgnore
 */
class CsvReaderFactory
{
    /**
     * @throws UnavailableStream
     */
    public function createFromPath(string $path, string $open_mode = 'r', $context = null): Reader
    {
        return Reader::from($path, $open_mode, $context);
    }
}
