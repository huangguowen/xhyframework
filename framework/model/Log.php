<?php

namespace xhyadmin\framework\model;
use think\facade\Db;

class Log extends \think\Model
{


    protected $name = 's_system_log_data';

    /**
     * get list
     *
     * @time 2020年04月28日
     * @param $params
     * @throws \think\db\exception\DbException
     * @return void
     */
    public function getList($param)
    {

        $query = $this;
        if (isset($param['category_identity']) && $param['category_identity'] != '') {
            $query = $this->where('category_identity', 'like', '%' . $param['category_identity']);
        }
        if (isset($param['log_type']) && $param['log_type'] != '') {
            $query = $this->where('log_type', 'like', '%' . $param['log_type'].'%');
        }

        return $query->field([$this->aliasField('*')])
            ->order($this->aliasField('user_id'), 'desc')
            ->paginate();
    }

    public function getCategory()
    {
        return Db::name('s_system_log_category')
            ->order('sort_number', 'asc')
            ->order('created', 'desc')
            ->select();
    }
}
