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

    //todo 无用代码，建立连接肯定有uid，这里是因为没有账号系统
    public function getSelfUid()
    {
        $uid = $this->request->session()->get('uid', '');

        if (empty($uid)) {
            throw new \Exception("非法的请求");
        }

        return $this->success($uid);
    }

    //上线处理
    //todo 无用代码，关联uid和用户登录使用的用户名，这里是因为没有账号系统
    public function pushOnline()
    {
        $uid = $this->request->session()->get('uid', '');
        $name = $this->request->session()->get('name', '');

        if (empty($uid)) {
            throw new \Exception("非法的请求");
        }

        $this->ctx->CometRpc->pushOnline($uid, $name);

        return $this->success();
    }

    public function getGroupOnlineUsers()
    {
        $uid = $this->request->session()->get('uid', '');

        if (empty($uid)) {
            throw new \Exception("非法的请求");
        }

        $group = $this->request->input('group', 1);
        return $this->success($this->ctx->CometRpc->getGroupOnlineUsers($group));
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
