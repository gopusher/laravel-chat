<?php

namespace Ctx\Service\CometRpc;

use Ctx\Basic\Ctx as BasicCtx;

/**
 * 模块接口声明文件
 * 备注：文件命名跟模块中的其他类不同，因为要防止模块声明类只能被实例化一次
 * 也就是只能用ctx->模块 来实例化，不能用loadC来实例化更多
 */
class Ctx extends BasicCtx
{
    /**
     * @return string
     */
    public function getCometServerKey()
    {
        return $this->imRedisKey . 'cometServers';
    }

    private $allCometServers = null;

    private function getAllCometServers()
    {
        if (! is_null($this->allCometServers)) {
            return $this->allCometServers;
        }

        //parse env
        $num = intval(env('COMET_SERVER_NUM'));
        if ($num < 1) {
            $this->allCometServers = [];
            return [];
        }

        for ($node = 1; $node <= $num; $node++) {
            $nodeAddr = env('COMET_RPC_ADDR_' . $node) . ':' . env('COMET_RPC_PORT_' . $node);

            $this->allCometServers[$nodeAddr] = [
                'token'     => env('COMET_RPC_TOKEN_' . $node),
                'ws_host'   => env('WEBSOCKET_ADDR_' . $node),
            ];
        }

        return $this->allCometServers;
    }

    public function getCometServersByNodes($nodes)
    {
        $ret = [];
        $allCometServers = $this->getAllCometServers();
        foreach ($nodes as $node) {
            $ret[$node] = $allCometServers[$node];
        }

        return $ret;
    }

    public function getAllAvailableCometServers()
    {
        return $this->ctx->Util->redis()->hgetall($this->getCometServerKey());
    }

    /**
     * Comet 服务上线通知
     * @see https://github.com/Gopusher/comet/wiki/notification-zh#comet-%E6%9C%8D%E5%8A%A1%E4%B8%8A%E7%BA%BF%E9%80%9A%E7%9F%A5
     *
     * @todo 消息去重, 根据 revision (消息id, 唯一) 消息可能重发(多次重复通知)
     */
    public function addCometServer($node, $revision)
    {
        // $node如: /comet/192.168.3.165:8901, 消息总是 /comet/ 开头
        $node = substr($node, 7);
        $this->ctx->Util->redis()->hset($this->getCometServerKey(), $node, 1);

        return true;
    }

    /**
     * Comet 服务下线通知
     * @see https://github.com/Gopusher/comet/wiki/notification-zh#comet-%E6%9C%8D%E5%8A%A1%E4%B8%8B%E7%BA%BF%E9%80%9A%E7%9F%A5
     *
     * @todo 消息去重, 根据 revision (消息id, 唯一) 消息可能重发(多次重复通知)
     *
     */
    public function removeCometServer($node, $revision)
    {
        // $node如: /comet/192.168.3.165:8901, 消息总是 /comet/ 开头
        $node = substr($node, 7);
        $this->ctx->Util->redis()->hdel($this->getCometServerKey(), [$node]);

        //todo 移除 服务器 连接的用户的在线状态

        return true;
    }

    //校验 uid 和 token 和 conn_id 当前comet机器的addr 都需要传递，防止伪造或则连接非指定的机器
    //todo 同一个账号不能多次登录
    public function checkToken($connId, $token, $clientInfo, $rpcAddr)
    {
        return $this->ctx->Im->checkToken($connId, $token, $clientInfo, $rpcAddr);
    }
}
