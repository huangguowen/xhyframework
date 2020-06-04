<?php
namespace xhyadminframework\middleware;

use catcher\Code;
use catcher\exceptions\FailedException;
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
               return CatchResponse::fail("token 过期");
           }
           if ($e instanceof TokenBlacklistException) {
               return CatchResponse::fail("您已下线");
           }
           if ($e instanceof TokenInvalidException) {
               return CatchResponse::fail("token 不合法");
           }
           return CatchResponse::fail("登录用户不合法");
       }

       return $next($request);
    }
}
