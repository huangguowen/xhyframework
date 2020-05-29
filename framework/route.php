<?php
//其他中间件
$routeMiddleware = config('xhy.middleware');

//公共模块
$router->resource('common', '\xhyadmin\framework\controller\Common');
$router->get('user/info', '\xhyadmin\framework\controller\Common@info');
$router->put('user/switch/status/<id>', '\xhyadmin\framework\controller\User@switchStatus');
$router->post('common/logout', '\xhyadmin\framework\controller\Common@logout');


//主页仪表盘
$router->resource('dashboard', '\xhyadmin\framework\controller\Dashboard');

//菜单模块
$router->resource('menu', '\xhyadmin\framework\controller\Menu');
$router->post('menu/sort', '\xhyadmin\framework\controller\Menu@sort');
$router->get('menu/function/', '\xhyadmin\framework\controller\Menu@functionList');
$router->post('menu/functionAdd', '\xhyadmin\framework\controller\Menu@functionAdd');
$router->post('menu/functionDel', '\xhyadmin\framework\controller\Menu@functionDel');
$router->post('menu/functionUpdate/<id>', '\xhyadmin\framework\controller\Menu@functionUpdate');
$router->post('menu/sortExchange', '\xhyadmin\framework\controller\Menu@sortExchange');
$router->put('menu/switch/<id>', '\xhyadmin\framework\controller\Menu@switch');

//角色模块
$router->resource('role', '\xhyadmin\framework\controller\Role');
$router->post('role/getAllMenuData', '\xhyadmin\framework\controller\Role@getAllMenuData');
$router->post('role/getAssignedMenuData', '\xhyadmin\framework\controller\Role@getAssignedMenuData');
$router->resource('role/saveAssignMenu', '\xhyadmin\framework\controller\Role@saveAssignMenu');
$router->resource('role/swtichStatus', '\xhyadmin\framework\controller\Role@swtichStatus');

//用户模块
$router->resource('user', '\xhyadmin\framework\controller\User')->middleware($routeMiddleware);
$router->get('user/getRolesByUserId', '\xhyadmin\framework\controller\User@getRolesByUserId');
$router->post('user/updatePassword/<id>', '\xhyadmin\framework\controller\User@updatePassword');

$router->post('basic/modifyPassword', '\xhyadmin\framework\controller\User@modifyPassword');
$router->post('basic/updateProfile', '\xhyadmin\framework\controller\User@updateProfile');
$router->get('basic/getProfile', '\xhyadmin\framework\controller\User@getProfile');

//系统日志
$router->resource('log', '\xhyadmin\framework\controller\Log');
$router->get('log/category', '\xhyadmin\framework\controller\Log@getCategoryList');


//单位模块
$router->resource('unit', '\xhyadmin\framework\controller\Unit');

//字典模块
$router->resource('dict', '\xhyadmin\framework\controller\Dict');
$router->post('dict/sort', '\xhyadmin\framework\controller\Dict@sort');
$router->get('dict/list', '\xhyadmin\framework\controller\Dict@dictList');
$router->put('dict/listSort', '\xhyadmin\framework\controller\Dict@listSort');
$router->put('dict/switch/status/<id>', '\xhyadmin\framework\controller\Dict@switchStatus');

