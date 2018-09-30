<?php

namespace App\Http\Controllers\Im;

use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    /**
     * 首页
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $staticHost = config('im.static_host');
        $apiHost = config('im.api_host');

        $name = $this->request->session()->get('name', '');
        $uid = $this->request->session()->get('uid', '');

        return view(
            'im/index',
            compact('staticHost', 'apiHost', 'name', 'uid')
        );
    }

    /**
     * 登录,注册 uid
     */
    public function register()
    {
        if (! empty($_SESSION['uid'])) {
            return $this->success(); //已经注册成功过，跳过注册逻辑.
        }
        if (empty($_POST['name'])) {
            throw new \Exception('名字不能为空');
        }

        $name = $_POST['name'];
        if (mb_strlen($name) > 16) {
            throw new \Exception('名字最大为16个字');
        }

        $uid = uniqid();
        $this->request->session()->put('uid', $uid);
        $this->request->session()->put('name', $name);

        return $this->success();
    }

    public function test()
    {
        return $this->success();
    }
}
