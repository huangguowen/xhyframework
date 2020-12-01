<?php
/**
 * Created by PhpStorm.
 * User: Huangguowen
 * Date: 2020/8/20 0020
 * Time: 上午 11:48
 */
namespace xhyadminframework\commond;

use think\console\command\Make;
use think\console\input\Argument;

class websocketHandle extends Make
{
    protected $type = "Websocket";

    protected function configure()
    {
        parent::configure();
        $this->setName('make:websockt_handle')
            ->setDescription('Create WebSocketEvent class');
    }

    protected function getStub(): string
    {
        return __DIR__ . '/make' . DIRECTORY_SEPARATOR . 'WebSocketEvent.stub';
    }

    protected function getNamespace(string $app): string
    {
        return parent::getNamespace($app) . '/swoole/Websocket';
    }
}