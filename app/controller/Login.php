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
        if (Auth::isLogined()) {
            return redirect('/');
        }

        $isLoginFailed = false;

        if ($request->isPost()) {
            try {
                if (Auth::login($request->post('password'))) {
                    return redirect('/');
                }
            } catch (\think\Exception $e) {
                return View::fetch('error');
            }
            $isLoginFailed = true;
        }

        View::assign('isLoginFailed', $isLoginFailed);
        return View::fetch();
    }
}