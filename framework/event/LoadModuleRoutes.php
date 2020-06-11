<?php
declare (strict_types = 1);

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
            $routes = array_merge($routes, [app()->getRootPath().\think\facade\Env::get('appconfig.APPNAME').'/route.php']);
        }
        $routeMiddleware = config('xhy.route_middleware');

        if ($domain) {
            $router->domain($domain, function () use ($router, $routes) {
                foreach ([$routes[0]] as $route) {
                    include $route;
                }
            })->middleware($routeMiddleware);
        } else {
            $router->group(function () use ($router, $routes) {
                foreach ($routes as $route) {
                    include $route;
                }
            })->middleware($routeMiddleware);
        }

        //framework的未登录
        include $routes[1];
        // 新项目加载登录
        if (!empty(\think\facade\Env::get('appconfig.APPNAME'))) {
            //不需要登录的模块
            include app()->getRootPath().\think\facade\Env::get('appconfig.APPNAME').'/noMiddlewareRoute.php';
        }


    }
}
