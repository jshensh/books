<?php
namespace app\validate;

use think\Validate;

class Transactmode extends Validate
{
    protected $rule = [
        'id|自增编号'         => ['integer', 'between' => '0,99999'],
        'name|显示名称'       => ['require', 'length' => '1,30'],
        'topup|充值来源'      => ['require', 'integer', 'between' => '0,99999'],
        'withdrawal|提现去向' => ['require', 'integer', 'between' => '0,99999'],
        'sk|收款支持'         => ['require', 'integer', 'between' => '0,1'],
        'pay|支付支持'        => ['require', 'integer', 'between' => '0,1'],
        'is_shown|列表显示'   => ['require', 'integer', 'between' => '0,1'],
        'sortid|排序权重'     => ['require', 'integer', 'between' => '0,99999'],
    ];
}