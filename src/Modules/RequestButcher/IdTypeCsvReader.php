<?php

namespace App\Modules\RequestButcher;

use App\Modules\Common\Csv\CsvReaderFactory;
use App\Modules\Common\Csv\CsvReaderInterface;
use App\Modules\Common\Csv\Exception\WrongRowFormatException;
use App\Modules\RequestButcher\Dto\IdTypeDto;
use Iterator;
use League\Csv\Exception;
use League\Csv\UnavailableStream;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

readonly class IdTypeCsvReader implements CsvReaderInterface
{
    public function __construct(private CsvReaderFactory $csvReaderFactory)
    {
    }

    /**
     * @return Iterator<IdTypeDto>
     *
     * @throws WrongRowFormatException
     * @throws FileNotFoundException
     */
    public function read(string $path): Iterator
    {
        try {
            $csv = $this->csvReaderFactory->createFromPath($path);
            // Делаем отступ шапки для сохранения совместимости с форматом для postman-а
            $csv->setHeaderOffset(0);

            return $csv->getRecordsAsObject(IdTypeDto::class);
        } catch (UnavailableStream $e) {
            throw new FileNotFoundException(sprintf('Файл `%s` не найден!', $path), $e->getCode(), $e);
        } catch (Exception $e) {
            throw new WrongRowFormatException(sprintf('Файл `%s` имеет неверный формат!', $path), $e->getCode(), $e);
        }
    }
}
