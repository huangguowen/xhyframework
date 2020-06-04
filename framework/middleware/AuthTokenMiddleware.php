<?php
namespace xhyadminframework\middleware;

use xhyadminframework\XhyResponse;
use xhyadminframework\Code;
use xhyadminframework\exceptions\FailedException;
use thans\jwt\exception\TokenBlacklistException;
use thans\jwt\exception\TokenExpiredException;
use thans\jwt\exception\TokenInvalidException;
use thans\jwt\facade\JWTAuth;
use think\Middleware;

class AuthTokenMiddleware extends Middleware
{
    public function handle($request, \Closure $next)
    {
       try {
          JWTAuth::auth();
       } catch (\Exception $e) {
           if ($e instanceof TokenExpiredException) {
               return XhyResponse::fail("token 过期");
           }
           if ($e instanceof TokenBlacklistException) {
               return XhyResponse::fail("您已下线");
           }
           if ($e instanceof TokenInvalidException) {
               return XhyResponse::fail("token 不合法");
           }
           return XhyResponse::fail("登录用户不合法");
       }

       return $next($request);
    }
}
