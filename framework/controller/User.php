<?php

/*
 * author: 黄国文
 * create_time: 2020/05/11
 *
 */

namespace xhyadminframework\controller;

use app\Request;
use xhyadminframework\base\XhyController;
use base\XhyAuth;
use xhyadminframework\XhyResponse;
use xhyadminframework\Tree;
use xhyadminframework\Utils;
use think\facade\Db;

class User extends XhyController
{

    /**
     *
     * @time 2020年04月24日
     * @param Request $request
     * @throws \think\db\exception\DbException
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        //region 准备参数（验证参数/定义变量/排序字段）
        //--------------------------------------------------------------------

        //@验证规则
        $rule = [
            'page' => 'require|number',
            'limit' => 'require|number',
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg);
        if ($paramError != "") {
            return $this->fail($paramError);
        }

        //分页参数
        $pageIndex = $request->param("page");           //当前页码
        $pageSize = $request->param("limit");           //每页大小
        //@查询参数
        $param = $request->param();
        $where=[];
        if($this->auth->user()->organizeid){
            $where[]= ['organizeid','=',$this->auth->user()->organizeid];
        }
        if(isset($param["user_name"])&&$param["user_name"]){
            $where[] = ['user_name','like','%'.$param["user_name"].'%'];
        };
        if(isset($param["login_name"])&&$param["login_name"]){
            $where[] = ['login_name','like','%'.$param["login_name"].'%'];
        };
        if(isset($param["is_enabled"])&&$param["is_enabled"]){
            $where[] = ['is_enabled','=',$param["is_enabled"]];
        };
        $data = Db::table('s_user')
            ->leftJoin('p_account','p_account.user_id=s_user.user_id')
            ->where($where)
            ->field('SQL_CALC_FOUND_ROWS s_user.*,s_user.user_id as id')
            ->page($pageIndex, $pageSize)
            ->order('create_time','desc')
            ->select()
            ->toArray();
        $totalCount = Db::query('SELECT FOUND_ROWS() c')[0]['c'];
        return $this->listData($data, $totalCount, $pageIndex, $pageSize);
        //endregion
    }

    /**
     *
     * @time 2020年05月15日
     * @param Request $request
     * @throws \think\db\exception\DbException
     * @return \think\response\Json
     */
    public function getRolesByUserId()
    {
        $where =[];
        if($this->auth->user()->organizeid){
            //如果有机构  则可选择角色排除超级管理员
            $where[]= ['role_identity','<>','admin'];
        }
        $role_ids = Db::table('s_role')->where('is_enabled', 1)->where($where)->select()->toArray();
        return $this->success($role_ids);
    }

    /**
     *
     * @time 2019年12月06日
     * @throws \Exception
     * @return string
     */
    public function create()
    {
    }

    /**
     *
     * @param $request $request
     * @time 2019年12月06日
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $rule = [
            'user_name|用户名' => 'require|max:20',
            'password|密码' => 'require|min:6|max:24',
//            'login_name|账号'    => 'require|email|unique:'.Users::class.',id',// 先不用了
            'login_name|账号' => 'require',
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg, $request->param());
        if ($paramError != "") {
            return $this->fail($paramError);
        }
        try {
            $isSuccess = true;
            $param = $request->param();

            //查询是否有该账号了
            $user = Db::table("s_user")->where('login_name', $request->param()['login_name'])->find();
            if (!empty($user)) {
                return XhyResponse::fail("账户已存在，请重新输入");
            }
            //增加
            Db::startTrans();
            $guid = Utils::guid();
            $c_time = Utils::now();;
            $userInsertData = [
                'user_id' => $guid,
                'user_name' => $param['login_name'],
                'user_type' => 'admin',
                'login_name' => $param['login_name'],
                'login_password' => password_hash($param['password'], PASSWORD_DEFAULT),
                'is_enabled' => 1,
                'is_protected' => 0,
                'created' => $c_time,
                'creater' => $this->auth->user()->user_name,
            ];

            Db::table("s_user")->insert($userInsertData);
            //todo yjj 添加账号至p_account
            $accountData =[
                'user_id'=>$guid,
                'nick_name'=>$param['login_name'],
                'category'=>2,
                'organizeid'=>(isset($param['school_id'])&&$param['school_id'])?$param['school_id']:$this->auth->user()->organizeid,
                'status'=>1,
                'create_time'=>$c_time
            ];
            Db::table('p_account')->insert($accountData);

            //批量增加角色
            foreach ($request->param()['roles'] as $v) {
                $roleData[] = [
                    'user_id' => $guid,
                    'role_id' => $v,
                    'is_main' => 1,
                    'assigned' => $c_time,
                ];
            }
            Db::table("s_user_in_role")->insertAll($roleData);
            $logTitle = "添加用户".$request->param('user_name')."成功";
            $logDetail = "";
            $this->logInfo($logTitle, $logDetail);
            $isSuccess = true;

            Db::commit();
            return XhyResponse::success('', '添加成功');
        } catch (Exception $e) {
            Db::rollback();
            return XhyResponse::fail("操作失败");
        } finally {

            //region 结束
            if ($isSuccess == false) {
                Db::rollback();
            }
            //endregion
        }
    }

    /**
     *
     * @time 2019年12月04日
     * @param $id
     * @return \think\response\Json
     */
    public function read($id, XhyAuth $auth)
    {
        $user = $auth->user();
        $user->roles = $this->user->getRole($id);
        //获取当前用户的所属组织
        $user->info = Db::table('p_account')->where('user_id',$id)->find();
        return XhyResponse::success($user);
    }

