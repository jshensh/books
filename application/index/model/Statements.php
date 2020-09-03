<?php
namespace app\index\model;

use think\Model;

class Statements extends Model
{
    // protected $pk = 'k';
    public function getTAttr($value)
    {
        return date("Y-m-d", $value);
    }

    public function setTAttr($value)
    {
        return strtotime($value);
    }
}