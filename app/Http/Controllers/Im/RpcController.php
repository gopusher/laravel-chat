<?php

namespace App\Http\Controllers\Im;

use App\Http\Controllers\Controller;

class RpcController extends Controller
{
    public function rpc()
    {
        \Log::info('rpc:' . var_export($this->request->all(), true));


        $agent = config('im.rpc_user_agent');

        $data = $this->request->all();

        if ($this->request->server->get('HTTP_USER_AGENT') == $agent &&
            isset($data['class'], $data['method']) && $data['class'] == 'Im'
        ) {
            $method = $data['method'];
            $args = isset($data['args']) ? $data['args'] : array();

            header('Content-Type: application/json; charset=utf-8');
            $data = call_user_func_array(array($this->ctx->CometRpc, $method), $args);

            return $this->success($data);
        } else {
            throw new \Exception("非法的请求");
        }
    }
}
