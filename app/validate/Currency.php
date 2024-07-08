<?php
namespace app\validate;

use think\Validate;

class Currency extends Validate
{
    protected $rule = [
        'code|货币代码'      => ['require', 'length' => '3,10', 'regex' => '/^[a-z0-9\/]+$/i'],
        'name|货币名称'      => ['require', 'length' => '1,10'],
        'scale|小数位数'     => ['require', 'integer', 'between' => '0,8'],
        'symbol|货币符号'    => ['length' => '0,5'],
        'unit_name|货币单位' => ['length' => '0,5'],
    ];
}