<?php
namespace app\index\model;

use think\Model;

class Transactions extends Model
{
    // protected $pk = 'k';
    public function getTAttr($value)
    {
        return date("Y-m-d H:i:s", $value);
    }
}