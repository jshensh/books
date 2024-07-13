<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use app\BaseController;
use app\service\Auth;
use app\model\Currency as CurrencyModel;
use app\model\Transactions as TransactionsModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\Loan as LoanModel;

class Transfer extends BaseController
{
    public function index(Request $request)
    {
        View::assign(
            'data',
            [
                'currency'     => CurrencyModel::column('*', 'code'),
                'transactmode' => TransactmodeModel::column('*', 'id'),
                'loanUser'     => LoanModel::distinct('name')->column('name')
            ]
        );
        return View::fetch();
    }
}