<?php

namespace Illuminate\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Contracts\Events\Dispatcher;

class FailingJob
{
    /**
     * Call the "failed" method, raise the failed job event, and delete the job.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Queue\Jobs\Job  $job
     * @param  \Exception $e
     * @return void
     */
    public static function handle($connectionName, $job, $e = null)
    {
        $job->markAsFailed();

        if ($job->isDeleted()) {
            return;
        }

        try {
            // If the job has failed, we will call the "failed" method and fire
            // an event indicating the job has failed so it can be logged if
            // needed before finally deleting it.  This is to allow every
            // developer to better monitor their failed queue jobs.
            $job->failed($e);
        } finally {
            static::events()->dispatch(new JobFailed(
                $connectionName, $job, $e ?: new ManuallyFailedException
            ));

            $job->delete();
        }
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    protected static function events()
    {
        return Container::getInstance()->make(Dispatcher::class);
    }
}
