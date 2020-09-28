<?php
declare (strict_types=1);

namespace xhyadminframework\event;

use xhyadminframework\XhyAdmin;
use think\Route;

class LoadModuleRoutes
{
    /**
     * 处理
     *
     * @time 2019年11月29日
     * @return void
     */
    public function handle(): void
    {
        $router = app(Route::class);

        $domain = config('xhy.domain');

        $routes = XhyAdmin::getRoutes();
        if (!empty(\think\facade\Env::get('appconfig.APPNAME'))) {
            $routes = array_merge($routes, [
                app()->getRootPath() . \think\facade\Env::get('appconfig.APPNAME') . '/route.php',
                app()->getRootPath() . \think\facade\Env::get('appconfig.APPNAME2') . '/route.php'
            ]);
        }
        $routeMiddleware = config('xhy.route_middleware');
        //config配置项
        $configMiddleware = [\xhyadminframework\middleware\Config::class];
        $router->group(function () use ($router, $routes) {
            foreach ([$routes[0], @$routes[2], @$routes[3]] as $route) {
                include $route;
            }
        })->middleware(array_merge($routeMiddleware, $configMiddleware));

        //framework的未登录
        $router->group(function () use ($router, $routes) {
            include $routes[1];
        })->middleware($configMiddleware);

        // app加载登录
        if (!empty(\think\facade\Env::get('appconfig.APPNAME'))) {
            //不需要登录的模块
            $router->group(function () use ($router) {
                include app()->getRootPath() . \think\facade\Env::get('appconfig.APPNAME') . '/noMiddlewareRoute.php';
            })->middleware($configMiddleware);
        }
        // 做游客登录需要用到 游客登录的 路由名称是 vistorRoute.php (在路由加中间件 易于管理)
        if (file_exists(app()->getRootPath() . \think\facade\Env::get('appconfig.APPNAME') . '/vistorRoute.php')) {
            $router->group(function () use ($router) {
                include app()->getRootPath() . \think\facade\Env::get('appconfig.APPNAME') . '/vistorRoute.php';
            });
        }
        // 抽离代码
        if (file_exists(app()->getRootPath() . \think\facade\Env::get('appconfig.APPNAME2') . '/noMiddlewareRoute.php')) {
            //不需要登录的模块
            $router->group(function () use ($router) {
                include app()->getRootPath() . \think\facade\Env::get('appconfig.APPNAME2') . '/noMiddlewareRoute.php';
            })->middleware($configMiddleware);
        }
    }
}
