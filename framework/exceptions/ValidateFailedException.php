<?php
namespace xhyadminframework\exceptions;

use xhyadminframework\Code;
use xhyadminframework\exceptions\XhyException;

class ValidateFailedException extends XhyException
{
    protected $code = Code::VALIDATE_FAILED;
}
