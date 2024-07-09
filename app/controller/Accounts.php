<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use think\facade\Session;
use app\BaseController;
use app\service\Auth;
use app\model\Currency as CurrencyModel;
use app\model\Transactions as TransactionsModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\Statements as StatementsModel;
use app\model\Loan as LoanModel;
use app\service\Data as DataService;

class Accounts extends BaseController
{
    public function index($currency, Request $request, DataService $dbData)
    {
        $msg = null;
        $originPostData = null;

        try {
            if ($request->isPost()) {
                $params = $request->post();
                $result = $this->validate($params, 'app\validate\Accounts');

                $insertResult = $dbData->insertNew($params["transactMode"], $params["txt"], $params["money"], $params["name"]);
                if ($insertResult) {
                    $insertResult['postData'] = $params;
                    Session::set('rollback', json_encode($insertResult));
                    $msg = '插入成功 <a href="?rollback=true">撤销</a>';
                } else {
                    $msg = '插入失败';
                }
            } else {
                if ($request->get("rollback")) {
                    $data = json_decode(Session::get('rollback'), 1);
                    if ($data && $dbData->doRollback($data)) {
                        $msg = '撤销成功';
                        Session::delete('rollback');
                        $originPostData = $data['postData'];
                    } else {
                        $msg = '撤销失败';
                    }
                }
            }
        } catch (\think\exception\ValidateException $e) {
            $msg = "数据错误，{$e->getMessage()}";
        }

        $transactionsLastID = TransactionsModel::rightJoin('transactmode', 'transactmode.id = transactions.transactmode_id')
            ->group('transactions.transactmode_id')
            ->where('transactmode.currency_code', '=', $currency)
            ->column('max(transactions.id) as ids');
        $transactModeList = TransactmodeModel::alias('t1')
            ->where('transactmode.currency_code', '=', $currency)
            ->leftJoin([TransactionsModel::where('id', 'in', $transactionsLastID)->buildSql() => 't2'], 't1.id = t2.transactmode_id')
            ->field(['t1.*', 't2.transactmode_id', 't2.amount'])
            ->order(['t1.sortid', 't1.id'])
            ->select();
                                
        $lastdayClosed = StatementsModel::where('t', '<>', strtotime(date("Y-m-d")))
            ->where('currency_code', '=', $currency)
            ->order('t', 'desc')
            ->limit(1)
            ->value('closed', 0);
                                
        $todayClosed = StatementsModel::where('t', '=', strtotime(date("Y-m-d")))
            ->where('currency_code', '=', $currency)
            ->value('closed', 0);

        View::assign('data', [
            'transactModeList' => $transactModeList,
            'loanSum'          => LoanModel::join('transactmode', 'transactmode.id = loan.transactmode_id')->where('transactmode.currency_code', '=', $currency)->sum('loan.money'),
            'todayTotal'       => (float)$todayClosed === 0.00 ? 0 : ($todayClosed - $lastdayClosed),
            'msg'              => $msg,
            'originPostData'   => $originPostData,
            'currentCurrency'  => CurrencyModel::find($currency)
        ]);
        return View::fetch();
    }
}