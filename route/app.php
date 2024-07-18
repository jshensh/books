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

Route::pattern([
    'currency' => '[a-zA-Z\(\)]+',
    'token'    => '[^/]+',
]);

Route::group(function() {
    
    Route::rule('', 'Index/index');
    Route::rule('currency$', 'Currency/index');
    Route::get('loan$', 'Loan/index');
    Route::post('loan/share$', 'Loan/share');
    Route::rule('transfer$', 'Transfer/index');
    Route::rule('transfer/request$', 'Transfer/request');

    Route::group(function() {

        Route::rule('transactmode/:currency', 'Transactmode/index');
        Route::rule('accounts/:currency/loan/:name', 'Loan/detail')->pattern(['name' => '.+']);
        Route::get('accounts/:currency/chart$', 'Accounts/chart');
        Route::get('accounts/:currency/transactions$', 'Accounts/transactions');
        Route::rule('accounts/:currency/', 'Accounts/index');

    })->middleware(app\middleware\CheckCurrencyExists::class);
    
})->middleware(app\middleware\Auth::class);

Route::rule('login', 'login/index');
Route::rule('share/:token/transfer', 'loan/transfer');
Route::get('share/:token/:currency', 'loan/detail');
Route::get('share/:token', 'loan/index');