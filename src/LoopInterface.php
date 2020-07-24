<?php

declare(strict_types=1);

namespace ChangZee\Task;

interface LoopInterface
{
    /**
     * @return bool whether to continue listening of the queue.
     */
    public function canContinue() : bool;
}
