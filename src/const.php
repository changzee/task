<?php

declare(strict_types=1);

namespace ChangZee\Task;

/***
 * 任务状态: 等待
 */
const TASK_STATUS_WAITING = 1;

/**
 * 任务状态: 正在执行
 */
const TASK_STATUS_RESERVED = 2;

/***
 * 任务状态: 已完成
 */
const TASK_STATUS_DONE = 3;
