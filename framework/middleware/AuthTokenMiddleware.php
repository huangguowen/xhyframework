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
                return json([
                    'code' => 10005,
                    'message'  => 'The token is expired.',
                ]);
            }
            if ($e instanceof TokenBlacklistException) {
                return json([
                    'code' => 10005,
                    'message'  => 'The token is in blacklist.',
                ]);
            }
            if ($e instanceof TokenInvalidException) {
                return json([
                    'code' => 10006,
                    'message'  => 'Token Signature could not be verified.',
                ]);
            }
            return json([
                'code' => 10005,
                'message'  => '登录用户不合法',
            ]);
        }

        return $next($request);
    }
}
