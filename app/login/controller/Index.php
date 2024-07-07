<?php
namespace app\login\controller;

use think\Request;
use think\facade\View;
use app\BaseController;
use app\common\service\Auth;

class Index extends BaseController
{
    public function index(Request $request)
    {
        if (Auth::isLogined()) {
            return redirect('/index');
        }

        $isLoginFailed = false;

        if ($request->isPost()) {
            try {
                if (Auth::login($request->post('password'))) {
                    return redirect('/index');
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