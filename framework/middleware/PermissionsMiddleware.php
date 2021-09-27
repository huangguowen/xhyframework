<?php

namespace xhyadminframework\middleware;

use app\Request;
use xhyadminframework\model\Menu;
use xhyadminframework\XhyCacheKeys;
use xhyadminframework\Code;
use xhyadminframework\exceptions\PermissionForbiddenException;
use think\facade\Cache;
use think\helper\Str;

class PermissionsMiddleware
{
    /**
     *
     * @time 2019年12月12日
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws PermissionForbiddenException
     */
    public function handle(Request $request, \Closure $next)
    {

        $rule = $request->rule()->getName();

        if (!$rule) {
            return $next($request);
        }
        // 模块忽略
        [$controller, $action] = $this->parseRule($rule);

        // toad
        if (in_array($controller, $this->ignoreModule())) {

            return $next($request);
        }
        // 用户未登录
        $user = $request->user();
        if (!$user) {
            throw new PermissionForbiddenException('Login is invalid', Code::LOST_LOGIN);
        }
        //dd($this->parseRule($rule));
        $permission = $this->getPermission($controller, $action);

        // 记录操作
        //        $this->operateEvent($request->user()->user_id, $permission);
        // 超级管理员
        if ($request->user()->user_id === config('xhy.permissions.super_admin_id')) {
            return $next($request);
        }

        // Get 请求
        if ($request->isGet() && config('xhy.permissions.is_allow_get')) {
            return $next($request);
        }
        if(Cache::get(XhyCacheKeys::USER_PERMISSIONS . $user->user_id)){
            $checkPermission =  Cache::get(XhyCacheKeys::USER_PERMISSIONS . $user->user_id);
        }else{
            $checkPermission = $permission;
        }
        if (!$permission) {
            throw new PermissionForbiddenException();
        }
        if (!in_array($permission['menu_id'], $checkPermission)) {
            throw new PermissionForbiddenException();
        }

        return $next($request);
    }

    /**
     * 解析规则
     *
     * @time 2020年04月16日
     * @param $rule
     * @return array
     */
    protected function parseRule($rule)
    {
        $controller_action = @explode('\\', $rule);

        $last = $controller_action[count($controller_action) -1];

        @[$controller, $action] = explode('@', $last);
        if ([$controller, $action][1] == '') {
            [$controller, $action] = explode('/', $last);
        }


        return [$controller, $action];
    }


    /**
     *
     * @time 2019年12月14日
     * @param $module
     * @param $controllerName
     * @param $action
     * @param $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @return array|bool|\think\Model|null
     */
    protected function getPermission($controllerName, $action)
    {
        $permissionMark = sprintf('%s@%s', $controllerName, $action);
        $menu = Menu::where('permission_mark', $permissionMark)->find();
        if (!$menu) {
            $menu = \think\facade\Db::table('s_menu_function')->field('menu_function_id as menu_id')->where('permission_id', $permissionMark)->find();
        } else {
            $menu = $menu->toArray();
        }
        return $menu;
    }

    /**
     * 忽略模块
     *
     * @time 2020年04月16日
     * @return array
     */
    protected function ignoreModule()
    {
        return ['login'];
    }


}