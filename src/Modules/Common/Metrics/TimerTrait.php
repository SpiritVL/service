<?php

declare(strict_types=1);

namespace App\Modules\Common\Metrics;

trait TimerTrait
{
    private array $periods = [];

    public function start(string $name): void
    {
        $this->periods[$name]['start'] = microtime(true);
    }

    public function stop(string $name): void
    {
        $this->periods[$name]['time'] = microtime(true) - $this->periods[$name]['start'] ?? 0;
    }

    public function getTime(string $name): float
    {
        $time = round($this->periods[$name]['time'] ?? 0, 2);
        unset ($this->periods[$name]);

        return $time;
    }
}
