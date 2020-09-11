<?php
/**
 * name: 系统服务
 * date: 2020-09-019
 * user: 黄国文
 */
namespace xhyadminframework;
use think\exception\Handle;
use think\facade\Validate;
use think\Service;
use xhyadminframework\exceptions\XhyException;
use xhyadminframework\XhyQuery;

class XhyAdminService extends Service
{
    /**
     * notes: 注册通用命令
     * author: 黄国文
     * Date: 2020/9/8 0009
     * Time: 下午 3:24
     */
    public function boot()
    {
        //方法是在所有的系统服务注册完成之后调用，用于定义启动某个系统服务之前需要做的操作
    }

    /**
     * notes: 注册通用命令
     * author: 黄国文
     * Date: 2020/9/8 0009
     * Time: 下午 4:24
     */
    public function register()
    {
        $this->registerCommands();
        $this->registerValidates();
        $this->registerMiddleWares();
        $this->registerEvents();
        $this->registerQuery();
        $this->registerExceptionHandle();
    }

    /**
     * notes: 注册通用命令
     * author: 黄国文
     * Date: 2020/9/8 0009
     * Time: 下午 4:24
     */
    protected function registerCommands(): void
    {
        $commands = config('xhy.command');
        $this->commands([
            $commands
        ]);
    }

    /**
     * notes: 注册验证器
     * author: 黄国文
     * Date: 2020/9/9 0009
     * Time: 下午 2:20
     */
    protected function registerValidates(): void
    {
        $validates = config('xhy.validates');

        Validate::maker(function($validate) use ($validates) {
            foreach ($validates as $vali) {
                $vali = app()->make($vali);
                $validate->extend($vali->type(), [$vali, 'verify'], $vali->message());
            }
        });
    }

    /**
     * notes: 注册中间件
     * author: 黄国文
     * Date: 2020/9/9 0009
     * Time: 下午 2:24
     */
    protected function registerMiddleWares(): void
    {
//        $this->app->middleware->add(config('xhy.middleware'));
    }

    /**
     * notes: 注册监听者
     * author: 黄国文
     * Date: 2020/9/9 0009
     * Time: 下午 2:25
     */
    protected function registerEvents(): void
    {
        $this->app->event->listenEvents(config('xhy.events'));
    }

    /**
     * notes: 注册 query
     * author: 黄国文
     * Date: 2020/9/9 0009
     * Time: 下午 2:25
     */
    protected function registerQuery(): void
    {
        $connections = $this->app->config->get('database.connections');

        $connections['mysql']['query'] = XhyQuery::class;

        $this->app->config->set([
            'connections' => $connections
        ], 'database');
    }

    /**
     * notes: 注册 异常类
     * author: 黄国文
     * Date: 2020/9/9 0009
     * Time: 下午 3:25
     */
    protected function registerExceptionHandle(): void
    {
        $this->app->bind(Handle::class, XhyExceptionHandle::class);
    }
}
