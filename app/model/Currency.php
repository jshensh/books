<?php
namespace app\model;

use think\Model;

class Currency extends Model
{
    protected $pk = 'code';

    public function setCodeAttr($value)
    {
        return strtoupper($value);
    }
}