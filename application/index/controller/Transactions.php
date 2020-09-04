<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\facade\Session;

use app\index\model\Statements;
use app\index\model\Transactmode;
use app\index\model\Transactions as TransactionsModel;

class Transactions extends Controller
{
    public function index(Request $request)
    {
        $t = strtotime($request->get('t', date('Y-m-d')));
        $mode = $request->get('transactMode');

        if (!$t) {
            $t = strtotime(date('Y-m-d'));
        }

        $transactions = TransactionsModel::where('t', '>=', $t)
                            ->where('t', '<', $t + 86400)
                            ->order(['t' => 'desc', 'id' => 'desc']);
        if ($mode) {
            $transactions = $transactions->where('transactmode_id', '=', $mode);
        }

        $this->assign('data', [
            'transactMode' => Transactmode::field(['id', 'name'])->select(),
            'transactions' => $transactions->select(),
            'mode'         => $mode,
            'link'         => '<a href="?t=' . date('Y-m-d', $t - 86400) . ($mode ? "&transactMode={$mode}" : "") . '">前一天</a>'. (strtotime(date("Y-m-d")) - $t > 0 ? ('&nbsp;<a href="?t=' . date('Y-m-d', $t + 86400) . ($mode ? "&transactMode={$mode}" : "") . '">后一天</a>') : ""),
        ]);
        return $this->fetch();
    }
}