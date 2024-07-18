<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use app\BaseController;
use app\service\Auth;
use app\model\Currency as CurrencyModel;
use app\model\Transactions as TransactionsModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\TransferRequest as TransferRequestModel;

class Index extends BaseController
{
    public function index(Request $request)
    {
        $transactionsLastID = TransactionsModel::group('transactmode_id')->column('max(id)');
        $transactModeList = TransactmodeModel::alias('t1')
            ->leftJoin([TransactionsModel::where('id', 'in', $transactionsLastID)->buildSql() => 't2'], 't1.id = t2.transactmode_id')
            ->field(['t1.*', 't2.transactmode_id', 't2.amount']);
            
        View::assign(
            'currencySum',
            CurrencyModel::leftJoin([$transactModeList->buildSql() => 't3'], 't3.currency_code = currency.code')
                ->group('currency.code')
                ->field(['currency.*', 'IFNULL(sum(t3.amount), 0) as amount'])
                ->order('currency.sortid')
                ->select()
        );
        View::assign('pendingTransferCount', TransferRequestModel::where('status', '=', 0)->count());
        return View::fetch();
    }
}