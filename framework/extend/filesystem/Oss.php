<?php
namespace think\filesystem\driver;

use League\Flysystem\AdapterInterface;
use think\filesystem\Driver;
use Iidestiny\Flysystem\Oss\OssAdapter;

class Oss  extends Driver
{
    protected function createAdapter(): AdapterInterface
    {
        return new OssAdapter(
            $this->config['accessKey'],
            $this->config['secretKey'],
            $this->config['end_point'],
            $this->config['bucket'],
            $this->config['is_cname'],
            $this->config['prefix'],
        );
    }
}
