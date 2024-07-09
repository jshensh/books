<?php

namespace app\middleware;

use think\facade\View;
use think\facade\Request;
use think\Response;
use app\model\Currency;

class CheckCurrencyExists
{
    public function handle($request, \Closure $next)
    {
        $currency = Request::param('currency');

        if (!$currency || !Currency::find($currency)) {
            View::assign('errmsg', '找不到指定币种');
            return Response::create(View::fetch('/error'));
        }

        return $next($request);
    }
}