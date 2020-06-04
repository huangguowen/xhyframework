<?php
namespace xhyadminframework\exceptions;

use xhyadminframework\Code;

class FailedException extends XhyException
{
    protected $code = Code::FAILED;
}
