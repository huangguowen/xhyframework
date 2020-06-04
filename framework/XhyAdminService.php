<?php
namespace xhyadminframework;
use think\exception\Handle;
use think\facade\Validate;
use think\Service;
use xhyadminframework\XhyQuery;

class XhyAdminService extends Service
{
    /**
     *
     * @time 2019年11月29日
     * @return void
     */
    public function boot()
    {
    }

    /**
     * register
     *
     * @author JaguarJack
     * @email njphper@gmail.com
     * @time 2020/1/30
     * @return void
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
     *
     * @time 2019年12月13日
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([

        ]);
    }
    /**
     *
     * @time 2019年12月07日
     * @return void
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
     *
     * @time 2019年12月12日
     * @return void
     */
    protected function registerMiddleWares(): void
    {
        // todo
    }

    /**
     * 注册监听者
     *
     * @time 2019年12月12日
     * @return void
     */
    protected function registerEvents(): void
    {
        $this->app->event->listenEvents(config('xhy.events'));
    }

    /**
     * register query
     *
     * @time 2020年02月20日
     * @return void
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
     * register exception
     *
     * @time 2020年02月20日
     * @return void
     */
    protected function registerExceptionHandle(): void
    {
        $this->app->bind(Handle::class, CatchExceptionHandle::class);
    }
}
