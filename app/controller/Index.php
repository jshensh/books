<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use app\BaseController;
use app\service\Auth;

class Index extends BaseController
{
    public function index(Request $request)
    {
        return View::fetch();
    }
}