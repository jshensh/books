<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        \app\command\ResetPassword::class,
        \app\command\ResetLoanShareKey::class,
        \app\command\FixData::class
    ],
];
