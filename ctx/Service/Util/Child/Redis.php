<?php

namespace Ctx\Service\Util\Child;

use Ctx\Basic\Ctx;
use Predis\Client as RedisClient;

/**
 * 框架存储辅助类
 *
 */
class Redis extends Ctx
{
    /**
     * 构造函数
     */
    public function __construct()
    {
    }

    /**
     * redis实例
     */
    private static $redisObj = array();

    /**
     * 加载Redis对象
     *
     * @param string $redis
     * @return mixed
     */
    public function get($redis = 'default')
    {
        if (! isset(self::$redisObj[$redis])) {
            $config = config('database.redis.' . $redis);

            self::$redisObj[$redis] = new RedisClient([
                'scheme'    => 'tcp',
                'host'      => $config['host'],
                'port'      => $config['port'],
                'timeout'   => 5,
            ]);
        }

        return self::$redisObj[$redis];
    }

    public function __destruct()
    {
        foreach (self::$redisObj as $redis) {
            /** @var $redis RedisClient */
            $redis->disconnect();
        }
    }
}
