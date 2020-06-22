<?php

/**
 * 字典分类管理
 */
namespace xhyadminframework\controller;

use xhyadminframework\base\XhyController;
use xhyadminframework\base\XhyRequest;
use xhyadminframework\Tree;
use xhyadminframework\Utils;
use think\facade\Db;
use think\response\Json;

class Dict extends XhyController
{
    /**
     * 字典分类列表
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: lampzww
     * @Date: 15:14  2020/5/27
     */
    public function index(XhyRequest $request): Json
    {
        $type   =   $request->param('type','','trim');
        $data   =   Db::name('s_dict_category')
            ->field('dict_category_id as id,category_name as title,dict_category_parent_id')
            ->order('sort_number','asc')
            ->select()->toArray();
        if($type == 'treeSelect'){
            foreach ($data as &$item){
                $item['value'] = $item['key']    =   $item['id'];
            }
        }
        $list = Tree::done($data, "0", 'dict_category_parent_id', 'children', 'id');
        // 添加虚拟主分类

        !$type  &&  $list[] = [
            'id' => '00',
            'title' => '字典分类',
            'children' => $list
        ];
        return $this->success($list);
    }

    /**
     * 字典列表
     * @param XhyRequest $request
     * @return Json
     * @author: lampzww
     * @Date: 11:10  2020/5/28
     */
    public function dictList(XhyRequest $request)
    {
        //region 准备参数（验证参数/定义变量/排序字段）
        //--------------------------------------------------------------------

        //@验证规则
        $rule = [
            'page' => 'require|number',
            'limit' => 'require|number',
            'is_enabled' => 'number|in:0,1',
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg);
        if ($paramError != "") {
            return $this->fail($paramError);
        }

        //分页参数
        $pageIndex = $request->param("page"); //当前页码
        $pageSize = $request->param("limit"); //每页大小
        $orderbyField = $request->param("sortField"); //排序字段
        $orderbyDir = $request->param("sortOrder"); //排序方向

        //@查询参数
        $keyword    = $request->param("keyword","","trim");//关键字
        $is_enabled = $request->param("is_enabled");//状态
        $time       = $request->param("time","","trim");
        $cat_id     = $request->param("cat_id","","trim");

        //@默认排序字段名称
        $defaultOrderbyField = "a.sort_number asc";

        //排序条件
        $orderby = Utils::getOrderby($orderbyField ? 'a.'.$orderbyField : '', $orderbyDir, $defaultOrderbyField);
        #endregion

        //region 获取查询条件(查询条件/获取记录总数)
        //--------------------------------------------------------------------
        //@拼接查询条件
        $where = "";
        $where .= Utils::getString("and (a.dict_naming like '%{0}%' or b.category_name like '%{0}%')", $keyword);
        $where .= Utils::getString("and a.dict_category_id = '{0}'", $cat_id);
        $where .= Utils::getNumber("and a.is_enabled = {0}", $is_enabled);
        if($time){
            list($stime,$etime) = explode(' - ',$time);
            $where  .=  Utils::getString("and a.created >= '{0}'", $stime);
            $where  .=  Utils::getString("and a.created <= '{0}'", $etime);
        }
        $db_name    =   's_dict_name';
        //@获取记录总数
        $getCountSql = "select count(*) as total from $db_name as a left join s_dict_category as b on a.dict_category_id = b.dict_category_id where 1=1 $where";

        $totalCount = Db::query($getCountSql)[0]["total"];

        //endregion

        //region 编写查询语句
        //--------------------------------------------------------------------
        $mainSql = "
            select
                    a.dict_name_id,
                    a.dict_naming,
                    a.dict_explain,
                    a.sort_number,
                    a.is_enabled,
                    a.created,
                    a.modified,
                    b.category_name
            from
                    $db_name as a
            left join s_dict_category as b on a.dict_category_id = b.dict_category_id
            where 1=1
            $where $orderby
        ";
        $mainSql = Utils::getPagingSql($mainSql, $pageIndex, $pageSize);
        $data = Db::query($mainSql);
        return $this->listData($data, $totalCount, $pageIndex, $pageSize, $where);
    }


