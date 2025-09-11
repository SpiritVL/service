<?php

namespace App\Modules\Files\Subscribers;

use Symfony\Component\Console\Helper\ProgressBar;

class DownloadProgressSubscriberFactory
{
    public function create(ProgressBar $progressBar): DownloadProgressSubscriber
    {
        return new DownloadProgressSubscriber($progressBar);
    }
}
