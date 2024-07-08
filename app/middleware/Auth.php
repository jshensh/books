<?php

namespace app\middleware;

use app\service\Auth as AuthService;

class Auth
{
    public function handle($request, \Closure $next)
    {
        if (!AuthService::isLogined()) {
            return redirect('/login');
        }

        return $next($request);
    }
}