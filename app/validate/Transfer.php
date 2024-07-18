<?php
namespace app\validate;

use think\Validate;

class Transfer extends Validate
{
    protected $rule = [
        'txt'      =>  ['max:255'],
        'name'     =>  ['max:60'],
        'money'    =>  ['require', 'float'],
        'mode'     =>  ['require'],
        'isFrozen' =>  ['require', 'in:0,1'],
    ];
}