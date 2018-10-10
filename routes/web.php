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
        //comet 接入层rpc入口
        //@see https://github.com/Gopusher/comet/wiki/notification-zh
        Route::post('/rpc', 'RpcController@rpc');

        //登录
        Route::post('/register', 'IndexController@register');
        Route::get('/connectInfo', 'IndexController@connectInfo');
        //建立comet连接后获取uid
        Route::get('/getSelfUid', 'IndexController@getSelfUid');
        //关联uid和用户登录使用的用户名
        Route::post('/pushOnline', 'IndexController@pushOnline');
        //获取当前在线用户列表
        Route::get('/getGroupOnlineUsers', 'IndexController@getGroupOnlineUsers');

        //发送给指定uid
        Route::post('/sendToUser', 'IndexController@sendToUser');
        //发送给指定群
        Route::post('/sendToGroup', 'IndexController@sendToGroup');
    });
});
