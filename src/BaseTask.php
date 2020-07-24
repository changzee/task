<?php

declare(strict_types=1);

namespace ChangZee\Task;

use ArrayObject;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;

abstract class BaseTask extends ArrayObject
{
    use LoggerTrait;
    use LoggerAwareTrait;

    /***
     * @var string $taskName
     */
    public static $taskName;

    /***
     * @var Queue $queue
     */
    protected static $queue;

    /**
     * @var int default time to reserve a job
     */
    public $ttr = 300;

    /***
     * @var int | null $pushTtr
     */
    private $pushTtr;

    /***
     * @var int | null $pushDelay
     */
    private $pushDelay;

    /**
     * @var int | null $pushPriority
     */
    private $pushPriority;

    /**
     * Task constructor.
     * @param array $taskData
     */
    public function __construct(array $taskData = [])
    {
        parent::__construct($taskData,
            ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST);
    }

    /***
     * if logger exist, write log.
     * @param $level
     * @param $message
     * @param array $context
     */
    public function log($level, $message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
    /**
     * Sets TTR for task execute.
     * @param int $value
     * @return $this
     */
    public function setTtr(int $value)
    {
        $this->pushTtr = $value;
        if ($this->pushTtr < 0) {
            trigger_error('ttr must be positive.', E_USER_ERROR);
        }
        return $this;
    }

    /***
     * get ttr
     * @return int
     */
    public function getTtr() : int
    {
        return $this->pushTtr ?: $this->ttr;
    }

    /**
     * Sets delay for later execute.
     * @param int $value
     * @return $this
     */
    public function setDelay(int $value)
    {
        $this->pushDelay = $value;
        if ($this->pushDelay < 0) {
            trigger_error('delay must be positive.', E_USER_ERROR);
        }
        return $this;
    }

    /***
     * get delay
     * @return int
     */
    public function getDelay() : int
    {
        return $this->pushDelay ?: 0;
    }

    /**
     * Sets task priority.
     * @param int $value
     * @return $this
     */
    public function setPriority(int $value)
    {
        $this->pushPriority = $value;
        if ($this->pushPriority < 0) {
            trigger_error('priority must be positive.', E_USER_ERROR);
        }
        return $this;
    }

    /**
     * get priority.
     * @return int
     */
    public function getPriority() : int
    {
        return $this->pushPriority ?: 0;
    }

    /***
     * get task config.
     * @return array
     */
    abstract static public function getConfig() : array;

    /***
     * get queue.
     * @return Queue
     */
    abstract static public function getQueue() : Queue;

    /***
     * handle callback.
     * @return void
     */
    abstract public function handle() : bool;
}
