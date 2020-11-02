<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\facade\Session;

use app\index\model\Transactions;
use app\index\model\Transactmode;
use app\index\model\Loan;
use app\index\model\Statements;

use app\common\service\Data as DataService;

class Index extends Controller
{
    public function index(Request $request, DataService $dbData)
    {
        $msg = null;
        $originPostData = null;

        if ($request->isPost()) {
            $params = $request->post();
            $result = $this->validate($params, 'app\index\validate\Index');

            if (true !== $result) {
                $msg = "数据错误，{$result}";
            } else {
                $insertResult = $dbData->insertNew($params["transactMode"], $params["txt"], $params["money"], $params["name"]);
                if ($insertResult) {
                    $insertResult['postData'] = $params;
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

        $transactionsLastID = Transactions::group('transactmode_id')->column('max(id)');
        $transactModeList = Transactmode::alias('t1')
                                ->leftJoin([Transactions::where('id', 'in', $transactionsLastID)->buildSql() => 't2'], 't1.id = t2.transactmode_id')
                                ->field(['t1.*', 't2.transactmode_id', 't2.amount'])
                                ->order(['t1.sortid', 't1.id'])
                                ->select();
                                
        $lastdayClosed = Statements::where('t', '<>', strtotime(date("Y-m-d")))
                            ->order('t', 'desc')
                            ->limit(1)
                            ->value('closed', 0);
                                
        $todayClosed = Statements::where('t', '=', strtotime(date("Y-m-d")))
                        ->value('closed', 0);

        $this->assign('data', [
            'transactModeList' => $transactModeList,
            'loanSum'          => Loan::sum('money'),
            'todayTotal'       => (float)$todayClosed === 0.00 ? 0 : ($todayClosed - $lastdayClosed),
            'msg'              => $msg,
            'originPostData'   => $originPostData,
        ]);
        return $this->fetch();
    }
}
