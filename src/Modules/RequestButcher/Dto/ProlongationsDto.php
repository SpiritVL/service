<?php

namespace App\Modules\RequestButcher\Dto;

use League\Csv\Serializer\MapCell;

final readonly class ProlongationsDto
{
    public function __construct(
        #[MapCell("loanId")]
        public int $id,
        #[MapCell("type")]
        public string $type,
        #[MapCell("version")]
        public int $version,
        #[MapCell("prolongation")]
        public int $prolongation,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'version' => $this->version,
            'prolongation' => $this->prolongation,
        ];
    }
}
