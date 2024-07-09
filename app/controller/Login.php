<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use app\BaseController;
use app\service\Auth;

class Login extends BaseController
{
    public function index(Request $request)
    {
        $referer = parse_url($request->get('referer'));
        $referer = $referer ? (ltrim($referer['path'] ?? '', '/') . (isset($referer['query']) ? "?{$referer['query']}" : '')) : '';
        
        if (Auth::isLogined()) {
            return redirect("/{$referer}");
        }

        $isLoginFailed = false;

        if ($request->isPost()) {
            try {
                if (Auth::login($request->post('password'))) {
                    return redirect("/{$referer}");
                }
            } catch (\think\Exception $e) {
                View::assign('errmsg', '请先设置管理员密码');
                return View::fetch('/error');
            }
            $isLoginFailed = true;
        }

        View::assign('isLoginFailed', $isLoginFailed);
        return View::fetch();
    }
}