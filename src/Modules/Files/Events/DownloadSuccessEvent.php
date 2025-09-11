<?php

namespace App\Modules\Files\Events;

use Symfony\Contracts\EventDispatcher\Event;

class DownloadSuccessEvent extends Event
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
    )
    {
    }
}
