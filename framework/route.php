<?php

use think\Route;

$router = app(Route::class);
//其他中间件
$routeMiddleware = config('xhy.middleware');

//公共模块
$router->resource('common', '\xhyadminframework\controller\Common');
$router->get('user/info', '\xhyadminframework\controller\Common@info');
$router->put('user/switch/status/<id>', '\xhyadminframework\controller\User@switchStatus');


//主页仪表盘
$router->resource('dashboard', '\xhyadminframework\controller\Dashboard');

//菜单模块
$router->get('menu', '\xhyadminframework\controller\Menu@index');
$router->post('menu', '\xhyadminframework\controller\Menu@save');
$router->get('menu/<id>', '\xhyadminframework\controller\Menu@read');
$router->put('menu/<id>', '\xhyadminframework\controller\Menu@update');
$router->delete('menu/<id>', '\xhyadminframework\controller\Menu@delete');
$router->post('menu/sort', '\xhyadminframework\controller\Menu@sort');
$router->get('menu/function/', '\xhyadminframework\controller\Menu@functionList');
$router->post('menu/functionAdd', '\xhyadminframework\controller\Menu@functionAdd');
$router->post('menu/functionDel', '\xhyadminframework\controller\Menu@functionDel');
$router->post('menu/functionUpdate/<id>', '\xhyadminframework\controller\Menu@functionUpdate');
$router->post('menu/sortExchange', '\xhyadminframework\controller\Menu@sortExchange');
$router->put('menu/switch/<id>', '\xhyadminframework\controller\Menu@switch');

//角色模块
$router->get('role', '\xhyadminframework\controller\Role@index');
$router->post('role', '\xhyadminframework\controller\Role@save');
$router->get('role/<id>', '\xhyadminframework\controller\Role@read');
$router->put('role/<id>', '\xhyadminframework\controller\Role@update');
$router->delete('role/<id>', '\xhyadminframework\controller\Role@delete');
$router->get('role/getAllMenuData', '\xhyadminframework\controller\Role@getAllMenuData');
$router->get('role/getAssignedMenuData/<id>', '\xhyadminframework\controller\Role@getAssignedMenuData');
$router->post('role/saveAssignMenu', '\xhyadminframework\controller\Role@saveAssignMenu');
$router->post('role/swtichStatus', '\xhyadminframework\controller\Role@swtichStatus');

//用户模块
$router->get('user', '\xhyadminframework\controller\User@index')->middleware($routeMiddleware);
$router->post('user', '\xhyadminframework\controller\User@save')->middleware($routeMiddleware);
$router->get('user/getRolesByUserId', '\xhyadminframework\controller\User@getRolesByUserId');
$router->get('user/<id>', '\xhyadminframework\controller\User@read')->middleware($routeMiddleware);
$router->put('user/<id>', '\xhyadminframework\controller\User@update')->middleware($routeMiddleware);
$router->delete('user/<id>', '\xhyadminframework\controller\User@delete')->middleware($routeMiddleware);
$router->post('user/updatePassword/<id>', '\xhyadminframework\controller\User@updatePassword');

$router->post('basic/modifyPassword', '\xhyadminframework\controller\User@modifyPassword');
$router->post('basic/updateProfile', '\xhyadminframework\controller\User@updateProfile');
$router->get('basic/getProfile', '\xhyadminframework\controller\User@getProfile');

//系统日志
$router->get('log', '\xhyadminframework\controller\Log@index');
$router->get('log/<id>', '\xhyadminframework\controller\Log@read');
$router->get('log/category', '\xhyadminframework\controller\Log@getCategoryList');


//单位模块
$router->get('unit', '\xhyadminframework\controller\Unit@index');
$router->post('unit', '\xhyadminframework\controller\Unit@save');
$router->get('unit/<id>', '\xhyadminframework\controller\Unit@read');
$router->put('unit/<id>', '\xhyadminframework\controller\Unit@update');
$router->delete('unit/<id>', '\xhyadminframework\controller\Unit@delete');

//字典模块
$router->get('dict', '\xhyadminframework\controller\Dict@index');
$router->post('dict', '\xhyadminframework\controller\Dict@save');
$router->get('dict/<id>', '\xhyadminframework\controller\Dict@read');
$router->put('dict/<id>', '\xhyadminframework\controller\Dict@update');
$router->delete('dict/<id>', '\xhyadminframework\controller\Dict@delete');
$router->post('dict/sort', '\xhyadminframework\controller\Dict@sort');
$router->get('dict/list', '\xhyadminframework\controller\Dict@dictList');
$router->put('dict/listSort', '\xhyadminframework\controller\Dict@listSort');
$router->put('dict/switch/status/<id>', '\xhyadminframework\controller\Dict@switchStatus');

//系统参数管理
$router->get('config', '\xhyadminframework\controller\Config@index');
$router->post('config/save', '\xhyadminframework\controller\Config@save');
$router->get('config/group', '\xhyadminframework\controller\Config@getGroup');
$router->post('config/update', '\xhyadminframework\controller\Config@add');
$router->get('config/read', '\xhyadminframework\controller\Config@read');
$router->get('config/delete', '\xhyadminframework\controller\Config@delete');