<?php
namespace xhyadmin\framework\middleware;

use think\Middleware;

class DemoMiddleware extends Middleware
{
    public function handle($request, \Closure $next)
    {
        return $next($request);
    }
}
