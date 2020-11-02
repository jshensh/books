<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

use app\common\service\Data as DataService;

class Insert extends Command
{
    protected function configure()
    {
        $this->setName('Insert')
            ->addArgument('outTransactMode', Argument::REQUIRED, "支出款项交易方式 ID，纯收入账目请填写 0")
            ->addArgument('inTransactMode', Argument::REQUIRED, "收入款项交易方式 ID，纯支出账目请填写 0")
            ->addArgument('txt', Argument::REQUIRED, "交易备注")
            ->addArgument('money', Argument::REQUIRED, "交易金额")
            ->addOption('loan', null, Option::VALUE_OPTIONAL, '借/贷款人姓名')
            ->setDescription('命令行记账工具');
    }

    protected function execute(Input $input, Output $output)
    {
        $outTransactMode = $input->getArgument('outTransactMode');
        $inTransactMode = $input->getArgument('inTransactMode');
        $txt = $input->getArgument('txt');
        $money = $input->getArgument('money');
        $loan = $input->getOption('loan');
        
        $result = (new DataService())->insertNew("{$outTransactMode}_{$inTransactMode}", $txt, $money, $loan);

        if (!$result) {
            $output->error('数据插入失败');
            return false;
        }
        $output->info('数据插入成功');
        print_r($result);
    }
}