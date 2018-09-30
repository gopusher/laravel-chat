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
        $uid = $this->request->session()->get('uid', '');

        if (! empty($uid)) {
            return $this->success(); //已经注册成功过，跳过注册逻辑.
        }

        $name = $this->request->input('name');

        if (empty($name)) {
            throw new \Exception('名字不能为空');
        }

        if (mb_strlen($name) > 16) {
            throw new \Exception('名字最大为16个字');
        }

        $uid = uniqid();
        $this->request->session()->put('uid', $uid);
        $this->request->session()->put('name', $name);

        return $this->success();
    }

    public function connectInfo()
    {
        $uid = $this->request->session()->get('uid', '');
        if (empty($uid)) {
            throw new \Exception("非法的请求");
        }

        return $this->success([
            'wsHost'    => $this->ctx->Im->getConnectInfo($uid),
        ]);
    }

    public function test()
    {
        $uid = $this->request->session()->get('uid', '');
        if (empty($uid)) {
            throw new \Exception("非法的请求");
        }

        $data = $this->ctx->Im->getConnectInfo($uid);

        return $this->success($data);
    }
}
