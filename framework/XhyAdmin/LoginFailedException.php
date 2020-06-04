<?php
namespace catcher\exceptions;

use catcher\Code;

class LoginFailedException extends CatchException
{
    protected $code = Code::LOGIN_FAILED;

    protected $message = '您输入的账号或密码错误，请重新输入';
}
