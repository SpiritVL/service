<?php

namespace App\Modules\RequestButcher\Dto;

use League\Csv\Serializer\MapCell;

final readonly class ProlongationsFullDto
{
    public function __construct(
        #[MapCell("loanId")]
        public int $id,
        #[MapCell("type")]
        public string $type,
        #[MapCell("prolongation")]
        public int $prolongation,
        #[MapCell("prolongation_agreements")]
        public int $prolongationAgreements,
        #[MapCell("insurance_card")]
        public int $insuranceCard,
        #[MapCell("insurance_card_request")]
        public int $insuranceCardRequest,
        #[MapCell("notification_connected_services")]
        public int $notificationConnectedServices,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'prolongation' => $this->prolongation,
            'prolongation_agreements' => $this->prolongationAgreements,
            'insurance_card' => $this->insuranceCard,
            'insurance_card_request' => $this->insuranceCardRequest,
            'notification_connected_services' => $this->notificationConnectedServices,
        ];
    }
}
