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

class Index extends Controller
{
    public function index()
    {
        $data = array_map(
            function($v) {
                $v['minT'] = date('Y-m-d H:i:s', $v['minT']);
                $v['maxT'] = date('Y-m-d H:i:s', $v['maxT']);
                return $v;
            },
            Loan::group('name')
                ->field([
                    'name',
                    'sum(money)' => 'all',
                    'min(t)' => 'minT',
                    'max(t)' => 'maxT'
                ])
                ->order('all')
                ->select()
                ->toArray()
        );
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function detail($name = '', Request $request)
    {
        if (!$name) {
            return redirect('/loan');
        }

        if ($request->post('delete') === 'true') {
            $sum = Loan::where('loan.name', '=', $name)->sum('money');
            $transactions = new Transactions;
            $transactions->save([
                'transactmode_id' => 1,
                'money'           => -$sum,
                'txt'             => "{$name}销账",
                'amount'          => bcsub(
                    Transactions::order('id', 'desc') // 未锁表，暂未解决并发问题
                        ->where('transactmode_id', '=', 1)
                        ->limit(1)
                        ->value('amount', 0.00),
                    $sum,
                    2
                ),
                't'               => time(),
            ]);
            Loan::where('loan.name', '=', $name)->delete();
            return redirect('/loan');
        }

        if ($request->post('shareTime')) {
            $shareTime = $request->post('shareTime');
            if (is_numeric($shareTime) && $shareTime > 0) {
                $key = Config::get('system.loanshare_key');
                if (!$key) {
                    return json(["status" => "error"]);
                }
                $token = urlencode(UcAuthCode::encode($name, $key, $shareTime * 60));
                return json(["status" => "success", "link" => $request->root(true) . "/loanShare/{$token}"]);
            }
            return json(["status" => "error"]);
        }

        $data = Loan::with('transactmode')
                    ->where('loan.name', '=', $name)
                    ->order('loan.id')
                    ->select();

        if (!$data) {
            return redirect('/loan');
        }

        $this->assign('data', $data);
        $this->assign('name', $name);
        return $this->fetch();
    }
}
