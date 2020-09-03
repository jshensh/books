<?php
namespace app\index\validate;

use think\Validate;

class Index extends Validate
{
    protected $rule = [
        'txt'          =>  ['max:255', 'token'],
        'name'         =>  ['max:60'],
        'money'        =>  ['require', 'float'],
        'transactMode' =>  ['require'],
    ];
}