    /**
     * 添加、修改
     * @param XhyRequest $XhyRequest
     * @return Json
     * @author: lampzww
     * @Date: 15:15  2020/5/27
     */
    public function save(XhyRequest $request): Json
    {
        return $this->onSaveData($request);
    }

    /**
     * 更新菜单
     * @param $id
     * @param XhyRequest $XhyRequest
     * @return Json
     * @author: lampzww
     * @Date: 15:15  2020/5/27
     */
    public function update($id, XhyRequest $XhyRequest): Json
    {
        return $this->onSaveData($XhyRequest, $id);
    }

    /**
     * 删除分类
     * @param $id
     * @return Json
     * @author: lampzww
     * @Date: 15:15  2020/5/27
     */
    public function delete($id): Json
    {
        //region 准备参数
        $isSuccess = false;
        $id = Utils::safeValue($id);
        if ($id == "")return $this->fail("参数不能为空");
        //endregion
        Db::startTrans();
        try {
            if(request()->param('type','','intval') == 2){
                $before = Db::table('s_dict_name')->where('dict_name_id','=', $id)->find();
                if (!$before)return $this->fail('字典不存在');
                if ($before['is_protected'])return $this->fail('该数据受保护，不允许删除！');

                // 删除分类
                Db::table('s_dict_name')->where('dict_name_id', '=', $id)->delete();
                //日志
                $logTitle = "删除字典" . $before['dict_naming'] . "成功";
            }else{
                if (Db::table('s_dict_category')->where('dict_category_parent_id', $id)->find())return $this->fail('存在子分类，无法删除');
                $before = Db::table('s_dict_category')->where('dict_category_id','=', $id)->find();
                if (!$before)return $this->fail('分类不存在');

                // 删除分类
                Db::table('s_dict_category')->where('dict_category_id', '=', $id)->delete();
                //日志
                $logTitle = "删除分类" . $before['category_name'] . "成功";
            }

            $logDetail = '';
            $this->logInfo($logTitle, $logDetail);
            Db::commit();
            $isSuccess = true;
            return $this->success([],'删除成功');
        } catch (\Exception $exception) {
            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();
            $this->logError("删除出错", $exception);
            return $this->fail($exception->getMessage());
            //endregion
        } finally {
            //region 结束
            //失败时回滚事务
            if ($isSuccess == false) {
                Db::rollback();
            }
            //endregion
        }

    }


