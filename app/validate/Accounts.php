<?php
namespace app\validate;

use think\Validate;

class Accounts extends Validate
{
    protected $rule = [
        'txt'          =>  ['max:255', 'token'],
        'name'         =>  ['max:60'],
        'money'        =>  ['require', 'float'],
        'transactMode' =>  ['require'],
    ];
}