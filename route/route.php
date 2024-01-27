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

Route::rule('', '/index');

Route::group('', function(){
    Route::rule('index$', 'index/index/index');
    Route::rule('chart$', 'index/chart/index');
    Route::rule('transactions$', 'index/transactions/index');
    Route::rule('loan/detail/:name', 'loan/index/detail')->pattern(['name' => '.+']);
    Route::rule('loan/transfer$', 'loan/index/transfer');
    Route::rule('loan$', 'loan/index/index');
})->middleware('Auth');

Route::rule('login', 'login/index/index');
Route::rule('loanShare/:token', 'loan/share/index')->pattern(['token' => '.+']);

return [

];
