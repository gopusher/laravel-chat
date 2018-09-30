<?php

namespace Ctx\Service\Util;

use Ctx\Basic\Ctx as BasicCtx;

/**
 * 模块接口声明文件
 * 备注：文件命名跟模块中的其他类不同，因为要防止模块声明类只能被实例化一次
 * 也就是只能用ctx->模块 来实例化，不能用loadC来实例化更多
 */
class Ctx extends BasicCtx
{
    /**
     * redis 单例
     *
     * @var \Ctx\Service\Util\Child\Redis
     */
    private $redis;

    public function init()
    {
        $this->redis = $this->loadC('Redis');
    }

    /**
     * @param string $redis
     *
     * @return \Predis\Client
     */
    public function redis($redis = 'default')
    {
        return $this->redis->get($redis);
    }
}
