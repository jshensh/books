<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::group(function() {
    Route::get('', 'index/index/index');
    Route::post('', 'index/index/index');
    Route::get('currency$', 'index/index/Currency/index');
})->middleware(app\middleware\Auth::class);

Route::rule('login', 'login/index');