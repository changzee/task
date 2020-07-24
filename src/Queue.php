<?php

declare(strict_types=1);

namespace ChangZee\Task;

/**
 * Base Queue.
 */
abstract class Queue
{
    /**
     * @var string $channel queue channel name
     */
    public $channel;

    /**
     * @param string $message
     * @param int $ttr time to reserve in seconds
     * @param int $delay
     * @param int $priority
     * @return string id of a job message
     */
    abstract public function push(string $message, int $ttr, int $delay, int $priority) : string;

    /***
     * callback for consume
     * @param LoopInterface $loop
     * @param callable $callback
     * @return mixed
     */
    abstract public function consume(LoopInterface $loop, callable $callback);

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isWaiting(string $id) : bool
    {
        return $this->status($id) === TASK_STATUS_WAITING;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isReserved(string $id) : bool
    {
        return $this->status($id) === TASK_STATUS_RESERVED;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isDone(string $id) : bool
    {
        return $this->status($id) === TASK_STATUS_DONE;
    }

    /**
     * @param string $id of a job message
     * @return int status code
     */
    abstract public function status(string $id) : int;
}
