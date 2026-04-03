<?php

namespace App\Entity\RequestButcher;

use App\Repository\RequestButcher\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'request_butcher_task')]
class Task
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $identifier = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Session $session = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private array $params = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastError = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function setIdentifier(int $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getRequestBody(): array
    {
        // Общий запрос для всех типов документов
        $body = [
            'loanId' => $this->params['id'],
            'format' => 'pdf',
            'type' => $this->params['type'],
            'params[prolongation]' => $this->params['prolongation'] ?? ''
        ];

        // Добавляем одну (кроме пролонгаций) или несколько версий (для пролонгаций)
        return $this->addVersions($body);
    }

    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): static
    {
        $this->lastError = $lastError;

        return $this;
    }

    /**
     * @TODO переделать на парсер и DTO
     */
    private function addVersions(array $body): array
    {
        // Если указана только версия - добавляем ее
        if (isset($this->params['version'])) {
            $body['params[version]'] = $this->params['version'];

            return $body;
        }

        // Если версия не указана, это пролонгации, добавляем версии всех документов
        return array_merge($body, [
            'params[version]' => 0,
            'params[versions][prolongation_agreements]' => $this->params['prolongation_agreements'],
            'params[versions][insurance_card]' => $this->params['insurance_card'],
            'params[versions][insurance_card_request]' => $this->params['insurance_card_request'],
            'params[versions][notification_connected_services]' => $this->params['notification_connected_services'],
        ]);
    }
}
