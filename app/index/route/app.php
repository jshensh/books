<?php
use think\facade\Route;

Route::group('', function(){
    Route::get('', 'index/index/index');
    Route::rule('index', 'index/index/index');
    // Route::rule('chart$', 'index/chart/index');
    // Route::rule('transactions$', 'index/transactions/index');
    // Route::rule('loan/detail/:name', 'loan/index/detail')->pattern(['name' => '.+']);
    // Route::rule('loan/transfer$', 'loan/index/transfer');
    // Route::rule('loan$', 'loan/index/index');
})->middleware(app\common\middleware\Auth::class);