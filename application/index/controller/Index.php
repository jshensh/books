<?php
namespace app\index\controller;

use think\Controller;
use think\Request;

use app\index\model\Transactions;
use app\index\model\Transactmode;
use app\index\model\Loan;
use app\index\model\Statements;

class Index extends Controller
{
    public function index(Request $request)
    {
        $transactionsLastID = Transactions::group('transactmode_id')->column('max(id)');
        $transactModeList = Transactmode::alias('t1')
                                ->leftJoin([Transactions::where('id', 'in', $transactionsLastID)->buildSql() => 't2'], 't1.id = t2.transactmode_id')
                                ->field(['t1.*', 't2.transactmode_id', 't2.amount'])
                                ->order(['t1.sortid', 't1.id'])
                                ->select();
                                
        $yesterdayClosed = Statements::where('t', '<>', strtotime(date("Y-m-d")))
                            ->order('t', 'desc')
                            ->limit(1)
                            ->value('closed', 0);
                                
        $todayClosed = Statements::where('t', '=', strtotime(date("Y-m-d")))
                        ->value('closed', 0);

        $this->assign('data', [
            'transactModeList' => $transactModeList,
            'loanSum'          => Loan::sum('money'),
            'todayTotal'       => (float)$todayClosed === 0.00 ? 0 : ($todayClosed - $yesterdayClosed),
        //     'postStatus'       => ,
        //     'rollbackStatus'   => ,
        ]);
        return $this->fetch();
    }
}
