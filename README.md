# About message-push

一个基于消息队列实现的一个简洁、高效的后台任务推送模块。

## 依赖要求
+ PHP >= 7.2
+ ext-pdo: *,
+ ext-json: *,
+ ext-pcntl: *,
+ psr/log: 1.1.3,
+ guzzlehttp/guzzle: 6.3.0,
+ ext-redis: *
+ psr/container: *

## Keyword
+ PSR-14
+ Task

## Install
命令行安装
```bash
# composer require ChangZee/task
```

## 使用
定义Task基类
```php
use ChangZee\Task\RedisTask;

abstract class BaseTask extends RedisTask
{
    /**
     * @inheritDoc
     */
    static public function getConfig(): array
    {
        return [
            'timeout' => 3, // 拉取队列数据最长阻塞时间
            'redis_client' => (new Redis()), // redis实例，对应my2.0中redis配置节点
        ];
    }
}
```
定义具体Task处理类
```php
/***
* @property string $name
* @property string $age 
**/
class DemoTask extends BaseTask
{
    public static $taskName = 'demo';

    public function handle(): bool
    {
        // do some login
        return true;
    }
}
```
投递Task
```php
use ChangZee\Task\TaskExecutor;

$task = new DemoTask();
$task->name = "Haochang";
$task->age = 25;
TaskExecutor::getInstance()->execute($task);
```
后台消费推荐引入symfony/console组件:
```php

class DemoCommand extends Command
{
    private function getTasks() : array
    {
        return [
            DemoTask::$taskName => DemoTask::class,
        ];
    }

    protected function configure()
    {
        $this->setName('demo:run')
            ->setProcessTitle('demo:run')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('task_name', 't', InputOption::VALUE_REQUIRED),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $taskName = (string)$input->getOption('task_name');

        $tasks = $this->getTasks();
        $curTask = $tasks[$taskName] ?? '';
        if (empty($curTask)) {
            $logger->alert('任务名错误');
            exit(-1);
        }
     
        $executor = TaskExecutor::getInstance();
        $executor->setLogger($logger);
        $executor->setLoop(new SignalLoop());

        $executor->daemon($curTask);
    }
}
```
启动脚本:
```
#!/usr/bin/php

<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use DemoCommand;
use Symfony\Component\Console\Application;

$application = new Application();

// 注册我们编写的命令 (commands)
$application->add(new DemoCommand());

$application->run();

```
启动进程
```bash
demo demo:run --task_name=demo
```

## TODO
1. 关于PHP7.4 Typehint支持
2. 基于Redis消费进程管理模块支持
3. 死信队列实现
