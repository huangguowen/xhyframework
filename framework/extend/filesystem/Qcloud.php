<?php
namespace think\filesystem\driver;

use League\Flysystem\AdapterInterface;
use think\filesystem\Driver;
use Overtrue\Flysystem\Cos\CosAdapter;

class Qcloud  extends Driver
{
    protected function createAdapter(): AdapterInterface
    {
        return new CosAdapter([
            'region'      => $this->config['region'],
            'credentials' => [
                'appId'      => $this->config['appId'],
                'secretId'   => $this->config['secretId'],
                'secretKey'  => $this->config['secretKey'],
            ],
            'bucket'          => $this->config['bucket'],
            'timeout'         => $this->config['timeout'] ?? 60,
            'connect_timeout' => $this->config['connect_timeout'] ?? 60,
            'cdn'             => $this->config['cdn'],
            'scheme'          => $this->config['scheme'] ?? 'https',
            'read_from_cdn'   => $this->config['read_from_cdn'] ?? false,
        ]);
    }
}
