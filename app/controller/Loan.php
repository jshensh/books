<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use think\facade\Config;
use think\facade\Db;
use app\BaseController;
use app\service\Auth;
use app\service\UcAuthCode;
use app\model\Currency as CurrencyModel;
use app\model\Loan as LoanModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\Transactions as TransactionsModel;

class Loan extends BaseController
{
    public function index($token = '', Request $request)
    {
        $loanQuery = LoanModel::join('transactmode', 'transactmode.id = loan.transactmode_id')
            ->group('loan.name,loan.is_frozen,transactmode.currency_code')
            ->field([
                'loan.name',
                'loan.is_frozen',
                'transactmode.currency_code',
                'sum(money)' => 'all',
                'min(loan.t)' => 'minT',
                'max(loan.t)' => 'maxT'
            ])
            ->order(['loan.name', 'transactmode.currency_code', 'loan.is_frozen', 'all']);

        if (!Auth::isLogined()) {
            $key = Config::get('system.loanshare_key');
            $name = UcAuthCode::decode($token, $key, true);

            if (!$name) {
                View::assign('errmsg', '无法访问指定账单，请联系您的债权（债务）人重新获取链接');
                return View::fetch('/error');
            }

            $loanQuery = $loanQuery->where('loan.name', '=', $name[0]);
            View::assign('rd', $name[1]);
            View::assign('token', $token);
        }

        $data = array_map(
            function($v) {
                $v['minT'] = date('Y-m-d H:i:s', $v['minT']);
                $v['maxT'] = date('Y-m-d H:i:s', $v['maxT']);
                return $v;
            },
            $loanQuery->select()->toArray()
        );
        View::assign('currency', CurrencyModel::column('*', 'code'));
        View::assign('data', $data);

        if (!Auth::isLogined()) {
            return View::fetch('/share/index');
        }

        return View::fetch();
    }

    public function share($name = '', Request $request)
    {
        if ($request->post('shareTime') && $request->post('name')) {
            $shareTime = $request->post('shareTime');
            if (is_numeric($shareTime) && $shareTime > 0) {
                $key = Config::get('system.loanshare_key');
                if (!$key) {
                    return json(["status" => "error"]);
                }
                $token = urlencode(UcAuthCode::encode($request->post('name'), $key, $shareTime * 60));
                return json(["status" => "success", "link" => $request->root(true) . "/share/{$token}"]);
            }
        }
        
        return json(["status" => "error"]);
    }

    public function detail($currency, $name = '', $token = '', Request $request)
    {
        $currency = CurrencyModel::find($currency);
        $decimal = 'decimal(' . (20 - $currency->scale) . ',' . $currency->scale . ')';
        
        if (Auth::isLogined()) {
            if (!$name) {
                return redirect(url('/loan')->domain(true));
            }

            if ($request->post('delete') === 'true') {
                $sum = LoanModel::join('transactmode', 'transactmode.id = loan.transactmode_id')
                    ->where('loan.name', '=', $name)
                    ->where('transactmode.currency_code', '=', $currency->code)
                    ->sum('loan.money');
                $transactions = new TransactionsModel;
                $transactions->save([
                    'transactmode_id' => TransactmodeModel::where('currency_code', '=', $currency->code)->min('id'),
                    'money'           => -$sum,
                    'txt'             => "{$name}销账",
                    'amount'          => bcsub(
                        TransactionsModel::order('id', 'desc') // 未锁表，暂未解决并发问题
                            ->where('transactmode_id', '=', TransactmodeModel::where('currency_code', '=', $currency->code)->min('id'))
                            ->limit(1)
                            ->value('amount', 0.00),
                        $sum,
                        $currency->scale
                    ),
                    't'               => time(),
                ]);
                Db::table('loan')
                    ->alias('TLOAN')
                    ->join('transactmode', 'transactmode.id = loan.transactmode_id')
                    ->where('loan.name', '=', $name)
                    ->where('transactmode.currency_code', '=', $currency->code)
                    ->extra('TLOAN')
                    ->delete();
                return redirect(url('/loan')->domain(true));
            }
        } else {
            $key = Config::get('system.loanshare_key');
            $name = UcAuthCode::decode($token, $key, true);

            if (!$name) {
                View::assign('errmsg', '无法访问指定账单，请联系您的债权（债务）人重新获取链接');
                return View::fetch('/error');
            }

            $name = $name[0];
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
            if (Auth::isLogined()) {
                return redirect('/loan');
            }
            View::assign('errmsg', '无法访问指定账单，请联系您的债权（债务）人重新获取链接');
            return View::fetch('/error');
        }

        View::assign('currency', $currency);
        View::assign('data', $data);
        View::assign('name', $name);
        View::assign('isFrozen', (int) (bool) $request->get('is_frozen', ''));

        if (!Auth::isLogined()) {
            return View::fetch('shareDetail');
        }
        return View::fetch();
    }
}