<?php
/**
 * 处理数据库配置参数
 * Created by PhpStorm.
 * User: lampzww
 * Date: 2020/6/11
 * Time: 16:42
 */

namespace xhyadminframework\middleware;


use think\facade\Db;

class Config
{
    /**
     * @param $request
     * @param \Closure $next
     * @return mixed|\think\response\Redirect
     * @author: lampzww
     * @Date: 16:43  2020/6/11
     */
    public function handle($request, \Closure $next)
    {
        //读取数据库基本参数
        $configs 	= cache("DB_CONFIG_DATA");

        if(!$configs){
            $data    =   Db::name('s_config')->column('gname,name,value');
            $configs    =   [];
            foreach ($data as $item){
                if($item['gname']){
                    $configs[$item['gname']][$item['name']] = unserialize($item['value']) ? unserialize($item['value']) : '';
                }else{
                    $configs[$item['name']] =   unserialize($item['value']) ? unserialize($item['value']) : '';
                }
            }
            cache("DB_CONFIG_DATA", $configs);
        }

        foreach ($configs as $key=>$value){
            \think\facade\Config::set($value,$key);
        }
        return $next($request);
    }
}