    /**
     * 向上向下排序
     * @param XhyRequest $XhyRequest
     * @return Json
     * @author: lampzww
     * @Date: 15:16  2020/5/27
     */
    public function sort(XhyRequest $XhyRequest)
    {
        $rule = [
            'id' => 'require',
            'action' => 'require'
        ];
        $msg = [
            'id.require'         =>  '参数错误！',
        ];
        $paramError = $this->checkParams($rule, $msg);
        if ($paramError != "") return $this->fail($paramError);
        try {
            $id = $XhyRequest->param('id');
            $action = $XhyRequest->param('action');
            $info = Db::table('s_dict_category')->where('dict_category_id', $id)->find();
            if (!$info)return $this->fail('分类不存在');
            $op = $action == 'up' ? '<' : '>';
            $sort = $action == 'up' ? 'desc' : 'asc';
            $result = $this->updateCateSortNumber($info, $op, $sort, $this);
            return $this->success([$result],'排序成功');
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());
        }
    }


    /**
     * 交换排序
     * @param $info
     * @param string $op
     * @param string $desc
     * @param $model
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: lampzww
     * @Date: 14:39  2020/5/27
     */
    public function updateCateSortNumber($info, $op = '<', $desc = 'desc', $model)
    {
        $isSuccess = false;
        $changInfo = Db::table('s_dict_category')->where('dict_category_parent_id', $info['dict_category_parent_id'])
            ->where('sort_number', $op, $info['sort_number'])
            ->order('sort_number', $desc)
            ->find();
        if ($changInfo) {
            Db::startTrans();
            try {
                Db::table('s_dict_category')->where('dict_category_id', $info['dict_category_id'])->update(['sort_number' => $changInfo['sort_number']]);
                Db::table('s_dict_category')->where('dict_category_id', $changInfo['dict_category_id'])->update(['sort_number' => $info['sort_number']]);
                //日志
                $logTitle = "排序成功";
                $logDetail = '';
                $model->logInfo($logTitle, $logDetail);
                // 提交事务
                Db::commit();
                $isSuccess = true;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $model->logError("排序出错", $e);
            } finally {
                //失败时回滚事务
                if ($isSuccess == false) {
                    Db::rollback();
                }
            }
            return true;
        }
        return false;
    }


    /**
     * 通过当前分类的父分类id获取树节点ID
     * @param $parent_id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: lampzww
     * @Date: 15:17  2020/5/27
     */
    protected function getTreeId($parent_id)
    {
        $model = Db::table('s_dict_category');
        $tree = '00001';
        //  获取当前最大的树节点id
        // $maxMenuTreeId = $menuModel->where('menu_parent_id', $parent_menu_id)->order('menu_treeid', 'desc')->limit(1)->select();
        $query = "select max(dict_category_treeid) as max_treeid from s_dict_category where dict_category_parent_id='" . $parent_id . "'";
        $max = Db::query($query);
        $maxTreeId = $max[0]['max_treeid'];

        // 如果当前分类的父分类id为0  即当前分类是顶级分类 新增时用
        if ($parent_id == "0") {
            // 顶级分类 的最大值存在 则新的树id 为 最大值+1
            $tree = (str_pad($maxTreeId + 1, strlen($maxTreeId), "0", STR_PAD_LEFT));

        } else {
            // 如果不是顶级分类
            // 如果有最大值  最大值+1
            if (strlen($maxTreeId) > 0) {
                $tree = (str_pad($maxTreeId + 1, strlen($maxTreeId), "0", STR_PAD_LEFT));
            } else {
                // 如果没有 需要获取父节点的treeid
                $info = $model->where('dict_category_id', $parent_id)->find();

                $tree = $info['dict_category_treeid'] . $tree;
            }

        }
        return $tree;


    }

    /**
     * 添加编辑分类
     * @param XhyRequest $XhyRequest
     * @param string $id
     * @return Json
     * @author: lampzww
     * @Date: 15:17  2020/5/27
     */
    private function onSaveData(XhyRequest $request, $id = '')
    {
        $param = $request->param();

        if($param['type'] == 2){
            $rule = [
                'dict_category_id'      =>  'require',
                'dict_naming'           =>  'require',
            ];
            $msg = [
                'dict_category_id.require'    =>  '请选择字典分类！',
                'dict_naming.require'         =>  '请填写字典名称！',
            ];
            $model = Db::table('s_dict_name');
        }else{
            $rule = [
                'category_name'     =>  'require',
            ];
            $msg = [
                'category_name.require'         =>  '请填写分类名称！',
            ];
            $model = Db::table('s_dict_category');
        }

        $isSuccess = false;
        //参数验证
        $paramError = $this->checkParams($rule, $msg);
        if ($paramError != "") {
            return $this->fail($paramError);
        }

        //判断添加或修改
        if ($id == "") {
            $action = "addnew";
        } else {
            $action = "modify";
        }

        Db::startTrans();
        try {
            if ($action == "addnew") {
                if($param['type'] == 2){
                    $logTitle = "添加字典" . $param['dict_naming'] . '成功';
                    $param['dict_name_id'] = Utils::guid();
                    $param['sort_number'] = (int)Db::table('s_dict_name')->where('dict_category_id', $param['dict_category_id'])->max('sort_number') + 1;
                    unset($param['type']);
                    $param['data_level'] = 1;
                    $param['is_protected']  =   isset($param['is_protected']) ? $param['is_protected'] : 0;
                }else{
                    $logTitle = "添加分类" . $param['category_name'] . '成功';
                    // 如果是子分类 自动写入父类模块
                    $parentId = $param['dict_category_parent_id'] ?? 0;
                    $param['dict_category_treeid'] = self::getTreeId($parentId);

                    $param['is_end'] = 0;
                    $param['dict_category_id'] = Utils::guid();
                    $param['physics_name']  =   $param['category_name'];
                    $param['sort_number'] = self::getSortNumb($parentId);

                }
                $param['created'] = Utils::now();
                $param['modified'] = Utils::now();
                $param['creater'] = $this->userName;
                $param['modifier'] = $this->userName;
                $model->insert($param);

            } else {
                if($param['type'] == 2){
                    $logTitle = "编辑字典" . $param['dict_naming'] . '成功';
                    $info = $model->where('dict_name_id', $id)->find();
                    if (!$info) {
                        return $this->fail('字典不存在');
                    }
                    $param['is_protected']  =   isset($param['is_protected']) ? $param['is_protected'] : 0;
                    $param['modifier'] = $this->userName;
                    unset($param['type']);
                    $model->update($param, ['dict_name_id' => $id]);
                }else{
                    $logTitle = "编辑分类" . $param['category_name'] . '成功';
                    $info = $model->where('dict_category_id', $id)->find();
                    if (!$info) {
                        return $this->fail('分类不存在');
                    }
                    $param['modifier'] = $this->userName;
                    $param = array_merge($param, [
                        'dict_category_parent_id' => $info['dict_category_parent_id']
                    ]);
                    $model->update($param, ['dict_category_id' => $id]);
                }

            }
            $isSuccess = true;
            $logDetail = "";
            $this->logInfo($logTitle, $logDetail);
            Db::commit();
            return $this->success([],$logTitle);
        } catch (\Exception $exception) {
            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();
            $this->logError("添加/修改出错", $exception);
            return $this->fail($exception->getMessage());
            //endregion
        } finally {
            //region 结束
            if ($isSuccess == false) {
                Db::rollback();
            }
        }

    }

    /**
     * 获取父节点最大的sort_numb
     * @param $parent_id
     * @return int|mixed
     * @author: lampzww
     * @Date: 15:17  2020/5/27
     */
    private function getSortNumb($parent_id)
    {
        $max = Db::table('s_dict_category')->where('dict_category_parent_id', $parent_id)->max('sort_number');
        return ($max + 1);
    }

    /**
     * 读取单条分类数据
     * @param $id
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: lampzww
     * @Date: 14:33  2020/5/27
     */
    public function read($id)
    {
        $type = request()->param('type','','intval');
        if($type == 2){
            $row = Db::table("s_dict_name")->where("dict_name_id", $id)->field('dict_name_id,
                     dict_naming, 
                     dict_category_id, 
                     dict_explain, 
                     is_enabled, 
                     is_protected')->find();
        }else{
            $row = Db::table("s_dict_category")->where("dict_category_id", $id)->field('dict_category_id,
                     category_name, 
                     is_enabled, 
                     remark_info')->find();
        }

        return $this->success($row);
    }

    /**
     * 修改状态
     * @param $id
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: lampzww
     * @Date: 17:09  2020/5/28
     */
    public function switchStatus($id): Json
    {
        $ids = Utils::stringToArrayBy($id);

        foreach ($ids as $_id) {

            $info = Db::table('s_dict_name')->where('dict_name_id', $_id)->find();
            if ($info['is_enabled'] == 1) {
                Db::table('s_dict_name')->where('dict_name_id', $_id)->update(['is_enabled' => 0]);
            } else {
                Db::table('s_dict_name')->where('dict_name_id', $_id)->update(['is_enabled' => 1]);
            }
        }

        return $this->success([], '操作成功');
    }


    /**
     * 字典排序
     * @return Json
     * @throws \think\db\exception\DbException
     * @author: lampzww
     * @Date: 18:26  2020/5/28
     */
    public function listSort(): Json
    {

        $id     =   request()->param('id','','trim');
        $value  =   request()->param('value',0,'intval');
        Db::table('s_dict_name')->where('dict_name_id', $id)->update(['sort_number' => $value]);
        return $this->success(Db::table('s_dict_name')->getLastSql(), '操作成功');
    }
}
