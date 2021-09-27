<?php

/**
 * name:角色管理
 * date:2020-05-01
 * user:
 * note:
 */

namespace xhyadminframework\controller;

use xhyadminframework\base\XhyController;
use xhyadminframework\base\XhyRequest;
use xhyadminframework\XhyResponse;
use xhyadminframework\Tree;
use think\response\Json;
use think\facade\Db;
use xhyadminframework\Utils;
use xhyadminframework\model\Menu as MenuModel;

class role extends XhyController
{

    const DEFAULTMENUID = 'afef82e722794634a3eba727b616a3c2';
    /**
     * 获取角色列表
     * @time 2020年01月09日
     * @param XhyRequest $XhyRequest
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function index(XhyRequest $request)
    {

        //region 准备参数（验证参数/定义变量/排序字段）
        //--------------------------------------------------------------------

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
        $pageIndex = $request->param("page");           //当前页码
        $pageSize = $request->param("limit");           //每页大小
        $orderbyField = $request->param("sortField");   //排序字段  
        $orderbyDir = $request->param("sortOrder");     //排序方向

        //@查询参数
        $role_name = $request->param("role_name");
        $is_enabled = $request->param("is_enabled");

        //@默认排序字段名称
        $defaultOrderbyField = "created desc";

        //排序条件
        $orderby = Utils::getOrderby($orderbyField, $orderbyDir, $defaultOrderbyField);
        #endregion

        //region 获取查询条件(查询条件/获取记录总数)
        //--------------------------------------------------------------------
        //@拼接查询条件
        $where = "";
        $where .= Utils::getString("and role_name like '%{0}%'", $role_name);
        $where .= Utils::getNumber("and is_enabled = {0}", $is_enabled);

        //@获取记录总数
        $getCountSql = "select count(*) as total from v_role where 1=1 $where";
        $totalCount = Db::query($getCountSql)[0]["total"];

        //endregion

        //region 编写查询语句
        //--------------------------------------------------------------------
        $mainSql = "
            select  
                    role_id as id,
                    role_name,
                    remark_info,
                    is_enabled_text,
                    is_enabled,
                    created,
                    is_protected
            from
                    v_role 
            where 1=1 
            $where $orderby
        ";

        $mainSql = Utils::getPagingSql($mainSql, $pageIndex, $pageSize);
        $data = Db::query($mainSql);
        return $this->listData($data, $totalCount, $pageIndex, $pageSize);
        //endregion
    }


    
    /**
     * 获取角色记录
     * @time 2020年01月09日
     * @param XhyRequest $XhyRequest
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function read($id)
    {
        $row = Db::table("s_role")->where("role_id", $id)->find();
        return $this->success($row);
    }

    /**
     * 保存
     *
     * @time 2020年01月09日
     * @param XhyRequest $request
     * @return \think\response\Json
     */
    public function save(XhyRequest $request): \think\response\Json
    {
        return $this->onSaveData($request);
    }


    /**
     * 更新
     *
     * @time 2020年01月09日
     * @param $id
     * @param XhyRequest $request
     * @return \think\response\Json
     */
    public function update($id, XhyRequest $request): \think\response\Json
    {
        return $this->onSaveData($request, $id);
    }

