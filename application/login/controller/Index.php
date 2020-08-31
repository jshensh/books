<?php
namespace app\login\controller;

use think\Controller;
use think\Request;
use app\common\service\Auth;

class Index extends Controller
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
                return $this->fetch('error');
            }
            $isLoginFailed = true;
        }
        
        $this->assign('isLoginFailed', $isLoginFailed);
        return $this->fetch();
    }
}
