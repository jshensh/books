<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use app\BaseController;
use app\service\Auth;
use app\model\Currency as CurrencyModel;

class Currency extends BaseController
{
    public function index(Request $request)
    {
        return View::fetch();
    }
}