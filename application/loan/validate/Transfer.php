<?php
namespace app\loan\validate;

use think\Validate;

class Transfer extends Validate
{
    protected $rule = [
        'transferFrom' =>  ['require', 'max:60'],
        'transferTo'   =>  ['require', 'max:60', 'different:transferFrom'],
        'txt'          =>  ['max:255', 'token'],
        'money'        =>  ['require', 'float'],
    ];
}