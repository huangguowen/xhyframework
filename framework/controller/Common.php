<?php

/**
 * name:学生管理
 * date:2020-05-01
 * user:linxr
 * note:xxxxx
 * asdfasdfasdfasdf
 */

namespace xhyadmin\framework\controller;

use catcher\Utils;
use think\facade\Db;
use app\Request;
use xhyadmin\framework\request\LoginRequest;
use xhyadmin\framework\model\Users;
use catcher\base\CatchController;
use catcher\CatchAuth;
use catcher\CatchCacheKeys;
use catcher\CatchResponse;
use catcher\exceptions\LoginFailedException;
use think\facade\Cache;

class Common extends CatchController
{
     /**
   * 登陆
   *
   * @time 2019年11月28日
   * @param LoginRequest $request
   * @param CatchAuth $auth
   * @return bool|string
   */
  public function login(LoginRequest $request, CatchAuth $auth)
  {
      $params = $request->param();

      $token = $auth->attempt($params);

      $user = $auth->user();

      if ($user->is_enabled != Users::ENABLE) {
        throw new LoginFailedException('该用户已被禁用');
      }
      $roles = Db::table('s_user_in_role')->where('user_id', $user->user_id)->select()->toArray();
      //还未分配角色
      if (empty($roles)) {
          return CatchResponse::fail('请向管理员分配此账号角色');
      }
      $title = $user->login_name.'已经登录';
      $logDetail ='';
      // 2020-5-27 日志修改  yjj
      Utils::writeLog("info", $this->Request, $user->user_id, $user->user_name, $title, $logDetail);

      return $token ? CatchResponse::success([
          'token' => $token,
      ], '登录成功') : CatchResponse::success('', '登录失败');
  }

/**
 * 登出
 *
 * @time 2019年11月28日
 * @param CatchAuth $auth
 * @return \think\response\Json
 */
  public function logout(CatchAuth $auth, Request $request): \think\response\Json
  {
      $user = $request->user();
      $title = $user->login_name.'已经登出';
      $this->logInfo($title, '');
      if ($auth->logout()) {
          Cache::delete(CatchCacheKeys::USER_PERMISSIONS . $user->user_id);
          return CatchResponse::success();
      }

      return CatchResponse::fail('登出失败');
  }





    /**
     * 获取用户信息
     *
     * @time 2020年01月07日
     * @param CatchAuth $auth
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @return \think\response\Json
     */
    public function info(CatchAuth $auth)
    {
        $user = $auth->user();
        $sql = '
            (
                select 
                    menu_id as id ,
                    component_name as component,
                    is_expand,
                    small_icon as icon,
                    1 as keepAlive,
                    menu_parent_id as parent_id,
                    "" as redirect,
                    link_page as route,
                    menu_name as title, 
                    1 as type,
                    "" as permisson_name
                from 
                    v_user_menu_permisson 
                where user_id="' . $user->user_id . '"
                order by sort_number asc
            )
                union all
                    
                select 
                    menu_function_id as id ,
                    component_name as component,
                    2 as is_expand,
                    "home" as icon,
                    1 as keepAlive,
                    menu_id as parent_id,
                    "" as redirect,
                    route_page as route,
                    function_name as title, 
                    2 as type,
                    permission_id as permisson_name
                from 
                    v_user_function_permisson  
                where user_id="' . $user->user_id . '"
        ';
        $roles = Db::table('s_user_in_role')->where('user_id', $user->user_id)->select()->toArray();
        $permissions = Db::query($sql);
        $user->permissions = $permissions;
        $user->username = $user->user_name; //兼容前端显示名称
        $user->status = 1;
        $user->roles = $roles;

        // 缓存用户权限
        Cache::set(CatchCacheKeys::USER_PERMISSIONS . $user->user_id, array_column($permissions, 'id'));
        return CatchResponse::success($user);
    }
}
