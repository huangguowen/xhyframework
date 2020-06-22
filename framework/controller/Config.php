<?php
/**
 * 数据库配置参数管理
 * Created by PhpStorm.
 * User: lampzww
 * Date: 2020/6/10
 * Time: 16:11
 */

namespace xhyadminframework\controller;


use app\Request;
use base\XhyAuth as Auth;
use think\facade\Env;
use think\response\Json;
use xhyadminframework\base\XhyController;
use think\facade\Db;
use xhyadminframework\model\Users;

class Config extends XhyController
{
    protected $group;//参数分组

    public function __construct(Users $user, Auth $auth, Request $request)
    {
        parent::__construct($user, $auth, $request);
        $this->group    =   config('xhy.db_config_group');
    }

    /**
     * 参数列表
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: lampzww
     * @Date: 16:18  2020/6/11
     */
    public function index()
    {
        $groupid = input('groupid',1,'intval');
        $config = Db::name('s_config')->where(['groupid'=>$groupid])->order("sort","asc")->select()->toArray();
        if($config){
            foreach ($config as &$item){
                $item['value']    = unserialize($item['value']);
                $item['data']     = unserialize($item['data']) ? unserialize($item['data']) : '';
            }
        }
        return $this->success(['list'=>$config,'debug'=>Env::get('APP_DEBUG') ? true : false],config());
    }

    /**
     * 保存设置
     * @return Json
     * @author: lampzww
     * @Date: 14:04  2020/6/11
     */
    public function save(): Json
    {
        $groupid = input('groupid',1,'intval');
        self::saveConfig($groupid);
        return $this->success([],'保存成功');
    }

    /**
     *获取参数分组
     * @return \think\response\Json
     * @author: lampzww
     * @Date: 9:56  2020/6/11
     */
    public function getGroup(): Json
    {
        return $this->success($this->group);
    }

    /**
     * 保存参数设置
     * @param $groupid
     * @throws \think\db\exception\DbException
     * @author: lampzww
     * @Date: 16:18  2020/6/11
     */
    private function saveConfig($groupid)
    {
        $config = Db::name('s_config')->where(['groupid'=>$groupid])->column("name,gname");
        if($config)foreach ($config as $item){
            $val = input($item['name'],"","trim");
            Db::name('s_config')->where(['name'=> $item['name'],'groupid'=>$groupid,'gname'=>$item['gname']])->update(['value'=>serialize($val)]);
        }
        cache("DB_CONFIG_DATA",null); //清除缓存
    }

    /**
     * 添加/修改参数
     * @return Json
     * @throws \think\db\exception\DbException
     * @author: lampzww
     * @Date: 16:18  2020/6/11
     */
    public function add()
    {

        $rule = [
            'groupid'       => 'require',
            'name'          => 'require',
            'label'         => 'require',
            'gname'         => 'require',
        ];
        $message = [
            'groupid.require'       => "请选择参数分组",
            'name.require'          => "请填写参数名",
            'gname.require'         => "请填写组件名",
        ];

        $paramError = $this->checkParams($rule, $message);
        if ($paramError != "") {
            return $this->fail($paramError);
        }
        $groupid     = input('groupid',1,"intval");
        $config_name = input("ename","","trim");
        $name    = input("name", "", "trim");
        $label   = input("label", "", "trim");
        $type    = input("type", "", "trim");
        $data    = input("data","","trim");
        $about	= input("about","","trim");
        $uridata = input('uridata',"",'trim');
        $gname = input('gname',"",'trim');
        $value = input('value',"",'trim');

        if($config_name != $name && Db::name('s_config')->where(['name'=>$name,'groupid'=>$groupid,'gname'=>$gname])->count()>0)   return $this->fail("参数名[{$name}]已存在");

        if(in_array($type, ["select","checkbox","radio"])){
            if($data == ""){
                return $this->fail("请填写可选参数!");
            }else{
                $data = explode("\n", $data);
                $data = array_map("trim", $data);
                if(empty($data))return $this->fail("111请填写可选参数!");
                $option_data = [];
                foreach ($data as $val){
                    $_data = explode("=", $val);
                    if(count($_data) == 2)
                        $option_data[] = ['value'=> $_data[0], 'label' => $_data[1]];
                }
                if(empty($option_data))return $this->fail("请填写可选参数!");
                $data           = $option_data;
            }
        }else{
            $data = "";
        }
        $sort = input("sort",0,"intval");
        if($config_name){
            $config_data = [
                'name'  => $name,
                'label' => $label,
                'type'  => $type,
                'data'  => serialize($data),
                'groupid'   => $groupid,
                'about'	=> $about,
                'sort'=>$sort,
                'uridata'  => $uridata,
                'gname'  => $gname,//组件名
            ];
            $result = Db::name('s_config')->where(['name'=>$config_name,'groupid'=>$groupid,'gname'=>$gname])->update($config_data);
        }else{
            if($sort == 0){
                $sort = Db::name('s_config')->where("groupid = ".$groupid)->max("sort");
                $sort ++ ;
            }
            $config_data = [
                'name'  => $name,
                'label' => $label,
                'type'  => $type,
                'data'  => serialize($data),
                'groupid'   => $groupid,
                'about'		=> $about,
                'sort' => $sort,
                'value'=>   $value ? serialize($value) : '',
                'uridata'=>   '',//附加参数 #TODO
                'gname'  => $gname,//组件名
            ];
            $result = Db::name('s_config')->insert($config_data);
        }
        if ($result === false){
            return $this->fail("操作失败");
        }else{
            return $this->success("操作成功");
        }
    }

    /**
     * 删除参数
     * @return Json
     * @throws \think\db\exception\DbException
     * @author: lampzww
     * @Date: 16:17  2020/6/11
     */
    public function delete()
    {
        $id         =   input('id','','intval');
        $map        =   ['id'=>$id];
        $result     =   Db::name('s_config')->where($map)->delete();
        if ($result === false){
            return $this->fail('操作失败!');
        }else{
            return $this->success([],'操作成功!');
        }
    }

    /**
     * 读取配置详情
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: lampzww
     * @Date: 16:17  2020/6/11
     */
    public function read()
    {
        $name   =   input('name','','trim');
        $data = Db::name('s_config')->where(['name'=>$name])->find();
        if($data){
            unset($data['value']);
            $data['data'] = unserialize($data['data']);
            $data['groupid']    =   (string)$data['groupid'];
            $_data = "";
            if(is_array($data['data'])){
                foreach ($data['data'] as $val){
                    $_data .="{$val['value']}={$val['label']}\n";
                }
            }
            $data['data'] = trim($_data);
        }
        return $this->success($data);
    }


}
