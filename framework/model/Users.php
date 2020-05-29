<?php

/*
name:用户模型类
user:黄国文
date:
*/

namespace xhyadmin\framework\model;

use catcher\base\CatchModel;
use think\facade\Db;

class Users extends CatchModel
{

    protected $name = 's_user';

    protected $field = [
        'user_id', //
        'login_name', //
    ];

    /**
     * @param $userId
     * @return array
     * */
    public function getRole($userId): array
    {
        $roles = Db::table('s_user_in_role')->where('user_id', $userId)->select()->toArray();
        return $roles;
    }

    /**
     * @param $roleIds
     * @return array
     * */
    public function getPermissionIds($roles): array
    {
        $roles = array_column($roles, 'role_id');
        $permissionIds = Db::table('s_role_menu_permission')->whereIn('role_id', $roles)->select()->toArray();
        return $permissionIds;
    }

    /**
     * @param $permissionIds
     * @return array
     * */
    public function getMenuIds($permissionIds): array
    {
        $menuIds = array_column($permissionIds, 'menu_id');
        $menuIds = Db::table('s_menu')
            ->field('menu_name as title, menu_id as id, 
            component_name as component, 
            2 as hide_children_in_menu,
            small_icon as icon,
            1 as keepAlive,
             menu_parent_id as parent_id,
             "" as redirect,
             link_page as route,
             menu_name as title, 
             1 as type')
            ->whereIn('menu_id', $menuIds)
            ->where("is_enabled", "1")
            ->select()
            ->toArray();
        return $menuIds;
    }

    /**
     * 用户列表
     *
     * @time 2019年12月08日
     * @throws \think\db\exception\DbException
     * @return \think\Paginator
     */
    public function getList($param): \think\Paginator
    {
        return $this
            ->catchSearch()
            ->order('created', 'desc')
            ->paginate();
    }
}
