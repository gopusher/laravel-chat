<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::namespace('Im')->group(function () {
    //首页
    Route::get('/', 'IndexController@index');

    Route::prefix('/im/index')->group(function () {
        Route::get('/test', 'IndexController@test');

        //登录
        Route::post('/register', 'IndexController@register');

        //comet 接入层rpc入口
        Route::post('/rpc', 'RpcController@rpc');
    });
});
