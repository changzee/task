<?php

declare(strict_types=1);

namespace ChangZee\Task;

class CounterLoop implements LoopInterface
{
    public $counter = 0;

    /**
     * CounterLoop constructor.
     * @param int $counter 计数值
     */
    public function __construct(int $counter)
    {
        $this->counter = $counter;
    }

    /**
     * Checks signals state.
     * 执行counter数之后结束进程
     * @inheritdoc
     */
    public function canContinue() : bool
    {
        return ($this->counter--) > 0;
    }
}
