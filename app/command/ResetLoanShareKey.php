<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class ResetLoanShareKey extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('ResetLoanShareKey')
            ->setDescription('重置账单分享密钥');
    }

    protected function arr2ini($arr) {
        $ini = '';
        foreach ($arr as $key => $value) {
            if (!is_array($value)) {
                $ini .= "{$key} = {$value}\n";
            } else {
                $ini .= "\n[{$key}]\n" . $this->arr2ini($value);
            }
        }
        return $ini;
    }

    protected function execute(Input $input, Output $output)
    {
        $iniArr = parse_ini_file(app()->getRootPath() . '/.env', true, INI_SCANNER_RAW);
        if (!isset($iniArr['system'])) {
            $iniArr['system'] = [];
        }
        $iniArr['system']['LOANSHARE_KEY'] = bin2hex(random_bytes(16));

        file_put_contents(app()->getRootPath() . '/.env', $this->arr2ini($iniArr));

        $output->writeln('LoanShareKey is changed');
    }
}
