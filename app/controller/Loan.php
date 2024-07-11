<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use app\BaseController;
use app\service\Auth;
use app\service\UcAuthCode;
use app\model\Currency as CurrencyModel;
use app\model\Loan as LoanModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\Transactions as TransactionsModel;

class Loan extends BaseController
{
    public function index()
    {
        $data = array_map(
            function($v) {
                $v['minT'] = date('Y-m-d H:i:s', $v['minT']);
                $v['maxT'] = date('Y-m-d H:i:s', $v['maxT']);
                return $v;
            },
            LoanModel::join('transactmode', 'transactmode.id = loan.transactmode_id')
                ->group('loan.name,loan.is_frozen,transactmode.currency_code')
                ->field([
                    'loan.name',
                    'loan.is_frozen',
                    'transactmode.currency_code',
                    'sum(money)' => 'all',
                    'min(loan.t)' => 'minT',
                    'max(loan.t)' => 'maxT'
                ])
                ->order(['loan.name', 'transactmode.currency_code', 'loan.is_frozen', 'all'])
                ->select()
                ->toArray()
        );
        View::assign('currency', CurrencyModel::column('*', 'code'));
        View::assign('data', $data);
        return View::fetch();
    }

    public function detail($currency, $name = '', Request $request)
    {
        if (!$name) {
            return redirect(url('/loan')->domain(true));
        }

        $currency = CurrencyModel::find($currency);
        $decimal = 'decimal(' . (20 - $currency->scale) . ',' . $currency->scale . ')';

        if ($request->post('delete') === 'true') {
            $sum = Loan::join('transactmode', 'transactmode.id = loan.transactmode_id')
                ->where('loan.name', '=', $name)
                ->where('transactmode.currency_code', '=', $currency)
                ->sum('loan.money');
            $transactions = new TransactionsModel;
            $transactions->save([
                'transactmode_id' => 1,
                'money'           => -$sum,
                'txt'             => "{$name}销账",
                'amount'          => bcsub(
                    TransactionsModel::order('id', 'desc') // 未锁表，暂未解决并发问题
                        ->where('transactmode_id', '=', 1)
                        ->limit(1)
                        ->value('amount', 0.00),
                    $sum,
                    $currency->scale
                ),
                't'               => time(),
            ]);
            LoanModel::join('transactmode', 'transactmode.id = loan.transactmode_id')
                ->where('loan.name', '=', $name)
                ->where('transactmode.currency_code', '=', $currency)
                ->delete();
            return redirect(url('/loan')->domain(true));
        }

        if ($request->post('shareTime')) {
            $shareTime = $request->post('shareTime');
            if (is_numeric($shareTime) && $shareTime > 0) {
                $key = Config::get('system.loanshare_key');
                if (!$key) {
                    return json(["status" => "error"]);
                }
                $token = urlencode(UcAuthCode::encode($name, $key, $shareTime * 60));
                return json(["status" => "success", "link" => url("/loanShare/{$token}")->domain(true)]);
            }
            return json(["status" => "error"]);
        }

        $data = LoanModel::with('transactmode')
                    ->join('transactmode', 'transactmode.id = loan.transactmode_id')
                    ->field(['loan.id', 'loan.name', 'loan.transactmode_id', "cast(loan.money as {$decimal}) as money", 'loan.is_frozen', 'loan.txt', 'loan.t'])
                    ->where('transactmode.currency_code', '=', $currency->code)
                    ->where('loan.is_frozen', '=', (int) (bool) $request->get('is_frozen', ''))
                    ->where('loan.name', '=', $name)
                    ->order('loan.id')
                    ->select();

        if (!$data) {
            return redirect('/loan');
        }

        View::assign('currency', $currency);
        View::assign('data', $data);
        View::assign('name', $name);
        return View::fetch();
    }
}