<?php

use think\Route;

$router = app(Route::class);
//其他中间件
$routeMiddleware = config('xhy.middleware');

//公共模块
$router->resource('common', '\xhyadminframework\controller\Common');
$router->get('user/info', '\xhyadminframework\controller\Common@info');
$router->put('user/switch/status/<id>', '\xhyadminframework\controller\User@switchStatus');
$router->post('common/logout', '\xhyadminframework\controller\Common@logout');


//主页仪表盘
$router->resource('dashboard', '\xhyadminframework\controller\Dashboard');

//菜单模块
$router->resource('menu', '\xhyadminframework\controller\Menu');
$router->post('menu/sort', '\xhyadminframework\controller\Menu@sort');
$router->get('menu/function/', '\xhyadminframework\controller\Menu@functionList');
$router->post('menu/functionAdd', '\xhyadminframework\controller\Menu@functionAdd');
$router->post('menu/functionDel', '\xhyadminframework\controller\Menu@functionDel');
$router->post('menu/functionUpdate/<id>', '\xhyadminframework\controller\Menu@functionUpdate');
$router->post('menu/sortExchange', '\xhyadminframework\controller\Menu@sortExchange');
$router->put('menu/switch/<id>', '\xhyadminframework\controller\Menu@switch');

//角色模块
$router->resource('role', '\xhyadminframework\controller\Role');
$router->post('role/getAllMenuData', '\xhyadminframework\controller\Role@getAllMenuData');
$router->post('role/getAssignedMenuData', '\xhyadminframework\controller\Role@getAssignedMenuData');
$router->resource('role/saveAssignMenu', '\xhyadminframework\controller\Role@saveAssignMenu');
$router->resource('role/swtichStatus', '\xhyadminframework\controller\Role@swtichStatus');

//用户模块
$router->resource('user', '\xhyadminframework\controller\User')->middleware($routeMiddleware);
$router->get('user/getRolesByUserId', '\xhyadminframework\controller\User@getRolesByUserId');
$router->post('user/updatePassword/<id>', '\xhyadminframework\controller\User@updatePassword');

$router->post('basic/modifyPassword', '\xhyadminframework\controller\User@modifyPassword');
$router->post('basic/updateProfile', '\xhyadminframework\controller\User@updateProfile');
$router->get('basic/getProfile', '\xhyadminframework\controller\User@getProfile');

//系统日志
$router->resource('log', '\xhyadminframework\controller\Log');
$router->get('log/category', '\xhyadminframework\controller\Log@getCategoryList');


//单位模块
$router->resource('unit', '\xhyadminframework\controller\Unit');

//字典模块
$router->resource('dict', '\xhyadminframework\controller\Dict');
$router->post('dict/sort', '\xhyadminframework\controller\Dict@sort');
$router->get('dict/list', '\xhyadminframework\controller\Dict@dictList');
$router->put('dict/listSort', '\xhyadminframework\controller\Dict@listSort');
$router->put('dict/switch/status/<id>', '\xhyadminframework\controller\Dict@switchStatus');

