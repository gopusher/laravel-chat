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
    private $redisKey = 'im:';

    private function getCometServerKey()
    {
        return $this->redisKey . 'cometServers';
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
}
