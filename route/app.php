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
]);

Route::group(function() {
    
    Route::get('', 'Index/index');
    Route::post('', 'Index/index');

    Route::get('currency$', 'Currency/index');
    Route::post('currency$', 'Currency/index');

    Route::get('loan$', 'Loan/index');

    Route::group(function() {

        Route::get('transactmode/:currency', 'Transactmode/index');
        Route::post('transactmode/:currency', 'Transactmode/index');
        
        Route::get('accounts/:currency/$', 'Accounts/index');
        Route::post('accounts/:currency/$', 'Accounts/index');
        Route::get('accounts/:currency/chart$', 'Accounts/chart');
        Route::get('accounts/:currency/transactions$', 'Accounts/transactions');
        Route::get('accounts/:currency/loan/:name', 'Loan/detail')->pattern(['name' => '.+']);

    })->middleware(app\middleware\CheckCurrencyExists::class);
    
})->middleware(app\middleware\Auth::class);

Route::rule('login', 'login/index');