    /**
     * 添加/修改数据
     */
    private function onSaveData(XhyRequest $request, $id = ""): \think\response\Json
    {
        //region 准备参数 (定义变量/获取参数/验证参数)
        //--------------------------------------------------
        //定义公共变量
        $msg = "";
        $logTitle = "";
        $logDetail = "";
        $action = "";
        $isSuccess = false;

        // @获取参数
        $role_name = $request->param("role_name");
        $role_identity = $request->param("role_identity");
        $is_enabled = $request->param("is_enabled");
        $is_protected = $request->param("is_protected");
        $remark_info = $request->param("remark_info");

        //参数验证规则
        $rule =   [
            'role_name' => 'require',
            'is_enabled' => 'number',
            'is_protected' => 'number',
        ];

        $msg = [
        ];

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

        //实体参数
        $entity = [
            "role_name" => $role_name,
            "role_identity" => $role_identity,
            "is_enabled" => $is_enabled,
            "is_protected" => $is_protected,
            "remark_info" => $remark_info,
        ];

        //endregion

        Db::startTrans();
        try {

            if ($action == "addnew") {
                //判断角色是否存在
                $exist = Db::table("s_role")->where('role_name',$role_name)->find();
                if($exist){
                    return $this->fail('角色名称已存在');
                }
                $guid = Utils::guid();
                //region 添加操作
                $msg = "添加成功";
                $logTitle = "添加角色$role_name";
                $entity["role_id"] = $guid;
                $entity["role_type"] = 1;
                $entity["sort_number"] = 0;
                $entity["created"] = Utils::now();
                $entity["creater"] = $this->userName;
                Db::table("s_role")->insert($entity);
                //默认每个角色都有保存主页权限
                Db::table("s_role_menu_permission")->insert(
                    [
                        'role_id' => $guid,
                        'menu_id' => self::DEFAULTMENUID,
                    ]
                );

                //endregion
            } else {

                //region 修改操作 (获取修改前记录/执行修改)
                //获取修改前的记录
                $before = Db::table("s_role")->where("role_id", $id)->find();
                if ($before["role_id"] == "") {
                    return $this->fail("记录不存在，请刷新重试");
                }
                $exist = Db::table("s_role")->where('role_name',$entity['role_name'])->where("role_id", '<>',$id)->find();
                if($exist){
                    return $this->fail('角色名称已存在');
                }
                $msg = "修改成功";
                $logTitle = "修改角色{$before["role_name"]}信息";
                $entity["modified"] = Utils::now();
                $entity["modifier"] = $this->userName;
                Db::table("s_role")->where("role_id", $id)->update($entity);

                //endregion
            }

            //region 完成 (写入日志/提交事务/返回结果)
            $this->logInfo($logTitle, $logDetail);
            Db::commit();
            $isSuccess = true;
            return $this->success($msg);
            //endregion 

        } catch (\Exception $e) {

            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();
            $this->logError("添加修改角色出错", $e);
            return $this->fail($e->getMessage());
            //endregion

        } finally {

            //region 结束
            if ($isSuccess == false) {
                Db::rollback();
            }
            //endregion
        }
    }


