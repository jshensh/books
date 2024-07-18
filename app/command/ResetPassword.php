<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class ResetPassword extends Command
{
    protected $sttyStyle;
    
    protected function configure()
    {
        // 指令配置
        $this->setName('ResetPassword')
            ->setDescription('重置后台密码');
    }

    protected function toggleStty()
    {
        if ($this->sttyStyle) {
            shell_exec("stty {$this->sttyStyle}");
            $this->sttyStyle = '';
        } else {
            $this->sttyStyle = shell_exec('stty -g');
            shell_exec('stty -echo');
        }
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
        $password = '';
        $tried = 0;
        while ($tried < 3 && !$password) {
            $this->toggleStty();
            $output->write('New Password: ');
            $password = trim(fgets(STDIN));
            $this->toggleStty();
            $output->newLine();
            if (!$password) {
                $output->writeln('Bad password: too short');
                $tried++;
            }
        }

        if (!$password) {
            $output->writeln('passwd: password is unchanged');
            exit();
        }

        $this->toggleStty();
        $output->write('Retype password: ');
        $password2 = trim(fgets(STDIN));
        $this->toggleStty();
        $output->newLine();

        if ($password !== $password2) {
            $output->writeln('Passwords don\'t match');
            $output->writeln('passwd: password is unchanged');
            exit();
        }

        $iniArr = parse_ini_file(app()->getRootPath() . '/.env', true, INI_SCANNER_RAW);
        if (!isset($iniArr['system'])) {
            $iniArr['system'] = [];
        }
        $iniArr['system']['ADMIN_PASS'] = password_hash($password, PASSWORD_BCRYPT);

        file_put_contents(app()->getRootPath() . '/.env', $this->arr2ini($iniArr));

        array_map('unlink', glob(app()->getRootPath() . '/runtime/session/sess_*'));

        $output->writeln('passwd: password is changed');
    }
}
