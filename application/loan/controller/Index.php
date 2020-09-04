<?php
namespace app\loan\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\facade\Session;

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
                't'               => time(),
            ]);
            Loan::where('loan.name', '=', $name)->delete();
            return redirect('/loan');
        }

        $data = Loan::with('transactmode')
                    ->where('loan.name', '=', $name)
                    ->order('loan.t')
                    ->select();

        if (!$data) {
            return redirect('/loan');
        }

        $this->assign('data', $data);
        $this->assign('name', $name);
        return $this->fetch();
    }
}
