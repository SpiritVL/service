<?php

declare(strict_types=1);

namespace App\Modules\RequestButcher\Metrics;

use App\Entity\RequestButcher\Session;
use App\Entity\RequestButcher\Task;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;

class RunCollector implements MetricsCollectorInterface
{
    protected const NAME = 'rb';
    protected string $namespace;

    protected CollectorRegistry $collectionRegistry;
    protected Counter $counter;

    public function init(string $namespace, CollectorRegistry $collectionRegistry): void
    {
        $this->namespace = $namespace;
        $this->collectionRegistry = $collectionRegistry;

        $this->counter = $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            self::NAME,
            'Количество изменений статусов',
            ['sessionId', 'title', 'status']
        );
    }

    public function incDone(Session $session, Task $task, bool $isSuccess): void
    {
        $this->counter->inc([
            $session->getId(),
            $session->getTitle(),
            $isSuccess ? 'success' : 'error'
        ]);
    }
}
