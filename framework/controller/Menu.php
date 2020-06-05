<?php

/**
 * name：菜单设置
 * date:2020-05-013
 * user:yjj
 */

namespace xhyadminframework\controller;


use xhyadminframework\base\XhyRequest;

use think\Request as Request;

use xhyadminframework\base\XhyController;
use xhyadminframework\XhyResponse;
use xhyadminframework\Tree;
use xhyadminframework\model\Menu as MenuModel;
use xhyadminframework\Utils;
use think\facade\Db;
use think\response\Json;

class Menu extends XhyController
{
    /**
     * 获取树列表
     * 查询时需要显示父节点
     * @param XhyRequest $XhyRequest
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(): Json
    {
        //$t1=microtime(true);
        $menuModel = new MenuModel();
        // 获取菜单
        $menuList = $menuModel->getList();
        $treeList = Tree::done($menuList, "0", 'menu_parent_id', 'children', 'id');
        // 添加虚拟主菜单
        $list[] = [
            'id' => '00',
            'title' => '菜单',
            'children' => $treeList
        ];
        return $this->success($list);
    }

    public function actionGetRoleList(): Json
    {
        $menuModel = new MenuModel();
        // 获取菜单类型
        $menuList = $menuModel->roleGetList();
        $treeList = Tree::done($menuList, "0", 'parent_id', 'children', 'id');
        $treeList = Tree::resetTree($treeList);
        return XhyResponse::success($treeList);
    }

    /**
     * 添加菜单
     * @param XhyRequest $XhyRequest
     * @return Json
     */
    public function save(XhyRequest $XhyRequest): Json
    {
        return $this->onSaveData($XhyRequest);

    }

    /**
     * 更新菜单
     * @param $id
     * @param XhyRequest $XhyRequest
     * @return Json
     */
    public function update($id, XhyRequest $XhyRequest): Json
    {
        return $this->onSaveData($XhyRequest, $id);

    }

