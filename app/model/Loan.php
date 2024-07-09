<?php
namespace app\model;

use think\Model;

class Loan extends Model
{
    // protected $pk = 'k';

    public function getTAttr($value)
    {
        return date("Y-m-d H:i:s", $value);
    }
}