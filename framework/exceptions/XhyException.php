<?php
namespace xhyadminframework\exceptions;

use Exception;
use think\exception\HttpException;

abstract class XhyException extends HttpException
{
    protected const HTTP_SUCCESS = 200;

    public function __construct(string $message = '', int $code = 0, Exception $previous = null, array $headers = [], $statusCode = 0)
    {
        parent::__construct($statusCode, $message ? : $this->getMessage(), $previous, $headers, $code);
    }

    public function getStatusCode()
    {
        return self::HTTP_SUCCESS;
    }
}