<?php

namespace Ctx\Service\Im;

use Ctx\Basic\Ctx as BasicCtx;
use Ctx\Basic\Exception;
use Ctx\Service\Im\Child\JsonRPC;

/**
 * 模块接口声明文件
 * 备注：文件命名跟模块中的其他类不同，因为要防止模块声明类只能被实例化一次
 * 也就是只能用ctx->模块 来实例化，不能用loadC来实例化更多
 */
class Ctx extends BasicCtx
{
    /**
     * @var \Predis\Client
     */
    private $redis;

    /**
     * @var JsonRPC
     */
    private $rpcClient;

    public function init()
    {
        $this->redis = $this->ctx->Util->redis();

        $this->rpcClient = $this->loadC('JsonRPC');
    }

    //TODO 按照一定策略获取机器，如同一个群组的在一个机器或则cpu空闲的机器
    //todo 增加逻辑同一个账号同一个平台类型不能多次登录
    public function getConnectInfo($uid)
    {
        /**
         * @var array $allCometServers like ["192.168.3.142:8901" => "1", "192.168.3.142:8902" => "1"]
         */
        $allCometServers = $this->ctx->CometRpc->getAllAvailableCometServers();
        if (empty($allCometServers)) {
            throw new Exception('暂无可用的comet服务');
        }
        $rpcAddr = array_rand($allCometServers);

        $cometServers = $this->ctx->CometRpc->getCometServersByNodes([$rpcAddr]);
        //ip 带了 . 用array_get 有问题
        if (! isset($cometServers[$rpcAddr], $cometServers[$rpcAddr]['ws_host'])) {
            throw new Exception($rpcAddr . '缺少env配置');
        }
        $cometWsHost = $cometServers[$rpcAddr]['ws_host'];

        $connId = uniqid(); //todo 需要修改 需要生成唯一的 id

        $clientInfo = json_encode([
            'uid'       => $uid,
        ]);
        $token = $this->getTokenInfo($connId, $rpcAddr, $clientInfo);

        $this->redis->set(
            $this->getAuthKey($connId),
            $token,
            'Ex',
            60 //60s过期
        );

        // 生成规则
        // @see https://github.com/Gopusher/comet/wiki/notification-zh#comet-%E6%9C%8D%E5%8A%A1%E9%80%9A%E7%9F%A5
        return sprintf('%s/ws?c=%s&t=%s&i=%s', $cometWsHost, $connId, $token, rawurlencode($clientInfo));
    }

    private function getTokenInfo($connId, $rpcAddr, $clientInfo)
    {
        //按照一定规则随便生成token
        //校验是否让登录到指定的机器上
        return md5(json_encode([$connId, $rpcAddr, $clientInfo]));
    }

    //校验 uid 和 token 和 conn_id 当前comet机器的addr 都需要传递，防止伪造或则连接非指定的机器
    //todo 同一个账号不能多次登录
    public function checkToken($connId, $token, $clientInfo, $rpcAddr)
    {
        $redisKey = $this->getAuthKey($connId);
        $t = $this->redis->get($redisKey);

        if ($t == $this->getTokenInfo($connId, $rpcAddr, $clientInfo) && $token == $t) {
            $this->redis->del([$redisKey]);
            return true;
        }

        throw new \Exception('校验失败');
    }

    private function getAuthKey($connId)
    {
        return $this->imRedisKey . 'auth:' . $connId;
    }

    //在线用户
    private function getGroupKey($group)
    {
        return $this->imRedisKey. 'group:map:' . $group;
    }

    //加入讨论组
    public function joinGroup($group, $uid)
    {
        $redisKey = $this->getGroupKey($group);
        $this->redis->hset($redisKey, $uid, 1);
    }

    //离开讨论组
    public function leaveGroup($group, $uid)
    {
        $redisKey = $this->getGroupKey($group);
        $this->redis->hdel($redisKey, [$uid]);
    }

    //获取讨论组成员
    //todo 这里是测试代码：固定加入群组，实际情况是群组功能单独的api
    public function getGroupUsers($group)
    {
        $redisKey = $this->getGroupKey($group);
        return array_keys($this->redis->hgetall($redisKey));
    }

    //消息类型
    const MESSAGE_TYPE_PERSON = 'person';
    const MESSAGE_TYPE_GROUP = 'group';

    const MESSAGE_CONTENT_TYPE_TEXT = 'text';
    const MESSAGE_CONTENT_TYPE_ONLINE = 'online';

    public function sendToUser($from, $to, $msg)
    {
        //私聊需要双写
        $connections = $this->getUsersConnectionsGroupByCometAddr([$from, $to]);

        foreach ($connections as $addr => $connIds) {
            list($host, $port) = explode(':', $addr);

            $msgBody = json_encode([
                'from'          => $from,
                'to'            => $to,
                'type'          => self::MESSAGE_TYPE_PERSON,
                'contentType'   => self::MESSAGE_CONTENT_TYPE_TEXT,
                'content'       => $msg,
            ]);
            //todo 判断发送结果
            $rpcToken = $this->ctx->CometRpc->getRpcToken($host, $port);
            $ret = $this->rpcClient->SendToConnections($host, $port, $rpcToken, $connIds, $msgBody);
            \Log::error(var_export($ret, true));
        }

        return true;
    }

    public function sendToGroup($from, $to, $msg)
    {
        $uids = $this->getGroupUsers($to);
        $connections = $this->getUsersConnectionsGroupByCometAddr((array) $uids);

        foreach ($connections as $addr => $connIds) {
            list($host, $port) = explode(':', $addr);

            $msgBody = json_encode([
                'from'          => $from,
                'to'            => $to,
                'type'          => self::MESSAGE_TYPE_GROUP,
                'contentType'   => self::MESSAGE_CONTENT_TYPE_TEXT,
                'content'       => $msg,
            ]);
            //todo 判断发送结果
            $rpcToken = $this->ctx->CometRpc->getRpcToken($host, $port);
            $this->rpcClient->SendToConnections($host, $port, $rpcToken, $connIds, $msgBody);
        }

        return true;
    }

    private function getUsersConnectionsGroupByCometAddr(array $uidArr)
    {
        $ret = $this->ctx->CometRpc->getUsersConnections($uidArr);

        $connections = [];
        foreach ($ret as $uid => $row) {
            if (empty($row)) { //uid 不存在连接
                //todo 后续增加更多处理
                continue;
            }

            foreach ($this->ctx->Util->json_decode($row) as $connId => $addr) {
                $connections[$addr][] = $connId;
            }
        }

        return $connections;
    }
}
