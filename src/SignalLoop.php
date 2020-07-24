<?php

declare(strict_types=1);

namespace ChangZee\Task;

class SignalLoop implements LoopInterface
{

    /**
     * @var array of signals to exit from listening of the queue.
     */
    public $exitSignals = [
        15, // SIGTERM
        2,  // SIGINT
        1,  // SIGHUP
    ];

    /**
     * @var array of signals to suspend listening of the queue.
     * For example: SIGTSTP
     */
    public $suspendSignals = [];

    /**
     * @var array of signals to resume listening of the queue.
     * For example: SIGCONT
     */
    public $resumeSignals = [];

    /**
     * @var bool status when exit signal was got.
     */
    private static $exit = false;

    /**
     * @var bool status when suspend or resume signal was got.
     */
    private static $pause = false;

    /***
     * SignalLoop constructor.
     */
    public function __construct()
    {
        // 设置信号处理函数, 防止任务被中断
        $this->setSignalHandler();
    }

    /**
     * Sets signal handlers.
     *
     * @inheritdoc
     */
    public function setSignalHandler() : void
    {
        if (extension_loaded('pcntl')) {
            foreach ($this->exitSignals as $signal) {
                pcntl_signal($signal, function () {
                    self::$exit = true;
                });
            }
            foreach ($this->suspendSignals as $signal) {
                pcntl_signal($signal, function () {
                    self::$pause = true;
                });
            }
            foreach ($this->resumeSignals as $signal) {
                pcntl_signal($signal, function () {
                    self::$pause = false;
                });
            }
        }
    }

    /**
     * Checks signals state.
     *
     * @inheritdoc
     */
    public function canContinue() : bool
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal_dispatch();
            // Wait for resume signal until loop is suspended
            while (self::$pause && !self::$exit) {
                usleep(10000);
                pcntl_signal_dispatch();
            }
        }

        return !self::$exit;
    }
}
