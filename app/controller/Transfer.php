<?php
namespace app\controller;

use think\Request;
use think\facade\View;
use think\facade\Session;
use app\BaseController;
use app\service\Auth;
use app\service\Data as DataService;
use app\model\Currency as CurrencyModel;
use app\model\Transactions as TransactionsModel;
use app\model\Transactmode as TransactmodeModel;
use app\model\Loan as LoanModel;

class Transfer extends BaseController
{
    public function index(Request $request, DataService $dbData)
    {
        $msg = null;
        $originPostData = null;

        try {
            if ($request->isPost()) {
                $this->validate($request->post(), ['__token__' => 'token']);
                
                $params = json_decode($request->post('realCommandJson'), 1);
                if (is_array($params)) {
                    foreach ($params as &$param) {
                        $result = $this->validate($param, 'app\validate\Transfer');
                        $param = array_filter($param, function($v, $k) {
                            return in_array($k, ['mode', 'txt', 'money', 'name', 'isFrozen'], true);
                        }, ARRAY_FILTER_USE_BOTH);
                    }
                    unset($param);
                }

                if (!$params) {
                    $msg = '插入失败';
                } else {
                    $insertResult = ['insertIds' => ['loan' => [], 'transactions' => []], 'originTodayStatementData' => []];
                    foreach ($params as $param) {
                        $tmp = $dbData->insertNew($param['mode'], $param["txt"], $param["money"], $param["name"], $param["isFrozen"]);
                        $insertResult['insertIds']['loan'] = array_merge($insertResult['insertIds']['loan'], $tmp['insertIds']['loan']);
                        $insertResult['insertIds']['transactions'] = array_merge($insertResult['insertIds']['transactions'], $tmp['insertIds']['transactions']);
                        // originTodayStatementData 需要保留同币种最旧数据
                        $insertResult['originTodayStatementData'] = array_merge($tmp['originTodayStatementData'], $insertResult['originTodayStatementData']);
                    }

                    if ($insertResult) {
                        $insertResult['postData'] = $request->post();
                        Session::set('rollback', json_encode($insertResult));
                        $msg = '插入成功 <a href="?rollback=true">撤销</a>';
                    } else {
                        $msg = '插入失败';
                    }
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

        View::assign(
            'data',
            [
                'currency'       => CurrencyModel::column('*', 'code'),
                'transactmode'   => TransactmodeModel::column('*', 'id'),
                'loanUser'       => LoanModel::distinct('name')->column('name'),
                'msg'            => $msg,
                'originPostData' => $originPostData,
            ]
        );
        return View::fetch();
    }
}