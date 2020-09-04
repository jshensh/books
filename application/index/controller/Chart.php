<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\facade\Session;

use app\index\model\Statements;

class Chart extends Controller
{
    public function index(Request $request)
    {
        $this->assign('statements', Statements::order('id')->select());
        return $this->fetch();
    }
}