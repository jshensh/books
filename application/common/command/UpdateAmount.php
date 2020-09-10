<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use app\common\model\Transactions;

class UpdateAmount extends Command
{
    protected function configure()
    {
        $this->setName('UpdateAmount')
            ->setDescription('更新数据表 transactions 余额列');
    }

    protected function execute(Input $input, Output $output)
    {
        $amount = [];
        Transactions::chunk(100, function ($rows) use (&$amount, $output) {
            $output->writeln('Updating amount ' . $rows[0]->id . ' - ' . end($rows)->id);
            foreach ($rows as $row) {
                if (!isset($amount[$row->transactmode_id])) {
                    $amount[$row->transactmode_id] = [0, 0];
                }
                $amount[$row->transactmode_id][0] = bcadd($amount[$row->transactmode_id][0], $row->money, 2);
                $amount[$row->transactmode_id][1] += 1;
                $row->amount = $amount[$row->transactmode_id][0];
                $row->save();
            }
        });
        $output->writeln('Done!');
        // sort($amount);
        $output->writeln(var_export($amount, 1));
        $output->writeln(var_export(Transactions::group('transactmode_id')->field(['transactmode_id'])->column('count(id) as c', 'transactmode_id'), 1));
    }
}