    /**
     * 删除
     *
     * @time 2020年01月09日
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id): \think\response\Json
    {
        //region 准备参数
        $isSuccess = false;
        $id =  Utils::safeValue($id);
        if ($id == "") {
            return $this->fail("参数不能为空");
        }
        //endregion

        Db::startTrans();
        try {

            //region 获取删除前的记录
            $before = Db::table("s_role")->where("role_id", $id)->find();
            if ($before["role_id"] == "") {
                return $this->fail("记录不存在，请刷新重试");
            }
            if ($before["is_protected"] == 1) {
                return $this->fail("受保护不可删除");
            }
            //endregion

            //region 执行删除

            //删除角色包含的用户
            $effect = Db::table("s_user_in_role")->where("role_id", $id)->delete();

            //删除角色包含的功能权限
            $effect = Db::table("s_role_function_permission")->where("role_id", $id)->delete();

            //删除角色包含的菜单权限
            $effect = Db::table("s_role_menu_permission")->where("role_id", $id)->delete();

            //删除角色主表
            $effect = Db::table("s_role")->where("role_id", $id)->delete();
            if ($effect <= 0) {
                return $this->fail("删除失败，可能记录不存在，请刷新重试");
            }
            //endregion

            //region 完成（提交事务、记录日志、返回结果）
            $this->logInfo("删除角色记录{名称={$before["role_name"]}");
            Db::commit();
            $isSuccess = true;

            return $this->success("删除成功");
            //endregion

        } catch (\Exception $e) {

            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();

            //删除出错处理
            $this->logError("删除角色记录出错", $e);
            return $this->fail("删除角色失败", $e);

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
     * 获取所有的菜单和功能权限数据
     */
    public function getAllMenuData(): Json
    {
        $permissions  = new MenuModel();
        // 获取菜单类型
        $menuList = $permissions->roleGetList();
        $treeList = Tree::done($menuList, "0", 'parent_id', 'children', 'id');
        $treeList = Tree::resetTree($treeList);
        return XhyResponse::success($treeList);
    }


    
    /**
     * 获取该角色已分配的菜单数据
     * @time 2020年01月09日
     * @param XhyRequest $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function getAssignedMenuData($id)
    {
        //查询该用户多少个 role_id
        $data = Db::table('s_role')->where('role_id', $id)->find();
        $data['permissionids'] = Db::table('s_role_menu_permission')
            ->field('menu_id')
            ->where('role_id', $id)
            ->union("SELECT menu_function_id as menu_id FROM s_role_function_permission where role_id = '$id'")
            ->select()
            ->toArray();
        return XhyResponse::success($data);
    }




    
    /**
     *
     * @time 2019年12月11日
     * @param $id
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function saveAssignMenu(XhyRequest $request): \think\response\Json
    {
        $id = $request->param('id');
        try {
            $isSuccess = false;
            $permissions = $request->param('permissions');
            $description = $request->param('role_identity') ?? '';
            $role_name = $request->param('role_name') ?? '';
            $is_protected = $request->param('is_protected') ?? '';
            Db::startTrans();
            if ($role_name) {
                //s_role
                $updataData = [
                    'role_identity' => $description,
                    'role_name' => $role_name,
                    'is_protected' => $is_protected,
                ];
                $updateRole = Db::table('s_role')->where('role_id', $id)->update($updataData);
                if ($updateRole === false) {
                    Db::rollback();
                    return XhyResponse::fail('更新失败');
                }
            }
            //s_menu
            if (!empty($permissions)) {
                //删除该角色下所有function_menu的权限
                $delete_menu_role = Db::table('s_role_function_permission')->where('role_id', $id)->delete();
                if ($delete_menu_role === false) {
                    Db::rollback();
                    return XhyResponse::fail('删除失败1');
                }
                //删除该角色下所有menu的权限
                $delete_role = Db::table('s_role_menu_permission')->where('role_id', $id)->delete();
                if ($delete_role === false) {
                    Db::rollback();
                    return XhyResponse::fail('删除失败2');
                }

                //查出所有的menu  和 function 的menu_id
                $s_function_menu = Db::table('s_menu_function')
                    ->field('menu_id, menu_function_id')
                    ->where('is_enabled', 1)
                    ->select()
                    ->toArray();
                $s_menu = Db::table('s_menu')
                    ->where('is_enabled', 1)
                    ->column('menu_id');
                $menuFunctionIds = array_column($s_function_menu, 'menu_function_id');
                $parent_function_menu_id = array_combine($menuFunctionIds, array_column($s_function_menu, 'menu_id'));
                $function_menu = array_intersect($menuFunctionIds, $permissions);
                $menu = array_intersect($s_menu, $permissions);
                //新增权限
                $insertMenuData = [];
                foreach ($menu as $k => $v) {
                    $insertMenuData[$k]['role_id'] = $id;
                    $insertMenuData[$k]['menu_id'] = $v;
                }
                $insert_role = Db::table('s_role_menu_permission')->insertAll($insertMenuData);
                if ($insert_role === false) {
                    Db::rollback();
                    return XhyResponse::fail('新增失败');
                }
                //新增function 权限
                $insertFunctionData = [];
                foreach ($function_menu as $k => $v) {
                    $insertFunctionData[$k]['role_id'] = $id;
                    $insertFunctionData[$k]['menu_function_id'] = $v;
                    $insertFunctionData[$k]['menu_id'] = $parent_function_menu_id[$v];
                }
                $insert_role = Db::table('s_role_function_permission')->insertAll($insertFunctionData);
                if ($insert_role === false) {
                    Db::rollback();
                    return XhyResponse::fail('新增失败');
                }
            }
            Db::commit();
            $isSuccess = true;
            return XhyResponse::success();
        } catch (\Exception $e) {
            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();
            $this->logError("添加修改学生出错", $e);
            return $this->fail($e->getMessage());
            //endregion
        } finally {
            if ($isSuccess == false) {
                Db::rollback();
            }
        }
    }

    /**
     *
     * @time 2020年5月27日
     * @param $id
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DbException
     */
    public function swtichStatus($id) :\think\response\Json
    {
        $ids = Utils::stringToArrayBy($id);

        foreach ($ids as $_id) {

            $user = Db::table('s_role')->where('role_id', $_id)->find();
            if ($user['is_enabled'] == 1) {
                $user = Db::table('s_role')->where('role_id', $_id)->update(['is_enabled' => 0]);
            } else {
                $user = Db::table('s_role')->where('role_id', $_id)->update(['is_enabled' => 1]);
            }
        }

        return XhyResponse::success([], '操作成功');
    }


}
