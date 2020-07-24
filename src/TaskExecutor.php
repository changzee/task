<?php

declare(strict_types=1);

namespace ChangZee\Task;

use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Throwable;
use function json_decode;

class TaskExecutor
{
    use LoggerTrait;
    use LoggerAwareTrait;

    /**
     * @var LoopInterface | string $loop
     */
    protected $loop;

    /***
     * set loop for daemon.
     * @param LoopInterface $loop
     * @return TaskExecutor
     */
    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;
        return $this;
    }

    /***
     * run consumer's  daemon
     * @param string $taskClass
     */
    public function daemon(string $taskClass)
    {
        /*** @var BaseTask $taskClass */
        $queue = $taskClass::getQueue();
        $queue->consume($this->loop, function ($id, $message, $ttr, $attempt) use ($taskClass) : bool {
            $payload = json_decode($message, true);

            try { // todo: 死信队列实现
                /*** @var BaseTask $task */
                $task = new $taskClass($payload);
                $res = $task->handle();
            } catch (Exception $e) {
                $this->alert($e->getMessage(), [
                    'id' => $id,
                    'ttr' => $ttr,
                    'attempt' => $attempt,
                    'trace' => $e->getTrace()
                ]);
                return false;
            } catch (Throwable $e) {
                $this->emergency($e->getMessage(), [
                    'id' => $id,
                    'ttr' => $ttr,
                    'attempt' => $attempt,
                    'trace' => $e->getTrace()
                ]);
                return false;
            }
            return $res;
        });
    }

    /***
     * send itself to queue for execution.
     * @param BaseTask $task
     * @return string
     */
    public function execute(BaseTask $task) : string
    {
        $message = json_encode($task->getArrayCopy(), JSON_UNESCAPED_UNICODE);
        return $task::getQueue()->push($message, $task->getTtr(), $task->getDelay(), $task->getPriority());
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
}
