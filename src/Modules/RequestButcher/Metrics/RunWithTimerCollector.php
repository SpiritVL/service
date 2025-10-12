<?php

declare(strict_types=1);

namespace App\Modules\RequestButcher\Metrics;

use App\Entity\RequestButcher\Session;
use App\Entity\RequestButcher\Task;
use App\Modules\Common\Metrics\TimerTrait;
use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;

class RunWithTimerCollector extends RunCollector implements MetricsCollectorInterface
{
    use TimerTrait;

    public function incDone(Session $session, Task $task, bool $isSuccess): void
    {
        parent::incDone($session, $task, $isSuccess);

        $histogram = $this->collectionRegistry->getOrRegisterHistogram(
            $this->namespace,
            self::NAME,
            'Скорость обработки',
            ['sessionId', 'title', 'status'],
            [1, 3, 5, 7, 10, 15, 20, 25, 30, 40, 60]
        );

        $histogram->observe($this->getTime((string) $task->getId()), [
            $session->getId(),
            $session->getTitle(),
            $isSuccess ? 'success' : 'error'
        ]);
    }
}
