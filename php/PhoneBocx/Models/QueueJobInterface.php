<?php

namespace PhoneBocx\Models;

interface QueueJobInterface
{
    /**
     * Return the utime that this job should run. If it
     * returns zero, do not run.
     *
     * @return integer
     */
    public function runAfter(): int;

    /**
     * This is called by the Queue worker after a job has failed,
     * with what it thinks the backoff should be. It will then
     * immediately ask this object for when it should run, so
     * if the backoff needs fuzzing, adjust it here.
     *
     * @return void
     */
    public function forceRunAfter(?int $utime = null);

    /**
     * Actually run the job. Return true if it works. The Queue
     * worker will then launch this->onSuccess(). If it
     * fails, return false. this->onFailure will then be
     * launched with a brief description.
     *
     * If maxAttempts is exceeded, this->onFatal will be called
     * instead.
     *
     * @return boolean
     */
    public function runJob(): bool;

    /**
     * The package that this job is from. This is to make sure
     * the package's autoloader is run before the job is processed.
     *
     * @return string
     */
    public function getPackage(): string;

    /**
     * A reference if needed, which can be used to filter jobs
     * in the Queue worker. Not really implemented.
     *
     * @return string
     */
    public function getRef(): string;

    /**
     * This is here in case runJob needs access to any random things,
     * like a parent Object, or an array or whatever. It is, by default,
     * serialised. See QueueJob
     *
     * @param mixed $obj
     * @return self
     */
    public function linkWith($obj): self;

    /**
     * If you've linked something, this will return it unserialized
     *
     * @return null|mixed
     */
    public function getLink();

    /**
     * How many seconds should be added each time the job fails
     *
     * @return integer
     */
    public function getFailureBackoff(): int;

    /**
     * How many attempts to run this job should be made
     *
     * @return integer
     */
    public function maxAttempts(): int;

    /**
     * The current attempt number to run this job.
     *
     * @return integer
     */
    public function getCurrentAttempts(): int;

    /**
     * Increment the attempt number and return it
     *
     * @return integer
     */
    public function incrementAttempts(): int;

    /**
     * When the job succeeds, do this. This is here so that
     * additional jobs can be launched from this, to chain
     * them together.
     *
     * @return void
     */
    public function onSuccess();

    /**
     * Called when a job fails.
     *
     * @param string $reason
     * @return void
     */
    public function onFailure(string $reason = "");

    /**
     * Called when a job will never be run again.
     *
     * @param string $reason
     * @return void
     */
    public function onFatal(string $reason = "");
}
