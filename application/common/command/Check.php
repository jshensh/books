<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use app\common\model\Transactions;
use app\common\model\Statements;
use app\common\model\Loan;

class Check extends Command
{
    protected function configure()
    {
        $this->setName('Check')
            ->setDescription('验证账簿数据合法性');
    }

    protected function execute(Input $input, Output $output)
    {
        $statements = [];
        $loanSum = 0.00;
        Transactions::chunk(100, function ($rows) use (&$statements, &$loanSum, $output) {
            $output->writeln('Calculating ' . $rows[0]->id . ' - ' . end($rows)->id . ' ' . count($rows) . ' Rows.');
            foreach ($rows as $row) {
                $dTime = strtotime(date('Y-m-d', (int)$row->t));
                if (!isset($statements[$dTime])) {
                    if (count($statements)) {
                        $statements[$dTime] = [
                            'low'    => end($statements)['closed'],
                            'high'   => end($statements)['closed'],
                            'closed' => end($statements)['closed'],
                            'income' => '0.00',
                            'expend' => '0.00',
                        ];
                    } else {
                        $statements[$dTime] = [
                            'low'    => '0.00',
                            'high'   => '0.00',
                            'closed' => '0.00',
                            'income' => '0.00',
                            'expend' => '0.00',
                        ];
                    }
                }
                
                if (preg_match("/(充值|提现)( |$)/", $row->txt)) {
                    continue;
                }
                if (preg_match("/(借款|还款|销账)( |$)/", $row->txt)) {
                    $loanSum = bcadd($loanSum, $row->money, 2);
                    continue;
                }
                $money = (float)$row->money;
                $statements[$dTime]['closed'] = bcadd($money, $statements[$dTime]['closed'], 2);

                // if ($dTime === 1485446400) {
                //     var_dump($row);
                // }

                if ((float)$statements[$dTime]['closed'] > (float)$statements[$dTime]['high']) {
                    $statements[$dTime]['high'] = $statements[$dTime]['closed'];
                }

                if ((float)$statements[$dTime]['closed'] < (float)$statements[$dTime]['low']) {
                    $statements[$dTime]['low'] = $statements[$dTime]['closed'];
                }

                if ($money > 0) {
                    $statements[$dTime]['income'] = bcadd($statements[$dTime]['income'], $money, 2);
                }

                if ($money < 0) {
                    $statements[$dTime]['expend'] = bcadd($statements[$dTime]['expend'], abs($money), 2);
                }
            }
        });
        
        $dbStatements = Statements::select()->toArray();

        foreach ($dbStatements as $key => $value) {
            if (
                // (float)$value['low'] !== (float)$statements[$value['t']]['low'] ||
                // (float)$value['high'] !== (float)$statements[$value['t']]['high'] ||
                (float)$value['closed'] !== (float)$statements[$value['t']]['closed']
                // (float)$value['income'] !== (float)$statements[$value['t']]['income'] ||
                // (float)$value['expend'] !== (float)$statements[$value['t']]['expend']
            ) {
                $output->writeln('Found Err Statement Data!');
                $output->writeln('Calculate Data:');
                $output->writeln(var_export([$value['t'] => $statements[$value['t']]], 1));
                $output->writeln('Statements Data:');
                $output->writeln(var_export($value, 1));
                break;
            }
        }

        $output->writeln('Done!');
        $output->writeln("Calculate Loan Sum: {$loanSum}");
        $output->writeln("DB Loan Sum: " . Loan::sum('money'));
    }
}