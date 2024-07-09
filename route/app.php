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
    
    Route::get('', 'Index/index');
    Route::post('', 'Index/index');

    Route::get('currency$', 'Currency/index');
    Route::post('currency$', 'Currency/index');
    
})->middleware(app\middleware\Auth::class);

Route::rule('login', 'login/index');