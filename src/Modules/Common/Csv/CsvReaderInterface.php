<?php

namespace App\Modules\Common\Csv;

use App\Modules\Common\Csv\Exception\WrongRowFormatException;
use Iterator;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

interface CsvReaderInterface
{
    /**
     * @throws FileNotFoundException
     * @throws WrongRowFormatException
     *
     * @return Iterator<string>
     */
    public function read(string $path): Iterator;
}
