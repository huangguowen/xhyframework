<?php

namespace xhyadminframework\model;

use xhyadminframework\base\XhyModel;
use think\facade\Db;
use base\CatchAuth;
use think\Model;

class Menu extends CatchModel
{


    protected $name = 's_menu';
    protected $createTime = 'created';
    protected $updateTime = 'modified';
    protected $pk = 'menu_id';

    /**
     * 菜单列表
     * @return array
     */
    public function getList()
    {
        $query = "select
                     menu_id as id,
                     menu_parent_id,
                     menu_name as title
                     from v_menu 
                     " . " order by sort_number  asc";
        return Db::query($query);
    }

    public function roleGetList()
    {
        $sql = '
select 
menu_id as menu_id ,
menu_id as id ,
          component_name as component,
          is_expand,
          "home" as icon,
          1 as keepAlive,
           menu_parent_id as parent_id,
           "" as redirect,
           link_page as route,
           menu_name as title,
           menu_name, 
           1 as type
           from v_menu
           
           union all
           select 

menu_function_id as menu_id ,
menu_function_id as id ,
          component_name as component,
          1 as is_expand,
          "home" as icon,
          1 as keepAlive,
           menu_id as parent_id,
           "" as redirect,
           route_page as route,
           function_name as title, 
           function_name as menu_name, 
           2 as type
           from v_menu_function
';
        $permissions = Db::query($sql);
        return $permissions;
    }

    public function roles(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(Roles::class, 'role_has_permissions', 'role_id', 'permission_id');
    }

    /**
     * 获取当前用户权限
     *
     * @time 2020年01月14日
     * @param array $permissionIds
     * @return \think\Collection
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     */
    public static function getCurrentUserPermissions(array $permissionIds): \think\Collection
    {
        return parent::whereIn('menu_id', $permissionIds)
            ->field(['permission_name as title', 'menu_id', 'menu_parent_id',
                'link_page', 'small_icon', 'component_name', 'link_page',
                'keepalive as keepAlive', 'is_expand', 'type'
            ])
            ->select();
    }

    /**
     * 交换排序yjj
     * @param $menu_info
     * @param string $op
     * @param string $desc
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateMenuSortNumber($menu_info, $op = '<', $desc = 'desc', $model)
    {
        $isSuccess = false;
        $changInfo = parent::where('menu_parent_id', $menu_info->menu_parent_id)
            ->where('sort_number', $op, $menu_info->sort_number)
            ->order('sort_number', $desc)
            ->limit(1)
            ->find();
        if ($changInfo) {
            Db::startTrans();
            try {
                parent::update(['sort_number' => $changInfo->sort_number], ['menu_id' => $menu_info->menu_id]);
                parent::update(['sort_number' => $menu_info->sort_number], ['menu_id' => $changInfo->menu_id]);
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
     * 添加列表yjj
     * @param $param
     * @return int|string
     */
    public function insertFunction($param)
    {
        return Db::name('s_menu_function')->insert($param);
    }

    /**
     * 删除列表yjj
     * @param $id
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function deleteFunction($id)
    {
        return Db::name('s_menu_function')->whereIn('menu_function_id', $id)->delete();
    }

    /**
     * 更新列表yjj
     * @param $id
     * @param $param
     * @return int
     * @throws \think\db\exception\DbException
     */

    public function updateFunction($id, $param)
    {
        return Db::name('s_menu_function')->where('menu_function_id', $id)->update($param);
    }

    public function getFunctionCount($id)
    {
        return Db::name('s_menu_function')->where('menu_id', '=', $id)->count();
    }
}
