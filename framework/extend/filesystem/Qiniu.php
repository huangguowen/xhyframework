<?php
namespace think\filesystem\driver;

use League\Flysystem\AdapterInterface;
use think\filesystem\Driver;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;

class Qiniu  extends Driver
{
    protected function createAdapter(): AdapterInterface
    {
        return new QiniuAdapter(
            $this->config['accessKey'],
            $this->config['secretKey'],
            $this->config['bucket'],
            $this->config['domain']
        );
    }
}
