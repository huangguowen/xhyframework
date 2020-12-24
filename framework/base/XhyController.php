<?php

namespace xhyadminframework\base;

use app\Request;
use think\Exception;
use think\Route;
use xhyadminframework\exceptions\AuthException;
use xhyadminframework\XhyAdmin;

use xhyadminframework\model\Users;
use base\XhyAuth as Auth;
use xhyadminframework\Utils;
use think\Validate;
use think\response\Json;
use xhyadminframework\Code;

abstract class XhyController
{
    public $userID;
    public $userName;
    public $ip;
    public $Request;

    public function __construct(Users $user, Auth $auth, Request $request)
    {
        $this->user = $user;
        $this->auth = $auth;

        $this->Request = $request;

        try {
            if (@$request->header()['authorization']) {
                $this->userID = $this->auth->user()->user_id;
                $this->userName = $this->auth->user()->user_name;
                $this->account_id = $this->auth->user()->account_id;
            } else {
                $this->userID = '';
                $this->userName = '';
                $this->account_id = '';
            }
            $this->ip = $request->ip();
            $this->init();
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $code = getTokenMessageToCode($message);
            if ($code == 30001 and !in_array($request->pathinfo(), config('xhy.no_verification_token'))) {
                $this->account_id = '';
                throw new AuthException($message);
            }
            if ($message == 'The token is in blacklist.') {
                throw new AuthException($message);
            }
            $this->init();
        }

    }

    public function init()
    {

    }

    public function logInfo($logTitle, $logDetail = "")
    {
        Utils::writeLog("info", $this->Request, $this->userID, $this->userName, $logTitle, $logDetail);
    }

    public function logError($logTitle, $error = "")
    {
        Utils::writeLog("error", $this->Request, $this->userID, $this->userName, $logTitle, $error);
    }

    /**
     * 验证参数
     */
    public function checkParams($rule, $msg = false)
    {
        //实例化系统验证器
        $validate = new Validate();
        $validate->rule($rule);
        if ($msg) {
            $validate->message($msg);
        }
        //check验证是否正确
        $checkData = $this->Request->param();
        $result = $validate->check($checkData);
        if (!$result) {
            //getError返回错误信息
            $error = $validate->getError($checkData);
            return $error;
        } else {
            return "";
        }
    }


    /**
     * 成功的响应
     *
     * @time 2019年12月02日
     * @param array $data
     * @param $msg
     * @param int $code
     * @return Json
     */
    public function success($data = [], $msg = 'success', $code = Code::SUCCESS): Json
    {
        return json([
            'code' => $code,
            'message' => $msg,
            'data' => $data,
        ]);
    }


    /**
     * 错误的响应
     *
     * @time 2019年12月02日
     * @param string $msg
     * @param int $code
     * @return Json
     */
    public static function fail($msg = '', $ex = "", $code = Code::FAILED): Json
    {
        if ($ex != "") {
            $msg = $msg . $ex->getMessage();
        }
        return json([
            'code' => $code,
            'message' => $msg,
        ]);
    }


    public static function listData($data = [], $totalCount, $pageIndex, $pageSize, $msg = 'success'): Json
    {
        return json(
            [
                'code' => Code::SUCCESS,
                'message' => $msg,
                'data' => $data,
                'pageInfo' => [
                    'count' => $totalCount,
                    'current' => (int)$pageIndex,
                    'limit' => $pageSize,
                ]
            ]);
    }


    /**
     *
     *
     * @time 2019年12月15日
     * @return void
     */
    protected function loadConfig(): void
    {
        $module = explode('\\', get_class($this))[1];

        $moduleConfig = CatchAdmin::moduleDirectory($module) . 'config.php';

        if (file_exists(CatchAdmin::moduleDirectory($module) . 'config.php')) {
            app()->config->load($moduleConfig);
        }
    }

    /**
     *
     * @time 2019年12月13日
     * @param $name
     * @param $value
     * @return void
     */
    public function __set($name, $value)
    {
        // TODO: Implement __set() method.
        $this->{$name} = $value;
    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
    }

    public function __isset($name)
    {
        // TODO: Implement __isset() method.
    }
}
