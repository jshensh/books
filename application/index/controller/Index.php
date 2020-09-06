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

class Index extends Controller
{
    private function doInsertNew($inTransactMode, $outTransactMode, $txt, $money, $loanName)
    {
        $money = (float)$money;
        $inTransactMode = (int)$inTransactMode;
        $outTransactMode = (int)$outTransactMode;
        $t = time();

        if ($money === 0.00) {
            return false;
        }

        $insertIds = [
            'loan' => [],
            'transactions' => []
        ];
        Db::startTrans();
        try {
            if (Statements::count()) {
                $lastday = Statements::order('t', 'desc')
                                ->limit(1)
                                ->find();
            }

            /**
             * Statements 为每日数据统计表
             * 先判断表内是否有数据
             * 若无数据，直接插入今日数据，值全为 0
             * 若有数据，并且最后一条数据不是今天插入的，则使用最后一条数值作为今日数据插入
             */
            if (isset($lastday)) {
                if ($lastday->t !== date("Y-m-d", $t)) {
                    (new Statements)->save([
                        't'      => date("Y-m-d", $t),
                        'low'    => $lastday->low,
                        'high'   => $lastday->high,
                        'closed' => $lastday->closed,
                        'income' => $lastday->income,
                        'expend' => $lastday->expend,
                    ]);
                }
            } else {
                (new Statements)->save([
                    't'      => date("Y-m-d", $t),
                    'low'    => 0,
                    'high'   => 0,
                    'closed' => 0,
                    'income' => 0,
                    'expend' => 0,
                ]);
            }

            // 重新获取今日统计，并上锁，为后续更新数据做准备，当发现仍没有数据时则报错
            $todayStatement = Statements::where('t', strtotime(date("Y-m-d", $t)))->lock(true)->find();
            if (!$todayStatement) {
                throw \Exception();
            }

            $originTodayStatementData = [
                'low'    => $todayStatement->low,
                'high'   => $todayStatement->high,
                'closed' => $todayStatement->closed,
                'income' => $todayStatement->income,
                'expend' => $todayStatement->expend,
            ];

            if ($money < 0) {
                $tmpTransactMode = $outTransactMode;
            } else {
                $tmpTransactMode = $inTransactMode;
            }

            /** 如果存在借款人，则修改账目备注（表示这是一笔借 / 贷款，借入贷出） */
            if ($loanName) {
                $hisLoanSum = Loan::where('name', '=', $loanName)->sum('money');
                $txtForLoan = $txt;
                if ((float)$hisLoanSum === 0.00) {
                    if ($money < 0) {
                        $txt = $txtForLoan ? "{$loanName}借款 ({$txtForLoan})" : "{$loanName}借款";
                    } else {
                        $txt = "借款";
                    }
                } else if ($hisLoanSum < 0) {
                    if ($money < 0) {
                        $txt = $txtForLoan ? "{$loanName}借款 ({$txtForLoan})" : "{$loanName}借款";
                    } else {
                        $txt = $txtForLoan ? "{$loanName}还款 ({$txtForLoan})" : "{$loanName}还款";
                    }
                } else if ($hisLoanSum > 0) {
                    if ($money < 0) {
                        $txt = $txtForLoan ? "还款 ({$txtForLoan})" : '还款';
                    } else {
                        $txt = $txtForLoan ? "借款 ({$txtForLoan})" : '借款';
                    }
                }

                /** 当一笔借款 / 还款操作数额过度，导致借贷关系反转时需要拆分单笔账目 */
                if (($hisLoanSum ^ $money) < 0 && (float)$hisLoanSum !== 0.00) {
                    $loanNewSum = (float)bcadd($hisLoanSum, $money, 2);
                    if (($hisLoanSum ^ $loanNewSum) < 0 && (float)$loanNewSum !== 0.00) {
                        $money = $loanNewSum;

                        $loan1 = new Loan;
                        $loan1->save([
                            'name'            => $loanName,
                            'transactmode_id' => $tmpTransactMode,
                            'money'           => -$hisLoanSum,
                            'txt'             => $txtForLoan,
                            't'               => $t
                        ]);
                        $insertIds['loan'][] = $loan1->id;

                        $transactions1 = new Transactions;
                        $transactions1->save([
                            'transactmode_id' => $tmpTransactMode,
                            'money'           => -$hisLoanSum,
                            'txt'             => $txt,
                            'amount'          => bcsub(
                                Transactions::order('id', 'desc') // 未锁表，暂未解决并发问题
                                    ->where('transactmode_id', '=', $tmpTransactMode)
                                    ->limit(1)
                                    ->value('amount', 0.00),
                                $hisLoanSum,
                                2
                            ),
                            't'               => $t
                        ]);
                        $insertIds['transactions'][] = $transactions1->id;

                        if ($money > 0) {
                            $txt = "借款";
                        } else {
                            $txt = $txtForLoan ? "{$loanName}借款 ({$txtForLoan})" : "{$loanName}借款";
                        }
                    }
                }

                $loan2 = new Loan;
                $loan2->save([
                    'name'            => $loanName,
                    'transactmode_id' => $tmpTransactMode,
                    'money'           => $money,
                    'txt'             => $txtForLoan,
                    't'               => $t
                ]);
                $insertIds['loan'][] = $loan2->id;
            } else {
                /** 如果是笔账户互转操作（充值 / 提现） */
                if ($inTransactMode > 0 && $outTransactMode > 0) {
                    $money = abs($money);
                    $tmpTransactMode = $inTransactMode;

                    $transactions1 = new Transactions;
                    $transactions1->save([
                        'transactmode_id' => $outTransactMode,
                        'money'           => -$money,
                        'txt'             => $outTransactMode === 1 ? $txt : '提现',
                        'amount'          => bcsub(
                            Transactions::order('id', 'desc') // 未锁表，暂未解决并发问题
                                ->where('transactmode_id', '=', $outTransactMode)
                                ->limit(1)
                                ->value('amount', 0.00),
                            $money,
                            2
                        ),
                        't'               => $t
                    ]);
                    $insertIds['transactions'][] = $transactions1->id;

                    $txt = $inTransactMode === 1 ? '提现' : $txt;
                } else {
                    // 只有发生实际的支出 / 收入操作时才操作统计数据
                    $todayStatement->closed = bcadd($todayStatement->closed, $money, 2);
                    if ($money < 0) {
                        $todayStatement->expend = bcsub($todayStatement->expend, $money, 2);
                        if ((float)bcadd($todayStatement->closed, $money, 2) < $todayStatement->low) {
                            $todayStatement->low = bcadd($todayStatement->closed, $money, 2);
                        }
                        $txt = $txt ? $txt : '支出';
                    } else if ($money > 0) {
                        $todayStatement->income = bcadd($todayStatement->income, $money, 2);
                        if ((float)bcadd($todayStatement->closed, $money, 2) > $todayStatement->high) {
                            $todayStatement->high = bcadd($todayStatement->closed, $money, 2);
                        }
                        $txt = $txt ? $txt : '收入';
                    }
                    $todayStatement->save();
                }
            }

            $transactions2 = new Transactions;
            $transactions2->save([
                'transactmode_id' => $tmpTransactMode,
                'money'           => $money,
                'txt'             => $txt,
                'amount'          => bcadd(
                    Transactions::order('id', 'desc') // 未锁表，暂未解决并发问题
                        ->where('transactmode_id', '=', $tmpTransactMode)
                        ->limit(1)
                        ->value('amount', 0.00),
                    $money,
                    2
                ),
                't'               => $t
            ]);
            $insertIds['transactions'][] = $transactions2->id;

            // 提交事务
            Db::commit();
            return ['insertIds' => $insertIds, 'originTodayStatementData' => $originTodayStatementData, 't' => $t];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        return false;
    }

    private function insertNew($mode, $txt, $money, $name)
    {
        list($outTransactMode, $inTransactMode) = explode("_", $mode);

        if (!($outTransactMode >= 0 && is_numeric($outTransactMode) && $inTransactMode >= 0 && is_numeric($inTransactMode) && $outTransactMode + $inTransactMode>0)) {
            return false;
        }

        if ($outTransactMode > 0 && $inTransactMode > 0) {
            $name = "";
        } else {
            if ($outTransactMode > 0) {
                $money = -abs($money);
            } else {
                $money = abs($money);
            }
        }

        return $this->doInsertNew($inTransactMode, $outTransactMode, $txt, $money, $name);
    }

    private function doRollback($data)
    {
        Db::startTrans();
        try {
            Loan::where('id', 'in', $data['insertIds']['loan'])->delete();
            Transactions::where('id', 'in', $data['insertIds']['transactions'])->delete();
            Statements::where('t', '=', strtotime(date('Y-m-d', $data['t'])))->update($data['originTodayStatementData']);

            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        return false;
    }

    public function index(Request $request)
    {
        $msg = null;
        $originPostData = null;

        if ($request->isPost()) {
            $params = $request->post();
            $result = $this->validate($params, 'app\index\validate\Index');

            if (true !== $result) {
                $msg = "数据错误，{$result}";
            } else {
                $insertResult = $this->insertNew($params["transactMode"], $params["txt"], $params["money"], $params["name"]);
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
                if ($data && $this->doRollback($data)) {
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
