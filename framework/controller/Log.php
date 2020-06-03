<?php
/**
 * name：操作日志
 * date:2020-05-013
 * user:yjj
 */
namespace xhyadminframework\controller;

use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\Tree;
use xhyadminframework\model\Log as LogModel;
use catcher\Utils;
use think\facade\Db;

class Log extends CatchController
{
    /**
     * 日志列表
     * @param CatchRequest $catchRequest
     * @param LogModel $log
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function index(CatchRequest $catchRequest,LogModel $log)
    {
        //@验证规则
        $rule =   [
            'page' => 'require|number',
            'limit' => 'require|number',
            'is_enabled' => 'number|between:0,1'
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg);
        if ($paramError != "") {
            return $this->fail($paramError);
        }
        //分页参数
        $pageIndex = $catchRequest->param("page");           //当前页码
        $pageSize = $catchRequest->param("limit");           //每页大小
        $orderbyField = $catchRequest->param("sortField");   //排序字段
        $orderbyDir = $catchRequest->param("sortOrder");     //排序方向
        $defaultOrderbyField = 'log_datetime desc';

        $start_time = $catchRequest->param('start_time');
        $end_time = $catchRequest->param('end_time');
        if($start_time){
            $start_time = substr($start_time,1,10)." 00-00-00";
        }
        if($end_time){
            $end_time = substr($end_time,1,10)." 23:59:39";
        }

        //@拼接查询条件
        $where = "";
        $where .= Utils::getString("and category_identity like '%{0}%'", $catchRequest->param('category_identity'));
        $where .= Utils::getString("and log_type like '%{0}%'", $catchRequest->param('log_type'));
        $where .= Utils::getString("and log_datetime >= '{0}'", $start_time);
        $where .= Utils::getString("and log_datetime <= '{0}'", $end_time);
        $where .= Utils::getString("and user_name like '%{0}%'", $catchRequest->param('user_name'));
        $where .= Utils::getString("and log_title like '%{0}%'", $catchRequest->param('log_title'));


        //echo $where;exit;
        //排序条件
        $orderby = Utils::getOrderby($orderbyField, $orderbyDir, $defaultOrderbyField);

        $getCountSql = "select count(*) as total from s_system_log_data where 1=1 $where";

        $totalCount = Db::query($getCountSql)[0]["total"];


        $mainSql = "
            select  
                  *
            from
                  s_system_log_data   
            where 1=1 
            $where
        ".$orderby;


        $mainSql = Utils::getPagingSql($mainSql, $pageIndex, $pageSize);
        $data = Db::query($mainSql);
        return $this->listData($data, $totalCount, $pageIndex, $pageSize);
    }
    /**
     * 获取分类列表
     * @param LogModel $log
     * @return \think\response\Json
     */
    public function getCategoryList(LogModel $log)
    {
        $categoryTree = Tree::done($log->getCategory()->toArray(), "default", 'system_log_category_parent_id', 'children', 'system_log_category_id');
        return $this->success($categoryTree);
    }

    /**
     * 获取详细信息
     * @param $id
     * @return \think\response\Json
     */
    public function read($id)
    {
        $query =  "select
            *
            from
                  s_system_log_data where system_log_data_id='".$id."'";
        $data = Db::query($query);
        return $this->success($data);
    }
}
