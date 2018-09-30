<?php

namespace Ctx\Service\Im;

use Ctx\Basic\Ctx as BasicCtx;
use Ctx\Basic\Exception;

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

    public function init()
    {
        $this->redis = $this->ctx->Util->redis();
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
}
