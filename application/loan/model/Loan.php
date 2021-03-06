<?php
namespace app\loan\model;

use think\Model;

class Loan extends Model
{
    // protected $pk = 'k';
    public function getTAttr($value)
    {
        return date("Y-m-d H:i:s", $value);
    }

    public function transactmode()
    {
        return $this->belongsTo('Transactmode')->field(['id', 'name as transactmode_name'])->bind('transactmode_name');
    }
}