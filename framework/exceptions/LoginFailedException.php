<?php
namespace xhyadminframework\exceptions;

use xhyadminframework\Code;
use xhyadminframework\exceptions\XhyException;

class LoginFailedException extends XhyException
{
    protected $code = Code::LOGIN_FAILED;

    protected $message = '您输入的账号或密码错误，请重新输入';
}
