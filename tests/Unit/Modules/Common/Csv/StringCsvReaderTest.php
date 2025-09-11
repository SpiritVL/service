<?php

namespace App\tests\Unit\Modules\Common;

use App\Modules\Common\Csv\CsvReaderFactory;
use App\Modules\Common\Csv\Exception\WrongRowFormatException;
use App\Modules\Common\Csv\StringCsvReader;
use App\Modules\Files\Dto\NameUrlDto;
use App\Modules\Files\NameUrlCsvReader;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class StringCsvReaderTest extends TestCase
{
    public function testSuccess(): void
    {
        $csv = Reader::createFromString("name,url\nname1,url1\nname2,url2\nname3,url3");
        $mockCsvFactory = $this->createMock(CsvReaderFactory::class);
        $mockCsvFactory->method('createFromPath')->willReturn($csv);

        $reader = new StringCsvReader($mockCsvFactory);
        $rows = $reader->read('path');
        $rows->next();

        $this->assertEquals(['name1', 'url1'], $rows->current());
    }

    public function testUnavailableStreamException()
    {
        $mockCsvFactory = $this->createMock(CsvReaderFactory::class);
        $mockCsvFactory
            ->method('createFromPath')
            ->willThrowException(UnavailableStream::dueToPathNotFound('path'));
        $reader = new StringCsvReader($mockCsvFactory);

        $this->expectException(FileNotFoundException::class);
        $reader->read('path');
    }

    public function testException()
    {
        $mockCsvFactory = $this->createMock(CsvReaderFactory::class);
        $mockCsvFactory
            ->method('createFromPath')
            ->willThrowException(new Exception());
        $reader = new StringCsvReader($mockCsvFactory);

        $this->expectException(WrongRowFormatException::class);
        $reader->read('path');
    }
}
