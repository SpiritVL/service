<?php

namespace App\Modules\RequestButcher\Dto;

use League\Csv\Serializer\MapCell;

final readonly class IdTypeDto
{
    public function __construct(
        #[MapCell("loanId")]
        public int $id,
        #[MapCell("type")]
        public string $type,
        #[MapCell("version")]
        public int $version = 0
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'version' => $this->version
        ];
    }
}
