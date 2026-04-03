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
            'loanId,type,prolongation,prolongation_agreements,insurance_card,insurance_card_request,notification_connected_services' => ProlongationsFullDto::class,
            'loanId,type,version,prolongation' => ProlongationsDto::class,
            'loanId,type,version' => BasicDto::class,
            default => throw new Exception('Dto not found')
        };
    }
}
