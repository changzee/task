<?php

declare(strict_types=1);

namespace ChangZee\Task;

abstract class RedisTask extends BaseTask
{
    /***
     * overwrite the parent method.
     * @param int $value
     * @return $this
     */
    public function setPriority(int $value)
    {
        trigger_error('message priority is not supported in the driver.', E_USER_WARNING);
        return $this;
    }

    /***
     * @return Queue
     */
    public static function getQueue(): Queue
    {
        if (!static::$queue) {
            $config = static::getConfig();
            static::$queue = new RedisQueue($config['redis_client'], static::$taskName, $config['timeout']);
        }
        return static::$queue;
    }
}
