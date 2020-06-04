<?php

namespace xhyadminframework\middleware;

use app\Request;
use xhyadminframework\model\Menu;
use xhyadminframework\CatchCacheKeys;
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
        [$module, $controller, $action] = $this->parseRule($rule);
        // toad
        if (in_array($module, $this->ignoreModule())) {

            return $next($request);
        }
        // 用户未登录
        $user = $request->user();
        if (!$user) {
            throw new PermissionForbiddenException('Login is invalid', Code::LOST_LOGIN);
        }
        //dd($this->parseRule($rule));
        $permission = $this->getPermission($module, $controller, $action);

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
        if (!$permission || !in_array($permission['menu_id'], Cache::get(CatchCacheKeys::USER_PERMISSIONS . $user->user_id), 'menu_id')) {
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
        [$controller, $action] = explode(Str::contains($rule, '@') ? '@' : '/', $rule);

        $controller = explode('\\', $controller);

        $controllerName = strtolower(array_pop($controller));

        array_pop($controller);

        $module = array_pop($controller);

        return [$module, $controllerName, $action];
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
    protected function getPermission($module, $controllerName, $action)
    {
        $permissionMark = sprintf('%s@%s', $controllerName, $action);
        $menu = Menu::where('module', $module)->where('permission_mark', $permissionMark)->find();
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
