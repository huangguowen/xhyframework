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
        $configs 	=   config();
        //读取数据库基本参数
        if(cache("DB_CONFIG_DATA")){
            $data   =   cache("DB_CONFIG_DATA");
        }else{
            $data    =   Db::name('s_config')->column('gname,name,value,type');
            cache("DB_CONFIG_DATA", $data);
        }
        //处理参数
        foreach ($data as $item){
            if($item['type']    ==  'line'){
                continue;
            }
            $keys   =   explode('.',$item['gname']);//层级  最多4层

            if(isset($keys[3])){
                $configs[$keys[0]][$keys[1]][$keys[2]][$keys[3]][$item['name']]    =   unserialize($item['value']) ? unserialize($item['value']) : '';
            }else{
                if(isset($keys[2])){
                    $configs[$keys[0]][$keys[1]][$keys[2]][$item['name']]    =   unserialize($item['value']) ? unserialize($item['value']) : '';
                }else{
                    if(isset($keys[1])){
                        $configs[$keys[0]][$keys[1]][$item['name']]    =   unserialize($item['value']) ? unserialize($item['value']) : '';
                    }else{
                        $configs[$keys[0]][$item['name']]    =   unserialize($item['value']) ? unserialize($item['value']) : '';
                    }
                }
            }
        }
        //重新设置参数
        foreach ($configs as $key=>$value){
            \think\facade\Config::set($value,$key);
        }
        return $next($request);
    }
}
