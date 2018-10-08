<?php

namespace Ctx\Service\CometRpc;

use Ctx\Basic\Ctx as BasicCtx;
use Illuminate\Support\Arr;

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

        //todo 其实可以不用env来管理服务器配置，采用redis的方式，然后admin后台也方便管理服务器信息
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

    public function getRpcToken($host, $port)
    {
        $node = $host . ':' . $port;
        $allCometServers = $this->getAllCometServers();
        if (isset($allCometServers[$node])) {
            return $allCometServers[$node]['token'];
        }

        throw new \Exception('获取comet rpc token失败');
    }

    public function getCometServersByNodes($nodes)
    {
        $ret = [];
        $allCometServers = $this->getAllCometServers();
        foreach ($nodes as $node) {
            if (isset($allCometServers[$node])) {
                $ret[$node] = $allCometServers[$node];
            }
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

        //移除 服务器 连接的用户的在线状态
        //[$connId => $uid]
        //可以提供批量下线方法，合并 redis 操作，部分redis命令支持批量
        foreach ($this->ctx->Util->redis()->hgetall($this->getComet2ConnKey($node)) as $connId => $uid) {
            $this->userOffline($uid, $connId, $node);
        }

        return true;
    }

    //校验 uid 和 token 和 conn_id 当前comet机器的addr 都需要传递，防止伪造或则连接非指定的机器
    //todo 同一个账号不能多次登录
    public function checkToken($connId, $token, $clientInfo, $rpcAddr)
    {
        return $this->ctx->Im->checkToken($connId, $token, $clientInfo, $rpcAddr);
    }

    private function parseClientInfo($clientInfo)
    {
        try {
            $clientInfo = $this->ctx->Util->json_decode($clientInfo);
            return Arr::get($clientInfo, 'uid');
        } catch (\Exception $e) {
            throw new \Exception('解析clientInfo出错 >> ' . $e->getMessage());
        }
    }

    //在线用户
    private function getOnlineKey()
    {
        return $this->imRedisKey. 'online:map';
    }

    /**
     * client 上线 回调
     *
     * 在线状态上报处理
     */
    public function online($connId, $clientInfo, $rpcAddr)
    {
        $uid = $this->parseClientInfo($clientInfo);
        $onlineKey = $this->getOnlineKey();

        //todo 加锁 防止并发的时候出错
        //添加用户 连接 到 所在的 机器
        $userConnInfo = $this->ctx->Util->redis()->hget($onlineKey, $uid);
        if (! empty($userConnInfo)) { //已经存在其他连接
            try {
                $userConnInfo = $this->ctx->Util->json_decode($userConnInfo);
                $userConnInfo[$connId] = $rpcAddr;
                $this->ctx->Util->redis()->hset($onlineKey, $uid, json_encode($userConnInfo));
            } catch (\Exception $e) {
                $this->ctx->Util->redis()->hset($onlineKey, $uid, json_encode([
                    $connId => $rpcAddr,
                ]));
            }
        } else {
            $this->ctx->Util->redis()->hset($onlineKey, $uid, json_encode([
                $connId => $rpcAddr,
            ]));
        }

        //映射 机器 上存在的 连接
        $this->ctx->Util->redis()->hset($this->getComet2ConnKey($rpcAddr), $connId, $uid);

        //todo 这里是测试代码：固定加入群组，实际情况是群组功能单独的api
        $this->ctx->Im->joinGroup(1, $uid);

        return true;
    }

    //todo 后期hash过大可以按照id hash 拆分
    //映射 机器 上存在的 连接
    private function getComet2ConnKey($rpcAddr)
    {
        return $this->imRedisKey. 'comet:map:' . $rpcAddr;
    }

    private function userOffline($uid, $connId, $rpcAddr)
    {
        //其实可以通过 $uid 获取 $connId
        $onlineKey = $this->getOnlineKey();

        //todo 加锁 防止并发的时候出错
        //添加用户 连接 从 所在的 机器
        $userConnInfo = $this->ctx->Util->redis()->hget($onlineKey, $uid);
        if (! empty($userConnInfo)) { //已经存在其他连接
            try {
                $userConnInfo = $this->ctx->Util->json_decode($userConnInfo);
                unset($userConnInfo[$connId]);
                if (! empty($userConnInfo)) {
                    $this->ctx->Util->redis()->hset($onlineKey, $uid, json_encode($userConnInfo));
                } else {
                    $this->ctx->Util->redis()->hdel($onlineKey, [$uid]);

                    //todo 这里是测试代码：固定加入群组，实际情况是群组功能单独的api
                    $this->ctx->Im->leaveGroup(1, $uid);

                    //todo 无用代码，这里是因为没有账号系统，实际开发中不需要，因为在线状态已经能通过 getOnlineKey 获取到
                    $this->ctx->Util->redis()->hdel($this->getOnlineNicknameKey(), [$uid]);
                }
            } catch (\Exception $e) {
                $this->ctx->Util->redis()->hdel($onlineKey, [$uid]);
            }
        }

        //映射 机器 上存在的 连接
        $this->ctx->Util->redis()->hdel($this->getComet2ConnKey($rpcAddr), [$connId]);

        return true;
    }

    /**
     * client 离线 回调
     *
     * 离线状态上报处理
     */
    public function offline($connId, $clientInfo, $rpcAddr)
    {
        $uid = $this->parseClientInfo($clientInfo);

        return $this->userOffline($uid, $connId, $rpcAddr);
    }

    private function getOnlineNicknameKey()
    {
        return $this->imRedisKey . 'nickname:map';
    }

    //todo 无用代码，关联uid和用户登录使用的用户名，这里是因为没有账号系统
    public function pushOnline($uid, $name)
    {
        $this->ctx->Util->redis()->hset($this->getOnlineNicknameKey(), $uid, $name);
    }

    public function getGroupOnlineUsers($group)
    {
        $uids = $this->ctx->Im->getGroupUsers($group);

        $nicknames = $this->ctx->Util->redis()->hgetall($this->getOnlineNicknameKey());

        $ret = [];
        foreach ($uids as $uid) {
            $ret[$uid] = $nicknames[$uid];
        }

        return $ret;
    }

    /**
     * @param $uidArr
     * @return array 如:
     *      array(
     *          'uid'   => json_encode(array(
     *              'conn_id_1' => comet1,
     *          ))
     *      )
     */
    public function getUsersConnections($uidArr)
    {
        if (empty($uidArr)) {
            return [];
        }

        $onlineKey = $this->getOnlineKey();
        $connections = $this->ctx->Util->redis()->hmget($onlineKey, ...$uidArr);

        return array_combine($uidArr, $connections);
    }
}
