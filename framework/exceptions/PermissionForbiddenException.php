<?php
namespace xhyadminframework\exceptions;

use xhyadminframework\Code;

class PermissionForbiddenException extends XhyException
{
    protected $code = Code::PERMISSION_FORBIDDEN;

    protected $message = '没有该操作权限';
}
