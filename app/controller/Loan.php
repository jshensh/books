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
use app\model\TransferRequest as TransferRequestModel;

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
            $name = UcAuthCode::decode(urldecode($token), $key, true);

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

    public function detail($currency, $name = '', $token = '', Request $request)
    {
        if (Auth::isLogined()) {
            if (!$name) {
                return redirect(url('/loan')->domain(true));
            }

            $currency = CurrencyModel::find($currency);
            $decimal = 'decimal(' . (20 - $currency->scale) . ',' . $currency->scale . ')';
        
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
            $name = UcAuthCode::decode(urldecode($token), $key, true);

            if (!$name) {
                View::assign('errmsg', '无法访问指定账单，请联系您的债权（债务）人重新获取链接');
                return View::fetch('/error');
            }

            $name = $name[0];
            $currency = CurrencyModel::find($currency);
            $decimal = 'decimal(' . (20 - $currency->scale) . ',' . $currency->scale . ')';
        }

        $data = LoanModel::withJoin('transactmode')
            ->field(['loan.id', 'loan.name', 'loan.transactmode_id', 'transactmode.name as transactmode_name', "cast(loan.money as {$decimal}) as money", 'loan.is_frozen', 'loan.txt', 'loan.t'])
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
            return View::fetch('/share/detail');
        }
        return View::fetch();
    }

    public function share(Request $request)
    {
        if ($request->post('shareTime') && $request->post('name')) {
            $shareTime = $request->post('shareTime');
            if (is_numeric($shareTime) && $shareTime > 0) {
                $key = Config::get('system.loanshare_key');
                if (!$key) {
                    return json(["status" => "error"]);
                }
                $token = urlencode(UcAuthCode::encode($request->post('name'), $key, $shareTime * 60));
                return json(["status" => "success", "link" => $request->root(true) . "/share/" . urlencode($token)]);
            }
        }
        
        return json(["status" => "error"]);
    }

    public function transfer($token = '', Request $request)
    {
        $key = Config::get('system.loanshare_key');
        $name = UcAuthCode::decode(urldecode($token), $key, true);

        if (!$name) {
            View::assign('errmsg', '无法访问指定账单，请联系您的债权（债务）人重新获取链接');
            return View::fetch('/error');
        }

        $name = $name[0];

        $msg = null;
        if ($request->isPost()) {
            try {
                $params = $request->post();

                if (!isset($params['loanName']) || !isset($params['currency']) || !isset($params['money'])) {
                    throw new \Exception('请求漏参');
                }

                $params['loanName'] = trim($params['loanName']);

                $currency = CurrencyModel::find($params['currency']);
                if (!$currency) {
                    throw new \Exception('交易币种输入错误');
                }

                $params['money'] = number_format((float) $params['money'], $currency->scale, ".", "");
                if (bccomp($params['money'], '99999999999.99999999', $currency->scale) > 0) {
                    throw new \Exception('请款金额过大');
                }
                if (bccomp($params['money'], number_format(pow(0.1, $currency->scale), $currency->scale, ".", ""), $currency->scale) < 0) {
                    throw new \Exception('请款金额过小');
                }

                if ($name === $params['loanName']) {
                    throw new \Exception('不能向自己请款');
                }

                if (TransferRequestModel::where('loan_name_to', '=', $name)->where('status', '=', 0)->count() >= 5) {
                    throw new \Exception('待审核的请款请求已有五个');
                }

                $params['txt'] = substr(strip_tags(trim($params['txt'] ?? '')), 0, 255);

                $row = (new TransferRequestModel)->save([
                    'loan_name_to'   => $name,
                    'loan_name_from' => $params['loanName'],
                    'txt'            => $params['txt'],
                    'currency_code'  => $params['currency'],
                    'money'          => $params['money'],
                    'status'         => 0
                ]);
                $msg = '提交成功';
            } catch (\Exception $e) {
                $msg = "提交失败，{$e->getMessage()}";
            }
        }

        $page = (int) $request->get('page');
        $page = $page <= 0 ? 1 : $page;

        $offset = 10;

        $dataCount = TransferRequestModel::where('loan_name_to', '=', $name)->count();
        $data = TransferRequestModel::where('loan_name_to', '=', $name)
            ->order('id', 'desc')
            ->limit(($page - 1) * $offset, $offset)
            ->select();
        
        View::assign(
            'data',
            [
                'data'      => $data,
                'dataCount' => $dataCount,
                'page'      => $page,
                'offset'    => $offset,
                'loanUser'  => LoanModel::distinct('name')->where('name', '<>', $name)->column('name'),
                'currency'  => CurrencyModel::column('*', 'code'),
                'msg'       => $msg
            ]
        );
        return View::fetch('/share/transfer');
    }
}