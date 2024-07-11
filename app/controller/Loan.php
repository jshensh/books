<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use app\BaseController;
use app\service\Auth;
use app\model\Currency as CurrencyModel;
use app\model\Loan as LoanModel;
use app\model\Transactmode as TransactmodeModel;

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
}