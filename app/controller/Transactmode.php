<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use think\facade\Db;
use app\BaseController;
use app\service\Auth;
use app\model\Currency as CurrencyModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\Transactions as TransactionsModel;

class Transactmode extends BaseController
{
    public function index($currency, Request $request)
    {
        $currency = CurrencyModel::find($currency);

        if (!$currency) {
            View::assign('errmsg', '找不到指定币种');
            return View::fetch('/error');
        }
        
        $msg = '';
        try {
            if ($request->isPost()) {
                $params = array_values($request->post('transactmode', []));

                if (is_array($params)) {
                    foreach ($params as &$param) {
                        $result = $this->validate($param, 'app\validate\Transactmode');
                        $param = array_filter($param, function($v, $k) {
                            return in_array($k, ['id', 'name', 'topup', 'withdrawal', 'sk', 'pay', 'is_shown', 'sortid'], true) && ($k !== 'id' || $v !== '');
                        }, ARRAY_FILTER_USE_BOTH);
                        $param['currency_code'] = $currency;
                    }
                    unset($param);
                }

                $transactmode = new TransactmodeModel;
                $transactmode->saveAll($params);
                
                $msg = '交易方式更新成功';
            } else {
                if ($request->get('delete') && is_numeric($request->get('delete'))) {
                    if (!TransactionsModel::where('transactmode_id')->count()) {
                        $row = TransactmodeModel::find($request->get('delete'));
                        $row && $row->delete();
                        return redirect($request->baseUrl());
                    }
                }
            }
        } catch (\think\exception\ValidateException $e) {
            $msg = "数据错误，{$e->getMessage()}";
        }
        View::assign(
            'transactmode',
            TransactmodeModel::leftJoin('transactions', 'transactions.transactmode_id = transactmode.id')
                ->where('transactmode.currency_code', '=', $currency->code)
                ->group('transactmode.id')
                ->field(['transactmode.*', 'count(transactions.id) as transactions_count'])
                ->order(['sortid', 'id'])
                ->select()
        );
        View::assign('currency', $currency);
        View::assign('msg', $msg);
        return View::fetch();
    }
}