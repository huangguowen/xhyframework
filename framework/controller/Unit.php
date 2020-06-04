<?php

/**
 * name:单位管理
 * date:2020-05-01
 * user:
 * note:
 */

namespace xhyadminframework\controller;

use xhyadminframework\base\XhyController;
use xhyadminframework\base\XhyRequest;
use xhyadminframework\Utils;
use think\facade\Db;

class unit extends XhyController
{
    protected $unit_role_id = 'CCA511AC2C9B54D2858909E299818FD3';
    /**
     * 获取单位列表
     * @time 2020年01月09日
     * @param XhyRequest $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function index(XhyRequest $request)
    {

        //region 准备参数（验证参数/定义变量/排序字段）
        //--------------------------------------------------------------------

        //@验证规则
        $rule = [
            'page' => 'require|number',
            'limit' => 'require|number',
            'unit_status' => 'number|in:0,1,4',
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
        $unit_name = $request->param("unit_name");
        $unit_status = $request->param("is_enabled");
        $unit_type = $request->param("unit_type","","trim");
        $unit_time = $request->param("unit_time","","trim");

        //@默认排序字段名称
        $defaultOrderbyField = "created desc";

        //排序条件
        $orderby = Utils::getOrderby($orderbyField, $orderbyDir, $defaultOrderbyField);
        #endregion

        //region 获取查询条件(查询条件/获取记录总数)
        //--------------------------------------------------------------------
        //@拼接查询条件
        $where = "";
        $where .= Utils::getString("and (unit_name like '%{0}%' or unit_code like '%{0}%')", $unit_name);
        $where .= Utils::getNumber("and unit_status = {0}", $unit_status);
        $where .= Utils::getNumber("and unit_type = {0}", $unit_type);
        if($unit_time){
            list($stime,$etime) = explode(' - ',$unit_time);
            $where  .=  Utils::getString("and created >= '{0}'", $stime);
            $where  .=  Utils::getString("and created <= '{0}'", $etime);
        }

        //@获取记录总数
        $getCountSql = "select count(*) as total from v_unit where 1=1 $where";
        $totalCount = Db::query($getCountSql)[0]["total"];

        //endregion

        //region 编写查询语句
        //--------------------------------------------------------------------
        $mainSql = "
            select
                    unit_id as id,
                    unit_name,
                    unit_code,
                    unit_status_text,
                    begin_datetime,
                    end_datetime,
                    created
            from
                    v_unit
            where 1=1
            $where $orderby
        ";

        $mainSql = Utils::getPagingSql($mainSql, $pageIndex, $pageSize);
        $data = Db::query($mainSql);
        return $this->listData($data, $totalCount, $pageIndex, $pageSize, $where);
        //endregion
    }

    /**
     * 获取单位记录
     * @time 2020年01月09日
     * @param XhyRequest $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function read($id)
    {
        $row = Db::table("s_unit")->where("unit_id", $id)->find();
        $row['begin_datetime'] = substr($row['begin_datetime'], 0, 10);
        $row['end_datetime'] = substr($row['end_datetime'], 0, 10);
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

        //参数验证规则
        $rule = [
            'unit_name'         =>  'require',
            'unit_code'         =>  'require',
            'unit_type'         =>  'require',
            'unit_status'       =>  'number|in:0,1,4',
            'mobile_number'     =>  'mobile',
            'begin_datetime'    =>  'require|date',
            'end_datetime'      =>  'require|date|gt:begin_datetime',
            'email_address'     =>  'email',
        ];

        $msg = [
            'unit_name.require'         =>  '请填写单位名称！',
            'unit_code.require'         =>  '请填写单位代码！',
            'email_address'             =>  '邮箱格式错误！',
            'end_datetime.gt'           =>  '结束时间必须大于开始时间！',
            'unit_type.require'         =>  '请选择单位类型！',
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
            'unit_code' => $request->param("unit_code"),
            'unit_name' => $request->param("unit_name"),
            'unit_type' => $request->param("unit_type"),
            'unit_status' => $request->param("unit_status","0"),
            'mobile_number' => $request->param("mobile_number"),
            'email_address' => $request->param("email_address"),
            'begin_datetime' => substr($request->param("begin_datetime"), 0, 10),
            'end_datetime' => substr($request->param("end_datetime"), 0, 10),
        ];

        //endregion

        Db::startTrans();
        try {
            $check_map = [
                ['unit_code','=',$entity['unit_code']]
            ];
            $action != "addnew" &&  $check_map[] = ['unit_id','<>',$id];
            if(Db::table("s_unit")->where($check_map)->count())return $this->fail("单位代码已存在！");
            if ($action == "addnew") {

                //region 添加操作
                $msg = "添加成功";
                $logTitle = "添加单位{$entity['unit_name']}";
                $entity["unit_id"] = Utils::guid();
                $entity["admin_id"] = $this->userID;
                $entity["created"] = Utils::now();
                $entity["creater"] = $this->userName;

                Db::table("s_unit")->insert($entity);

                // 创建对应单位管理员用户
                $userInsertData = [
                    'user_id' => Utils::guid(),
                    'user_name' => $entity['unit_name'] . '单位管理员',
                    'user_type' => 'admin',
//                    'login_name' => 'unitAdmin_' . ($entity["unit_code"] ?: $entity["unit_id"]),
                    'login_name' => 'unitAdmin_' . $entity["unit_code"],
                    'login_password' => password_hash('123456', PASSWORD_DEFAULT),
                    'is_enabled' => 1, // 启用
                    'is_protected' => 1, // 受保护不允许删除
                    'created' => $entity["created"],
                    'creater' => $this->userName,
                ];
                Db::table("s_user")->insert($userInsertData);

                Db::table("s_user_in_unit")->insert([
                    'unit_id' => $entity["unit_id"],
                    'user_id' => $userInsertData["user_id"],
                    'join_datetime' => $entity["created"],
                    'remark_info' => '添加单位时自动创建！',
                ]);

//                // 创建私有角色
//                $roleInsertData = [
//                    "role_id" => Utils::guid(),
//                    "role_type" => 2,
//                    'role_name' => $entity['unit_name'] . '单位管理员',
//                    "sort_number" => 0,
//                    'private_user_id' => $userInsertData["user_id"],
//                    'is_enabled' => 1, // 启用
//                    'is_protected' => 1, // 受保护不允许删除
//                    'remark_info' => '添加单位时自动创建！',
//                    "created" => $entity["created"],
//                    "creater" => $this->userName,
//                ];
//                if (!Db::table("s_role")->insert($roleInsertData)){
//                    return $this->fail("创建单位管理员角色失败！");
//                }
                $roleData = Db::table('s_role')->where('role_id', $this->unit_role_id)->find();
                if (!$roleData) {
                    return $this->fail("找不到单位管理员角色，创建单位失败");
                }
//                Db::table("s_user_in_role")->insert([
//                    'user_id' => $userInsertData["user_id"],
//                    'role_id' => $roleInsertData["role_id"],
//                    'is_main' => 0,
//                    'assigned' => $entity["created"],
//                    'remark_info' => '添加单位时自动创建！',
//                ]);
//                // 查找单位管理员角色
//                $roleData = Db::table('v_role')->where('role_identity', 'unitAdmin')->find();
//                if (!$roleData) {
//                    return $this->fail("找不到单位管理员角色，创建单位失败");
//                }
                Db::table("s_user_in_role")->insert([
                    'user_id' => $userInsertData["user_id"],
                    'role_id' => $roleData["role_id"],
                    'is_main' => 0,
                    'assigned' => $entity["created"],
                    'remark_info' => '添加单位时自动创建！',
                ]);

                //endregion
            } else {

                //region 修改操作 (获取修改前记录/执行修改)
                //获取修改前的记录
                $before = Db::table("s_unit")->where("unit_id", $id)->find();
                if ($before["unit_id"] == "") {
                    return $this->fail("记录不存在，请刷新重试");
                }

                $msg = "修改成功";
                $logTitle = "修改单位{$before["unit_name"]}信息";
                $entity["modified"] = Utils::now();
                $entity["modifier"] = $this->userName;

                Db::table("s_unit")->where("unit_id", $id)->update($entity);
                $role_info    =   Db::table("s_user_in_unit")->alias('a')
                                ->join('s_user_in_role b','a.user_id = b.user_id')->where('a.unit_id',$id)->field('b.role_id,a.user_id')->find();
                Db::table("s_role")->where("role_id", $role_info['role_id'])->update(['role_name'=>$entity['unit_name'] . '单位管理员']);
                Db::table("s_user")->where("user_id", $role_info['user_id'])->update(['login_name'=>'unitAdmin_' . $entity["unit_code"],]);

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
            $this->logError("添加修改单位出错", $e);
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
        $id = Utils::safeValue($id);
        if ($id == "") {
            return $this->fail("参数不能为空");
        }
        //endregion

        Db::startTrans();
        try {

            //region 获取删除前的记录
            $before = Db::table("s_unit")->where("unit_id", $id)->find();
            if ($before["unit_id"] == "") {
                return $this->fail("记录不存在，请刷新重试");
            }
            //endregion

            //region 执行删除

//            获取删除前的单位包含用户
            $before_user_id    =   Db::table("s_user_in_unit")->where("unit_id", $id)->column('user_id');
            //删除单位包含的用户
            Db::table("s_user_in_unit")->where("unit_id", $id)->delete();
            //删除用户历史密码
            Db::table('s_user_history_password')->where("user_id", "in", $before_user_id)->delete();
            //删除用户隶属角色
            Db::table('s_user_in_role')->where("user_id", "in", $before_user_id)->delete();
            //删除人员扩展
            Db::table('s_user_extend')->where("user_id", "in", $before_user_id)->delete();
            //删除用户数据
            Db::table('s_user')->where("user_id", "in", $before_user_id)->delete();

            //删除单位主表
            $effect = Db::table("s_unit")->where("unit_id", $id)->delete();
            if ($effect <= 0) {
                return $this->fail("删除失败，可能记录不存在，请刷新重试");
            }
            //endregion

            //region 完成（提交事务、记录日志、返回结果）
            $this->logInfo("删除单位记录{名称={$before["unit_name"]}");
            Db::commit();
            $isSuccess = true;

            return $this->success("删除成功");
            //endregion

        } catch (\Exception $e) {

            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();

            //删除出错处理
            $this->logError("删除单位记录出错", $e);
            return $this->fail("删除单位失败", $e);

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

}
