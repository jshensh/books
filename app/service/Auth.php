<?php
namespace app\service;

use think\facade\Session;
use think\facade\Config;

class Auth
{
    public static function isLogined()
    {
        return Session::has('logined');
    }

    public static function login($pass)
    {
        $configPass = Config::get('system.admin_pass');
        if (!$configPass) {
            throw new \think\Exception();
        }

        if ($pass === $configPass) {
            Session::set('logined', 1);
            return true;
        }

        return false;
    }

    public static function logout($pass)
    {
        Session::delete('logined');
        return true;
    }
}