    /**
     * @param $id
     * @return string
     * @throws \Exception
     */
    public function edit($id)
    {
    }

    /**
     *
     * @time 2019年12月04日
     * @param $id
     * @param UpdateRequest $request
     * @return \think\response\Json
     */
    public function update($id, Request $request)
    {
        $request = $request->post();
        $isSuccess = false;
        $roles = $request['roles'];
        $school_id = $request['school_id'];
        unset($request['roles']);
        unset($request['school_id']);
        try {
            Db::startTrans();
            //保存s_user信息
            $upDataUser = Db::name('s_user')
                ->where('user_id', $id)
                ->update($request);
            if ($upDataUser === false) {
                Db::rollback();
                return $this->fail("更新失败");
            }
            if (isset($roles)) {
                $rolesData = [];
                //删除该用户的所有roles
                $deleteRoles = Db::name('s_user_in_role')
                    ->where('user_id', $id)
                    ->delete();
                if ($deleteRoles === false) {
                    Db::rollback();
                    return $this->fail("删除角色失败");
                }
                //保存roles 信息
                foreach ($roles as $k => $v) {
                    $rolesData[$k]['user_id'] = $id;
                    $rolesData[$k]['role_id'] = $v;
                    $rolesData[$k]['is_main'] = 1;
                    $rolesData[$k]['assigned'] = date('Y-m-d H:i:s', time());
                }
                $insertRoles = Db::name('s_user_in_role')->insertAll($rolesData);
                if ($insertRoles === false) {
                    Db::rollback();
                    return $this->fail("插入角色失败");
                }
            }
            if(isset($school_id)){
                //
                $info = Db::table('p_account')->where('user_id',$id)->find();
                if($info){
                    Db::table('p_account')->where('user_id',$id)->update(['organizeid'=>$school_id]);
                }else{
                    Db::table('p_account')->insert([
                        'user_id'=>$id,
                        'status'=>1,
                        'organizeid'=>$school_id,
                        'create_time'=>Utils::now(),
                        'category'=>2
                    ]);
                }
            }
            $logTitle = "修改用户id{$id}信息";
            $this->logInfo($logTitle, '');

            Db::commit();
            return XhyResponse::success();
        } catch (\Exception $e) {

            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();
            $this->logError("修改用户出错", $e);
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
     *
     * @time 2019年12月04日
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id): \think\response\Json
    {
        $ids = Utils::stringToArrayBy($id);
        try {
            $isSuccess = false;
            Db::startTrans();
            foreach ($ids as $_id) {
                if ($_id == config('xhy.permissions.super_admin_id')) {
                    Db::rollback();
                    return $this->fail("管理员账号不能被删除！");
                }
                if ($_id == $this->auth->user()->user_id) {
                    Db::rollback();
                    return $this->fail("不能删除自己！");
                }
                $deleteUnit = Db::table('s_user_in_unit')->where(['user_id' => $_id])->delete();
                if ($deleteUnit === false) {
                    Db::rollback();
                    return $this->fail("删除失败，单位删除失败，请刷新重试");
                }

                $deleteRoles = Db::table('s_user_in_role')->where(['user_id' => $_id])->delete();
                if ($deleteRoles === false) {
                    Db::rollback();
                    return $this->fail("删除失败，可能记录不存在，请刷新重试");
                }
                $deleteUser = Db::table('s_user')->where(['user_id' => $_id])->delete();
                if ($deleteUser === false) {
                    Db::rollback();
                    return $this->fail("删除失败，可能记录不存在，请刷新重试");
                }
            }
            $this->logInfo("删除用户id{$id}");

            Db::commit();
            $isSuccess = true;
            return $this->success("删除成功");
        } catch (\Exception $e) {

            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();

            //删除出错处理
            $this->logError("删除用户出错", $e);
            return $this->fail("删除用户失败", $e);

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
     *
     * @time 2019年12月07日
     * @param $id
     * @return \think\response\Json
     */
    public function switchStatus($id): \think\response\Json
    {
        $ids = Utils::stringToArrayBy($id);

        foreach ($ids as $_id) {

            $user = Db::table('s_user')->where('user_id', $_id)->find();
            if ($user['is_enabled'] == 1) {
                $user = Db::table('s_user')->where('user_id', $_id)->update(['is_enabled' => 0]);
            } else {
                $user = Db::table('s_user')->where('user_id', $_id)->update(['is_enabled' => 1]);
            }
        }

        return XhyResponse::success([], '操作成功');
    }

    /**
     *
     * @time 2019年12月11日
     * @param Request $request
     * @param Roles $roles
     * @return \think\response\Json
     */
    public function getRoles(Request $request, Roles $roles): \think\response\Json
    {
        $roles = Tree::done($roles->getList());

        $roleIds = [];
        if ($request->param('uid')) {
            $userHasRoles = $this->user->findBy($request->param('uid'))->getRoles();
            foreach ($userHasRoles as $role) {
                $roleIds[] = $role->pivot->role_id;
            }
        }

        return XhyResponse::success([
            'roles' => $roles,
            'hasRoles' => $roleIds,
        ]);
    }

    /**
     *
     * @time 2019年12月04日
     * @param $id
     * @param UpdateRequest $request
     * @return \think\response\Json
     */
    public function updatePassword($id, Request $request)
    {
        $isSuccess = false;
        $rule = [
            'password|密码' => 'require|min:6|max:24',
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg, $request->param());
        if ($paramError != "") {
            return $this->fail($paramError);
        }
        try {
            Db::startTrans();
            //获取修改前的记录
          /*  $before = Db::table("s_user_in_role")->where("user_id", $id)->find();
            if ($before["user_id"] == "") {
                return $this->fail("用户不存在");
            }*/
            //保存s_user信息
            $upDataUser = Db::name('s_user')
                ->where('user_id', $id)
                ->update(['login_password' => password_hash($request->param()['password'], PASSWORD_DEFAULT)]);
            if ($upDataUser === false) {
                Db::rollback();
                return $this->fail("更新失败");
            }
            $logTitle = "修改用户id{$id}信息";
            $this->logInfo($logTitle, '');

            Db::commit();
            return XhyResponse::success('','修改密码成功');
        } catch (\Exception $e) {

            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();
            $this->logError("修改用户出错", $e);
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
     *
     * @time 2019年12月04日
     * @param UpdateRequest $request
     * @return \think\response\Json
     */
    public function modifyPassword(Request $request)
    {
        $isSuccess = false;
        $rule = [
            // 'password|密码' => 'require|min:6|max:24',
            'old_password' => 'require',
            'new_password' => 'require',
            'new_password2' => 'require|confirm:new_password',
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg, $request->param());
        if ($paramError != "") {
            return $this->fail($paramError);
        }

        // 校验原密码
        // $old_password = Db::name('s_user')
        //     ->where('user_id', $this->userID)
        //     ->value('login_password');
        // if($old_password != password_hash($request->param()['old_password'], PASSWORD_DEFAULT)){
        //     return $this->fail('原密码校验不通过');
        // }

        try {
            $user = Db::name('s_user')
                ->where('user_id', $this->userID)
                ->find();
            $pass = $user['login_password'];
            if (!password_verify($request->param()['old_password'], $pass)) {
                return $this->fail("原密码输入错误！");
            }
            Db::startTrans();
            //保存s_user信息
            $upDataUser = Db::name('s_user')
                ->where('user_id', $this->userID)
                ->update([
                    'login_password' => password_hash($request->param()['new_password'], PASSWORD_DEFAULT)
                ]);
            if ($upDataUser === false) {
                Db::rollback();
                return $this->fail("更新失败");
            }
            $logTitle = "修改密码id{$this->userID}";
            $this->logInfo($logTitle, '');

            Db::commit();
            return XhyResponse::success();
        } catch (\Exception $e) {

            //region 异常 (回滚事务/记录日志/返回错误)
            Db::rollback();
            $this->logError("修改密码出错", $e);
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
     *
     * @time 2020年5月25日
     * @param UpdateRequest $request
     * @return \think\response\Json
     */
    public function updateProfile(Request $request)
    {
        $isSuccess = false;
        $rule = [
            'user_name' => 'require|min:3',
        ];
        $msg = [];
        $paramError = $this->checkParams($rule, $msg, $request->param());
        if ($paramError != "") {
            return $this->fail($paramError);
        }
        try {
            //保存s_user信息
            $upDataUser = Db::name('s_user')
                ->where('user_id', $this->userID)
                ->update([
                    'user_name' => $request->param()['user_name'],
                ]);
            if ($upDataUser === false) {
                return $this->fail("更新失败");
            }
            $logTitle = "修改id{$this->userID}";
            $this->logInfo($logTitle, '');
            return XhyResponse::success();
        } catch (\Exception $e) {
            //region 异常 (回滚事务/记录日志/返回错误)
            $this->logError("修改密码出错", $e);
            return $this->fail($e->getMessage());
        } finally {
            //region 结束
            if ($isSuccess == false) {
                Db::rollback();
            }
            //endregion
        }
    }

    /**
     *
     * @time 2020年5月25日
     * @param UpdateRequest $request
     * @return \think\response\Json
     */
    public function getProfile(Request $request){
        $user = Db::name('s_user')->where('user_id', $this->auth->user()->user_id)->find();
        if (!$user) {
            return $this->fail("用户查询失败");
        }
        return XhyResponse::success($user);
    }
}
