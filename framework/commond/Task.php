<?php
/**
 * Created by PhpStorm.
 * User: Huangguowen
 * Date: 2020/8/20 0020
 * Time: 上午 9:31
 */

namespace xhyadminframework\commond;

use think\console\command\Make;
use think\console\input\Argument;

class Task extends Make
{
    protected $type = "Task";

    protected function configure()
    {
        parent::configure();
        $this->setName('make:task')
            ->setDescription('Create a new Swoole Task class');
    }

    protected function getStub(): string
    {
        return __DIR__ . '\make' . DIRECTORY_SEPARATOR . 'task.stub';
    }

    protected function getNamespace(string $app): string
    {
        return parent::getNamespace($app) . '\\swoole\\Task';
    }
}
