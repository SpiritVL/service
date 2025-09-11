<?php

namespace App\Modules\Files;

use App\Modules\Common\Csv\CsvReaderFactory;
use App\Modules\Common\Csv\CsvReaderInterface;
use App\Modules\Common\Csv\Exception\WrongRowFormatException;
use App\Modules\Files\Dto\NameUrlDto;
use Iterator;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

readonly class NameUrlCsvReader implements CsvReaderInterface
{
    public function __construct(private CsvReaderFactory $csvReaderFactory)
    {
    }

    /**
     * @return Iterator<NameUrlDto>
     *
     * @throws WrongRowFormatException
     * @throws FileNotFoundException
     */
    public function read(string $path): Iterator
    {
        try {
            $csv = $this->csvReaderFactory->createFromPath($path);

            return $csv->getRecordsAsObject(NameUrlDto::class);
        } catch (UnavailableStream $e) {
            throw new FileNotFoundException(sprintf('Файл `%s` не найден!', $path), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new WrongRowFormatException(sprintf('Файл `%s` имеет неверный формат!', $path), $e->getCode(), $e);
        }
    }
}
