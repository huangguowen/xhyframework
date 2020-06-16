<?php
/**
 *  文件上传处理类
 * Created by PhpStorm.
 * User: 黄国文
 * Date: 2020/6/16 0016
 * Time: 上午 11:04
 */
namespace xhyadminframework\base;

use \think\facade\Filesystem;

class XhyUpload
{
    protected $error;

    /**
     * 上传文件验证配置
     * @var array
     */
    protected $file_config = [
        'images'    =>  [
            'fileSize' => 1048576,
            'fileExt' => 'jpg,png,gif,jpeg',
            'fileMime' => 'image/jpeg,image/png,image/gif',
        ]
        #TODO 其他...
    ];
    public function __construct()
    {
        #TODO
    }

    /**
     * 文件上传
     * @return string|string[]
     * @author: lampzww
     * @Date: 9:31  2020/6/10
     */
    public function putFile()
    {
        switch (config('filesystem.default')){
            case 'local':
            case 'public':
                $type   =   request()->param('type','images','trim');//上传类型
                $field  =   request()->param('field','img', 'trim');//接收字段
                $url    =   self::local($type, $field);

                break;
            case 'qiniu'://七牛云
                return $this->qiniu();
                break;
            case 'oss'://阿里云oss
                return $this->oss();
                break;
            case 'qcloud'://腾讯云cos
                return $this->qcloud();
                break;
        }
        if($url){
            $param  =   request()->param();
            $param['url']   =   $url;
            $this->writeFileBase($param);
            return $param;
        }else{
            return  false;
        }

    }

    /**
     * 本地上传
     * @return string|string[]
     * @author: lampzww
     * @Date: 9:32  2020/6/10
     */
    public function local($type = 'images', $field = 'img', $name_rule = 'md5')
    {
        $file   =   request()->file($field);
        try {
            validate([$field=>$this->file_config[$type]])->check(request()->file());
        } catch (\Exception $e) {
            $this->error    =   $e->getError();
            return false;
        }

        $savename = Filesystem::disk('public')->putFile($type, $file, $name_rule);
        $path   =   ((int)$_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']. '/' .
            config('filesystem.disks.'.config('filesystem.default').'.url'). '/'.
            $savename;
        return str_replace('\\','/',$path);
    }

    public function qiniu()
    {
        #TODO 七牛云上传
    }

    public function oss()
    {
        #TODO 阿里云oss上传
    }

    public function qcloud()
    {
        #TODO 腾讯云cos上传
    }

    /**
     * 获取错误信息
     * @return mixed
     * @author: lampzww
     * @Date: 9:32  2020/6/10
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 事件写文件存储器
     * @author: huangguowen
     */
    public function writeFileBase($parm)
    {
        $baseEvent = config('xhy.filesystem');
        if (!empty($baseEvent)) {
            event($baseEvent, $parm);
        }
        return true;
    }
}
