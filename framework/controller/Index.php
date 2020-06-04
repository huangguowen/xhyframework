<?php
namespace xhyadminframework\controller;

use xhyadminframework\base\XhyController;
use think\facade\Db;

class Index extends XhyController
{
    /**
     *
     * @time 2019年12月12日
     * @throws \Exception
     * @return string
     */
    public function dashboard(): string
    {
        $mysqlVersion = Db::query('select version() as version');
        return $this->fetch([
            'mysql_version' => $mysqlVersion['0']['version'],
        ]);
    }
}
