<?php

namespace App\Modules\Files\Dto;

use League\Csv\Serializer\MapCell;

final readonly class NameUrlDto
{
    public function __construct(
        #[MapCell(0)]
        public string $name,
        #[MapCell(1)]
        public string $url
    ) {
    }
}
