<?php
/**
 * Created by PhpStorm.
 * User: Huangguowen
 * Date: 2020/8/20 0020
 * Time: 上午 9:31
 */

namespace xhyadminframework\commond;

use think\console\command\Make;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class SwooleConfig extends Make
{
    protected $type = "swoole";

    protected function configure()
    {
        parent::configure();
        $this->setName('make:task')
            ->setDescription('Create a new config/swoole.php class');
    }

    protected function getStub(): string
    {
        return __DIR__ . '/make' . DIRECTORY_SEPARATOR . 'config_swoole.stub';
    }

    protected function execute(Input $input, Output $output)
    {
        $name = trim($input->getArgument('name'));

        $classname = $this->getClassName($name);
        

        file_put_contents($classname, $this->buildClass($classname));

        $output->writeln('<info>' . $this->type . ':' . $classname . ' created successfully.</info>');
    }

    protected function getClassName(string $name): string
    {
        return app()->getRootPath() . 'config\\' . 'swoole.php';
    }
}
