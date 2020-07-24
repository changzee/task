<?php

declare(strict_types=1);

namespace ChangZee\Task;

use Redis;
use ChangZee\Task\Queue as BaseQueue;

class RedisQueue extends BaseQueue
{
    /**
     * @var Redis
     */
    public $redis;

    /***
     * @var int
     */
    public $timeout;

    /***
     * RedisQueue constructor.
     * @param string $channel
     * @param array $timeout
     * @param Redis $redisClient
     */
    public function __construct(Redis $redisClient, string $channel, array $timeout)
    {
        $this->redis = $redisClient;
        $this->channel = $channel;
        $this->timeout = $timeout ?? 0;
    }

    /**
     * @inheritdoc
     */
    public function status(string $id) : int
    {
        if ($this->redis->hexists("$this->channel.attempts", $id)) {
            return TASK_STATUS_RESERVED;
        }
        if ($this->redis->hexists("$this->channel.messages", $id)) {
            return TASK_STATUS_WAITING;
        }
        return TASK_STATUS_DONE;
    }

    /**
     * Clears the queue.
     */
    public function clear()
    {
        while (!$this->redis->set("$this->channel.moving_lock", true, ['NX', 'EX' => 1])) {
            usleep(10000);
        }
        $this->redis->del(
            "$this->channel.message",
            "$this->channel.delayed",
            "$this->channel.reserved",
            "$this->channel.waiting",
            "$this->channel.attempts",
            "$this->channel.message_id"
        );
    }

    /**
     * Removes a message by ID.
     *
     * @param int $id of a message
     * @return bool
     * @since 2.0.1
     */
    public function remove($id)
    {
        while (!$this->redis->set("$this->channel.moving_lock", true, ['NX', 'EX' => 1])) {
            usleep(10000);
        }
        if ($this->redis->hdel("$this->channel.messages", $id)) {
            $this->redis->zrem("$this->channel.delayed", $id);
            $this->redis->zrem("$this->channel.reserved", $id);
            $this->redis->lrem("$this->channel.waiting", 0, $id);
            $this->redis->hdel("$this->channel.attempts", $id);

            return true;
        }
        return false;
    }

    /**
     * @param int $timeout timeout
     * @return array|null payload
     */
    protected function reserve(int $timeout)
    {
        // Moves delayed and reserved messages into waiting list with lock for one second
        if ($this->redis->set("$this->channel.moving_lock", true, ['NX', 'EX' => 1])) {
            $this->moveExpired("$this->channel.delayed");
            $this->moveExpired("$this->channel.reserved");
        }

        // Find a new waiting message
        $id = null;
        if (!$timeout) {
            $id = $this->redis->rpop("$this->channel.waiting");
        } elseif ($result = $this->redis->brpop("$this->channel.waiting", $timeout)) {
            $id = $result[1];
        }
        if (!$id) {
            return null;
        }

        $payload = $this->redis->hget("$this->channel.messages", $id);
        list($ttr, $message) = explode(';', $payload, 2);
        $this->redis->zadd("$this->channel.reserved", time() + $ttr, $id);
        $attempt = $this->redis->hincrby("$this->channel.attempts", $id, 1);

        return [$id, $message, $ttr, $attempt];
    }

    /**
     * @param string $from
     */
    protected function moveExpired($from)
    {
        $now = time();
        if ($expired = $this->redis->zrevrangebyscore($from, $now, '-inf')) {
            $this->redis->zremrangebyscore($from, '-inf', $now);
            foreach ($expired as $id) {
                $this->redis->rpush("$this->channel.waiting", $id);
            }
        }
    }

    /**
     * Deletes message by ID.
     *
     * @param int $id of a message
     */
    protected function delete($id)
    {
        $this->redis->zrem("$this->channel.reserved", $id);
        $this->redis->hdel("$this->channel.attempts", $id);
        $this->redis->hdel("$this->channel.messages", $id);
    }

    /***
     * @param string $message
     * @param int $ttr
     * @param int $delay
     * @param int $priority
     * @return string
     */
    public function push(string $message, int $ttr, int $delay, int $priority): string
    {
        $id = $this->redis->incr("$this->channel.message_id");
        $this->redis->hset("$this->channel.messages", $id, "$ttr;$message");
        if (!$delay) {
            $this->redis->lpush("$this->channel.waiting", $id);
        } else {
            $this->redis->zadd("$this->channel.delayed", time() + $delay, $id);
        }
        return (string)$id;
    }

    public function consume(LoopInterface $loop, callable $callback)
    {
        while ($loop->canContinue()) {
            if (($payload = $this->reserve($this->timeout)) !== null) {
                list($id, $message, $ttr, $attempt) = $payload;
                if ($callback($id, $message, $ttr, $attempt)) {
                    $this->delete($id);
                }
            }
        }
    }
}
