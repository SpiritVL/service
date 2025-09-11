<?php

namespace App\tests\Unit\Modules\Files\Dto;

use App\Modules\Files\Dto\NameUrlDto;
use PHPUnit\Framework\TestCase;

class NameUrlDtoTest extends TestCase
{
    public function testFieldsCorrect(): void
    {
        $dto = new NameUrlDto(
            $name = 'name',
            $url = 'http://example.com'
        );

        $this->assertEquals($name, $dto->name);
        $this->assertEquals($url, $dto->url);
    }
}
