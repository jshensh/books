<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use app\model\Statements;
use app\model\Transactions;
use app\model\Transactmode;
use app\model\Loan;

class FixData extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('FixData')
            ->setDescription('数据分析与修正工具');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $skip = [];
            
            $statement = [];
            $timestampTemp = [];
            $offsetMoney = [];
            $amountByTransactionMode = [];

            foreach (Transactions::cursor() as $row) {
                $currencyInfo = Transactmode::join('currency', 'currency.code = transactmode.currency_code')
                    ->where('id', 'in', [$row->transactmode_id])
                    ->field(['currency.code', 'currency.scale', 'transactmode.id'])
                    ->select();

                $t = strtotime(substr($row->t, 0, 10)); // 当前账目日期时间戳
                $scale = $currencyInfo[0]->scale;

                if (!isset($amountByTransactionMode[$row->transactmode_id])) {
                    $amountByTransactionMode[$row->transactmode_id] = "0";
                }
                $amountByTransactionMode[$row->transactmode_id] = bcadd($amountByTransactionMode[$row->transactmode_id], $row->money, $scale);
                $row->amount = $amountByTransactionMode[$row->transactmode_id];
                $row->save();

                if (!isset($statement[$currencyInfo[0]->code])) {
                    // 如果未找到币种历史账目则创建初始化 Statement
                    $statement[$currencyInfo[0]->code] = [
                        $t => [
                            'low'    => "0",
                            'high'   => "0",
                            'closed' => "0",
                            'income' => "0",
                            'expend' => "0",
                        ]
                    ];
                    $timestampTemp[$currencyInfo[0]->code] = $t;
                } else {
                    if (!isset($statement[$currencyInfo[0]->code][$t])) {
                        // 如果未找到币种今日账目
                        if ($timestampTemp[$currencyInfo[0]->code] !== $t) {
                            // 如果处理的是当前币种当日首条记录则引用前日记录给 $statementTemp 并进行比较
                            // if (!isset($statement[$currencyInfo[0]->code][$timestampTemp[$currencyInfo[0]->code]])) {
                            //     break;
                            // }
                            $statementTemp = $statement[$currencyInfo[0]->code][$timestampTemp[$currencyInfo[0]->code]];
                            $dbStatementRow = Statements::where('currency_code', '=', $currencyInfo[0]->code)
                                ->where('t', '=', $timestampTemp[$currencyInfo[0]->code])
                                ->find();
                            if (
                                (
                                    !$dbStatementRow ||
                                    bccomp($dbStatementRow->low, $statementTemp['low'], $scale) ||
                                    bccomp($dbStatementRow->high, $statementTemp['high'], $scale) ||
                                    bccomp($dbStatementRow->closed, $statementTemp['closed'], $scale) ||
                                    bccomp($dbStatementRow->income, $statementTemp['income'], $scale) ||
                                    bccomp($dbStatementRow->expend, $statementTemp['expend'], $scale)
                                ) &&
                                !(
                                    $statementTemp['income'] === '0' &&
                                    $statementTemp['expend'] === '0' &&
                                    !$dbStatementRow
                                ) &&
                                !in_array($row->id, $skip, true)
                            ) {
                                $output->info("==============================");
                                $output->info("Error Transaction Row #{$row->id}");
                                $output->info('Currency Code:');
                                var_dump($currencyInfo[0]->code);
                                $output->info('Date:');
                                var_dump(date('Y-m-d', $timestampTemp[$currencyInfo[0]->code]), $timestampTemp[$currencyInfo[0]->code]);
                                $output->info('DB Data:');
                                var_dump(json_decode(json_encode($dbStatementRow), true));
                                $output->info('Calc Data:');
                                var_dump($statementTemp);
                                if (isset($offsetMoney[$currencyInfo[0]->code])) {
                                    $output->info('Offset Money:');
                                    var_dump(bcadd($offsetMoney[$currencyInfo[0]->code], "0", $scale));
                                    var_dump(bcadd($offsetMoney[$currencyInfo[0]->code], $dbStatementRow->closed, $scale));
                                }
                                $choice = (bcadd($offsetMoney[$currencyInfo[0]->code] ?? '0', $dbStatementRow->closed, $scale) === $statementTemp['closed']) ? 'Replace' : $output->choice($input, 'Replace, Ignore or Exit?', ['Replace', 'Ignore', 'Exit']);
                                var_dump($choice);
                                switch ($choice) {
                                    case 'Replace':
                                        $offsetMoney[$currencyInfo[0]->code] = bcsub($statementTemp['closed'], $dbStatementRow->closed, $scale);
                                        $dbStatementRow->save($statementTemp);
                                        break;
                                    case 'Ignore':
                                        $statement[$currencyInfo[0]->code][$timestampTemp[$currencyInfo[0]->code]] = [
                                            'low'    => $dbStatementRow->low,
                                            'high'   => $dbStatementRow->high,
                                            'closed' => $dbStatementRow->closed,
                                            'income' => $dbStatementRow->income,
                                            'expend' => $dbStatementRow->expend,
                                        ];
                                        break;
                                    case 'Exit':
                                        die();
                                }
                            }
                            
                            $statement[$currencyInfo[0]->code][$t] = [
                                'low'    => $statementTemp['closed'],
                                'high'   => $statementTemp['closed'],
                                'closed' => $statementTemp['closed'],
                                'income' => "0",
                                'expend' => "0",
                            ];
                        }
                    }
                }

                $timestampTemp[$currencyInfo[0]->code] = $t;
                if (!preg_match('/(借款|还款|销账|充值|提现)( \(.+\))?$/', $row->txt)) {
                    $money = $row->money;
                    if ($money < 0) {
                        $statement[$currencyInfo[0]->code][$t]['expend'] = bcsub($statement[$currencyInfo[0]->code][$t]['expend'], $money, $scale);
                        if ((float)bcadd($statement[$currencyInfo[0]->code][$t]['closed'], $money, $scale) < $statement[$currencyInfo[0]->code][$t]['low']) {
                            $statement[$currencyInfo[0]->code][$t]['low'] = bcadd($statement[$currencyInfo[0]->code][$t]['closed'], $money, $scale);
                        }
                    } else if ($money > 0) {
                        $statement[$currencyInfo[0]->code][$t]['income'] = bcadd($statement[$currencyInfo[0]->code][$t]['income'], $money, $scale);
                        if ((float)bcadd($statement[$currencyInfo[0]->code][$t]['closed'], $money, $scale) > $statement[$currencyInfo[0]->code][$t]['high']) {
                            $statement[$currencyInfo[0]->code][$t]['high'] = bcadd($statement[$currencyInfo[0]->code][$t]['closed'], $money, $scale);
                        }
                    }
                    $statement[$currencyInfo[0]->code][$t]['closed'] = bcadd($statement[$currencyInfo[0]->code][$t]['closed'], $money, $scale);
                }
            }

            foreach ($statement as $currency => $statementRow) {
                $statementTemp = end($statementRow);
                $dbStatementRow = Statements::where('currency_code', '=', $currency)
                    ->where('t', '=', key($statementRow))
                    ->find();
                if (
                    (
                        !$dbStatementRow ||
                        bccomp($dbStatementRow->low, $statementTemp['low'], $scale) ||
                        bccomp($dbStatementRow->high, $statementTemp['high'], $scale) ||
                        bccomp($dbStatementRow->closed, $statementTemp['closed'], $scale) ||
                        bccomp($dbStatementRow->income, $statementTemp['income'], $scale) ||
                        bccomp($dbStatementRow->expend, $statementTemp['expend'], $scale)
                    ) &&
                    !(
                        $statementTemp['income'] === '0' &&
                        $statementTemp['expend'] === '0' &&
                        !$dbStatementRow
                    ) &&
                    !in_array($row->id, $skip, true)
                ) {
                    $output->info("==============================");
                    $output->info("Error Transaction Row #{$row->id}");
                    $output->info('Currency Code:');
                    var_dump($currencyInfo[0]->code);
                    $output->info('Date:');
                    var_dump(date('Y-m-d', key($statementRow)), key($statementRow));
                    $output->info('DB Data:');
                    var_dump(json_decode(json_encode($dbStatementRow), true));
                    $output->info('Calc Data:');
                    var_dump($statementTemp);
                    if (isset($offsetMoney[$currencyInfo[0]->code])) {
                        $output->info('Offset Money:');
                        var_dump(bcadd($offsetMoney[$currencyInfo[0]->code], "0", $scale));
                        var_dump(bcadd($offsetMoney[$currencyInfo[0]->code], $dbStatementRow->closed, $scale));
                    }
                    $choice = (bcadd($offsetMoney[$currencyInfo[0]->code] ?? '0', $dbStatementRow->closed, $scale) === $statementTemp['closed']) ? 'Replace' : $output->choice($input, 'Replace, Ignore or Exit?', ['Replace', 'Ignore', 'Exit']);
                    var_dump($choice);
                    switch ($choice) {
                        case 'Replace':
                            $offsetMoney[$currencyInfo[0]->code] = bcsub($statementTemp['closed'], $dbStatementRow->closed, $scale);
                            $dbStatementRow->save($statementTemp);
                            break;
                        case 'Ignore':
                            $statement[$currencyInfo[0]->code][key($statementRow)] = [
                                'low'    => $dbStatementRow->low,
                                'high'   => $dbStatementRow->high,
                                'closed' => $dbStatementRow->closed,
                                'income' => $dbStatementRow->income,
                                'expend' => $dbStatementRow->expend,
                            ];
                            break;
                        case 'Exit':
                            die();
                    }
                }
            }

            foreach (Loan::cursor() as $row) {
                if (
                    !Transactions::where('money', '=', $row->money)
                        ->where('t', '=', strtotime($row->t))
                        ->find()
                ) {
                    $output->info("Error Loan Row #{$row->id}");
                    var_dump(json_decode(json_encode($row), true));
                }
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            // var_dump($statement);
        }
    }
}
