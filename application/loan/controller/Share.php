<?php
namespace app\loan\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\facade\Session;
use think\facade\Config;
use app\common\service\UcAuthCode;

use app\loan\model\Transactions;
use app\loan\model\Transactmode;
use app\loan\model\Loan;

class Share extends Controller
{
    public function index($token)
    {
        $key = Config::get('system.loanshare_key');
        $name = UcAuthCode::decode($token, $key, true);

        if (!$name) {
            return $this->fetch('error');
        }

        $data = Loan::with('transactmode')
                    ->where('loan.name', '=', $name[0])
                    ->order('loan.t')
                    ->select();

        if (!$data) {
            return $this->fetch('error');
        }

        $this->assign('data', $data);
        $this->assign('rd', $name[1]);
        return $this->fetch();
    }
}