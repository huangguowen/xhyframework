<?php
namespace xhyadminframework\request;

use xhyadminframework\base\XhyRequest;

class LoginRequest extends XhyRequest
{
    protected $needCreatorId = false;

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'email|用户名'    => 'email',
            'password|密码'  => 'require',
           // 'captcha|验证码' => 'require|captcha'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];

    }
}
