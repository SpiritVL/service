<?php

namespace App\Modules\RequestButcher\Dto;

use Exception;

final readonly class DtoFactory
{
    /**
     * @throws Exception
     */
    public function getClassByHeaders(array $headers): string
    {
        $key = implode(',', $headers);

        return match ($key) {
            'loanId,type,version,prolongation' => ProlongationsDto::class,
            'loanId,type,version' => BasicDto::class,
            default => throw new Exception('Dto not found')
        };
    }
}
