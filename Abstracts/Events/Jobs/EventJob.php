<?php

namespace Apiato\Core\Abstracts\Events\Jobs;

use Apiato\Core\Abstracts\Events\Interfaces\ShouldHandle;
use Apiato\Core\Abstracts\Jobs\Job;
use Illuminate\Contracts\Queue\ShouldQueue;

class EventJob extends Job implements ShouldQueue
{
    public function __construct(public ShouldHandle $handler)
    {
    }

    public function handle(): void
    {
        $this->handler->handle();
    }
}