    /**
     * 删除
     * @param $id
     * @return Json
     */
    public function delete($id): Json
    {
        //region 准备参数
        $isSuccess = false;
        $id = Utils::safeValue($id);
        if ($id == "") {
            return $this->fail("参数不能为空");
        }
        //endregion
        $menuModel = new MenuModel();
        Db::startTrans();
        try {
            if ($menuModel->where('menu_parent_id', $id)->find()) {
                return $this->fail('存在子菜单，无法删除');
            }
            $before = $menuModel->where('menu_id', $id)->find();
            if (!$before) {
                return $this->fail('菜单不存在');
            }
            if ($before->is_protected == 1) {
                return $this->fail('菜单受保护，不允许删除');
            }
            // 获取菜单下的操作
            $acitonInfo = Db::name('s_menu_function')->where('menu_id', $id)->value('menu_function_id');
            // 删除角色拥有的菜单操作权限
            if ($acitonInfo) {
                Db::name('s_role_function_permission')->where('menu_function_id', 'in', $acitonInfo)->where('menu_id', $id)->delete();
            }
            // 删除角色拥有的菜单权限
            Db::name('s_role_menu_permission')->where('menu_id', $id)->delete();
            // 删除菜单的操作权限
            Db::name('s_menu_function')->where('menu_id', $id)->delete();
            // 删除菜单
            $menuModel->where('menu_id', '=', $id)->delete();
            //日志
            $logTitle = "删除菜单" . $before->menu_name . "成功";
            $logDetail = '';
            $this->logInfo($logTitle, $logDetail);
            Db::commit();
            $isSuccess = true;
            return $this->success('删除成功');

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
     * 菜单向上向下排序
     * @param XhyRequest $XhyRequest
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */

    public function sort(XhyRequest $XhyRequest)
    {
        $rule = [
            'id' => 'require',
            'action' => 'require'
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg);
        if ($paramError != "") {
            return $this->fail($paramError);
        }
        try {
            $menuId = $XhyRequest->param('id');
            $action = $XhyRequest->param('action');
            $menuModel = new MenuModel();
            $menuInfo = $menuModel->where('menu_id', $menuId)->find();
            if (!$menuInfo) {
                return $this->fail('菜单不存在');
            }
            $op = $action == 'up' ? '<' : '>';
            $sort = $action == 'up' ? 'desc' : 'asc';
            $result = $menuModel->updateMenuSortNumber($menuInfo, $op, $sort, $this);
            return $this->success([$result]);
        } catch (\Exception $exception) {
            return $this->fail($exception->getMessage());

        }

    }

    /**
     * 菜单操作列表
     * @param $id
     * @param XhyRequest $XhyRequest
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */

    public function functionList(XhyRequest $XhyRequest)
    {
        $menuModel = new MenuModel();
        //分页参数
        $pageIndex = $XhyRequest->param("page") ?: 1;           //当前页码
        $pageSize = $XhyRequest->param("limit") ?: 10;           //每页大小
        $id = $XhyRequest->param("id") ?: 10;           //每页大小


        // 判断menu是否存在
        $menuInfo = $menuModel->where('menu_id', $id)->find();
        if (!$menuInfo) {
            return $this->fail('菜单不存在');
        }
        $where = "";
        $where .= Utils::getString("and menu_id = '{0}'", $id);

        $getCountSql = "select count(*) as total from s_menu_function where 1=1$where";

        $totalCount = Db::query($getCountSql)[0]["total"];

        $defaultOrderbyField = 'created desc';
        $mainSql = "
            select  
                  *
            from
                  s_menu_function   
           where 1=1$where" . "order by " . $defaultOrderbyField;


        $mainSql = Utils::getPagingSql($mainSql, $pageIndex, $pageSize);

        $data = Db::query($mainSql);
        return $this->listData($data, $totalCount, $pageIndex, $pageSize);

    }

    /**
     * 添加操作
     * @param Request $request
     * @return Json
     */

    public function functionAdd(XhyRequest $XhyRequest)
    {
        return $this->onFunctionSaveData($XhyRequest);
    }

    /**
     * 删除操作（可批量）
     * @param $id
     * @return Json
     */

    public function functionDel($id): Json
    {
        //region 准备参数
        $isSuccess = false;
        $id = Utils::safeValue($id);
        if ($id == "") {
            return $this->fail("参数不能为空");
        }

        Db::startTrans();
        $menuModel = new MenuModel();
        try {
            $actionInfo = Db::name('s_menu_function')->whereIn('menu_function_id', $id)->find();
            if (!$actionInfo) {
                return $this->fail('删除的记录不存在');
            }
            $menuModel->deleteFunction($id);
            //日志
            $logTitle = "删除" . $actionInfo['function_name'] . "成功";
            $logDetail = '';
            $this->logInfo($logTitle, $logDetail);
            Db::commit();
            $isSuccess = true;
            return $this->success('删除成功');
        } catch (\Exception $exception) {
            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();
            $this->logError("删除操作出错", $exception);
            return $this->fail($exception->getMessage());
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
     * 更新操作
     * @param $id
     * @param Request $request
     * @return Json
     */

    public function functionUpdate($id, XhyRequest $XhyRequest)
    {
        return $this->onFunctionSaveData($XhyRequest, $id);
    }

    /**
     * 移动节点
     * 需要更新树节点
     * @param XhyRequest $XhyRequest
     * @return Json
     */

    public function sortExchange(XhyRequest $XhyRequest)
    {

        $isSuccess = false;
        $rule = [
            'dropId' => 'require',
            'dragId' => 'require'
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg);
        if ($paramError != "") {
            return $this->fail($paramError);
        }

        $menuModel = new MenuModel();
        Db::startTrans();
        try {
            $dropId = $XhyRequest->param('dropId');
            $dragId = $XhyRequest->param('dragId');
            $to = $XhyRequest->param('to');
            // 00 代表是虚拟主菜单
            if ($dropId != '00') {
                // 判断两条数据是否存在
                $dropInfo = $menuModel->where('menu_id', $dropId)->find();
                if (!$dropInfo) {
                    return $this->fail("目标记录不存在，请刷新重试");
                }
            }
            $dragInfo = $menuModel->where('menu_id', $dragId)->find();
            if (!$dragInfo) {
                return $this->fail("移动的记录不存在，请刷新重试");
            }
            $newMenuParentId = $dropId == '00' ? 0 : $dropId;
            $newTreeId = self::getTreeId($newMenuParentId);


            $data = [
                'menu_parent_id' => $newMenuParentId,
                'sort_number' => self::getSortNumb($newMenuParentId),
                'menu_treeid' => $newTreeId
            ];

            if ($to == "to_parent") {
                // 获取子节点
                $child = $menuModel->where('menu_treeid', 'like', $dragInfo['menu_treeid'] . '%')->order('menu_treeid', 'asc')->select()->toArray();
                //echo $newTreeId;
                foreach ($child as $key => $value) {
                    $value['menu_treeid'] = $newTreeId . substr($value['menu_treeid'], strlen($dragInfo['menu_treeid']));
                    $menuModel->where('menu_id', $value['menu_id'])->update($value);
                }
            }
            //更新
            $menuModel->where('menu_id', $dragInfo['menu_id'])->update($data);

            //日志
            $logTitle = "移动" . $dragInfo['menu_name'] . "成功";
            $logDetail = '';
            $this->logInfo($logTitle, $logDetail);

            Db::commit();
            $isSuccess = true;
            return $this->success('移动成功');

        } catch (\Exception $exception) {
            Db::rollback();
            $this->logError("移动出错", $exception);
            return $this->fail($exception->getMessage());
        } finally {
            //region 结束
            if ($isSuccess == false) {
                Db::rollback();
            }
        }
    }

    /**
     * 通过当前菜单的父菜单id获取树节点ID
     * @param $parent_menu_id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getTreeId($parent_menu_id)
    {
        $menuModel = new MenuModel();
        $tree = '00001';
        //  获取当前最大的树节点id
        // $maxMenuTreeId = $menuModel->where('menu_parent_id', $parent_menu_id)->order('menu_treeid', 'desc')->limit(1)->select();
        $query = "select max(menu_treeid) as max_treeid from s_menu where menu_parent_id='" . $parent_menu_id . "'";
        $max = Db::query($query);
        $maxMenuTreeId = $max[0]['max_treeid'];

        // 如果当前菜单的父菜单id为0  即当前菜单是顶级菜单 新增时用
        if ($parent_menu_id == "0") {
            // 顶级菜单 的最大值存在 则新的树id 为 最大值+1
            $tree = (str_pad($maxMenuTreeId + 1, strlen($maxMenuTreeId), "0", STR_PAD_LEFT));

        } else {
            // 如果不是顶级菜单
            // 如果有最大值  最大值+1
            if (strlen($maxMenuTreeId) > 0) {
                $tree = (str_pad($maxMenuTreeId + 1, strlen($maxMenuTreeId), "0", STR_PAD_LEFT));
            } else {
                // 如果没有 需要获取父节点的treeid
                $menuInfo = $menuModel->where('menu_id', $parent_menu_id)->find();

                $tree = $menuInfo->menu_treeid . $tree;
            }

        }
        return $tree;


    }

    /**
     * 添加编辑菜单
     * @param XhyRequest $XhyRequest
     * @param string $id
     * @return Json
     */
    private function onSaveData(XhyRequest $XhyRequest, $id = '')
    {
        $param = $request->param();
        $rule = [
            'link_page' => 'require',
            'permission_mark' => 'require',
            'menu_name' => 'require',
            'module' => 'require',
            'component_name' => 'require'
        ];
        $msg = [];
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
        $menuModel = new MenuModel();
        Db::startTrans();
        try {
            if ($action == "addnew") {
                $logTitle = "添加菜单" . $param['menu_name'] . '成功';
                // 如果是子分类 自动写入父类模块
                $parentId = $param['menu_parent_id'] ?? 0;
                $param['menu_treeid'] = self::getTreeId($parentId);
                $param['created'] = Utils::now();
                $param['creater'] = $this->userName;
                $param['menu_id'] = Utils::guid();
                $param['sort_number'] = self::getSortNumb($parentId);
                $menuModel->insert($param);

            } else {
                $logTitle = "编辑菜单" . $param['menu_name'] . '成功';
                $menuInfo = $menuModel->where('menu_id', $id)->find();
                if (!$menuInfo) {
                    return $this->fail('菜单不存在');
                }
                $param = array_merge($param, [
                    'menu_parent_id' => $menuInfo->menu_parent_id
                ]);
                // 如果是父分类需要更新所有子分类的模块
                if (!$menuInfo->menu_parent_id) {
                    $menuModel->update([
                        'module' => $param['module'],
                    ], ['menu_parent_id' => $menuInfo->menu_parent_id]);
                }
                $menuModel->update($param, ['menu_id' => $id]);
            }
            $isSuccess = true;
            $logDetail = "";
            $this->logInfo($logTitle, $logDetail);
            Db::commit();
            return $this->success($msg);
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
     * 添加/编辑 操作按钮
     * @param XhyRequest $XhyRequest
     * @param string $id
     * @return Json
     */
    private function onFunctionSaveData(XhyRequest $XhyRequest, $id = '')
    {
        $rule = [
            'function_name' => 'require'
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg);
        if ($paramError != "") {
            return $this->fail($paramError);
        }
        $isSuccess = false;
        $param = $XhyRequest->param();

        $logModel = new MenuModel();
        //判断添加或修改
        if ($id == "") {
            $action = "addnew";
        } else {
            $action = "modify";
        }
        Db::startTrans();
        try {
            $where = $action == 'addnew' ? $param['menu_id'] : $id;
            if ($action == 'addnew') {
                //日志
                $logTitle = "添加" . $param['function_name'] . "成功";
                $param['menu_function_id'] = Utils::guid();
                $param['created'] = Utils::now();
                $param['creater'] = $this->userName;

                $menuAction = Db::table('s_menu_function')
                    ->where('menu_id', $where)
                    ->whereRaw("'function_name'=:function_name or 'function_identity'=:function_identity ", ['function_name' => $param['function_name'], 'function_identity' => $param['function_identity']])
                    ->find();
                if ($menuAction) {
                    return $this->fail('功能标识/控制器名称已存在');
                }
                $logModel->insertFunction($param);
            } else {
                // 编辑判断是否存在
                //日志
                $logTitle = "更新" . $param['function_name'] . "成功";
                $logModel->updateFunction($id, $param);
            }
            $logDetail = "";
            $this->logInfo($logTitle, $logDetail);

            Db::commit();
            return $this->success($msg);
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
     * @param $parentMenuId
     * @return int|mixed
     */
    private function getSortNumb($parentMenuId)
    {
        $menuModel = new MenuModel();
        $max = $menuModel->where('menu_parent_id', $parentMenuId)->max('sort_number');
        return ($max + 1);

    }

    public function read($id)
    {

        $row = Db::table("v_menu")->where("menu_id", $id)->field('menu_id,
                     menu_name, 
                     link_page,
                     component_name,
                     small_icon,
                     is_enabled,
                     is_expand,
                     is_protected,
                     inc_functions,
                    module,
                    permission_mark')->find();
        return $this->success($row);
    }

    public function switch($id)
    {
        $ids = Utils::stringToArrayBy($id);
        try {
        foreach ($ids as $_id) {
            $functionInfo = Db::table('s_menu_function')->where('menu_function_id', $_id)->find();
            $switch = $functionInfo['is_enabled'] == 1 ? 0 : 1;
            Db::table('s_menu_function')->where('menu_function_id', $_id)->update(['is_enabled' => $switch]);
            $logTitle = "启用/禁用" . $functionInfo['function_name'] . "成功";
            $logDetail = "";
            $this->logInfo($logTitle, $logDetail);
            return $this->success('操作成功');
        }}catch (\Exception $exception){
            $this->logError("启用/禁用出错", $exception);
            return $this->fail($exception->getMessage());
        }
    }
}
