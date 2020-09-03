<?php
namespace app\index\model;

use think\Model;

class Loan extends Model
{
    // protected $pk = 'k';
    public function getTAttr($value)
    {
        return date("Y-m-d", $value);
    }
}