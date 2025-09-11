<?php

namespace App\tests\Unit\Modules\Files;

use App\Modules\Common\Csv\CsvReaderFactory;
use App\Modules\Common\Csv\Exception\WrongRowFormatException;
use App\Modules\Files\Dto\NameUrlDto;
use App\Modules\Files\NameUrlCsvReader;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class NameUrlCsvReaderTest extends TestCase
{
    private $csvReader;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем mock через createFromString
        $csvContent = "name,url\ntest,https://example.com";
        $this->csvReader = Reader::createFromString($csvContent);
        $this->csvReader->setHeaderOffset(0);

        // Подменяем статический метод (требует phpunit >= 9)
        if (method_exists($this, 'replaceStatic')) {
            $this->replaceStatic(Reader::class, 'createFromPath')
                 ->willReturnCallback(function ($path) {
                     return $this->csvReader;
                 });
        }
    }
    public function testSuccess(): void
    {
        $csv = Reader::createFromString("name,url\nname1,url1\nname2,url2\nname3,url3");
        $mockCsvFactory = $this->createMock(CsvReaderFactory::class);
        $mockCsvFactory->method('createFromPath')->willReturn($csv);

        $reader = new NameUrlCsvReader($mockCsvFactory);
        $rows = $reader->read('path');
        $rows->next();

        $this->assertEquals(new NameUrlDto('name1', 'url1'), $rows->current());
    }

    public function testUnavailableStreamException()
    {
        $mockCsvFactory = $this->createMock(CsvReaderFactory::class);
        $mockCsvFactory
            ->method('createFromPath')
            ->willThrowException(UnavailableStream::dueToPathNotFound('path'));
        $reader = new NameUrlCsvReader($mockCsvFactory);

        $this->expectException(FileNotFoundException::class);
        $reader->read('path');
    }

    public function testException()
    {
        $mockCsvFactory = $this->createMock(CsvReaderFactory::class);
        $mockCsvFactory
            ->method('createFromPath')
            ->willThrowException(new Exception());
        $reader = new NameUrlCsvReader($mockCsvFactory);

        $this->expectException(WrongRowFormatException::class);
        $reader->read('path');
    }
}
