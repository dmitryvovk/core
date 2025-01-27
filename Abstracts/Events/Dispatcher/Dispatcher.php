<?php

namespace Apiato\Core\Abstracts\Events\Dispatcher;

use Apiato\Core\Abstracts\Events\Interfaces\ShouldHandle;
use Apiato\Core\Abstracts\Events\Interfaces\ShouldHandleNow;
use Apiato\Core\Abstracts\Events\Jobs\EventJob;
use DateInterval;
use DateTimeInterface;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Foundation\Bus\PendingDispatch as JobDispatcher;

class Dispatcher extends EventDispatcher
{
    public function dispatch($event, $payload = [], $halt = false): ?array
    {
        // Handle event Async when ShouldHandle Interface is implemented
        if ($event instanceof ShouldHandle) {

            // Initialize delay & queue variables
            $delay = $event->jobDelay;
            $queue = $event->jobQueue;

            // Create a job & initialize the dispatcher
            $job        = new EventJob($event);
            $dispatcher = new JobDispatcher($job);

            // Check if the delay is set and if it has the correct type
            if (isset($delay)
                && (is_numeric($delay)
                    || $delay instanceof DateTimeInterface
                    || $delay instanceof DateInterval
                )
            ) {
                $dispatcher->delay($delay);
            }
            // Check if the queue is set and if it is a string
            if (isset($queue) && is_string($queue)) {
                $dispatcher->onQueue($queue);
            }
        } elseif ($event instanceof ShouldHandleNow) {
            $event->handle();
        }

        return parent::dispatch($event, $payload, $halt);
    }
}
