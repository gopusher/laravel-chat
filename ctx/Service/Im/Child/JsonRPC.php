<?php

namespace Ctx\Service\Im\Child;

class JsonRPC
{
    private static $obj = [];

    private function getConnect($host, $port)
    {
        $key = $host . ':' . $port;
        if (!isset(self::$obj[$key])) {
            $conn = fsockopen($host, $port, $errno, $errstr, 3);
            if (! $conn) {
                throw new \Exception('建立连接失败');
            }
            self::$obj[$key] = $conn;
        }
        return self::$obj[$key];
    }

    public function execute($host, $port, $data) {
        $conn = $this->getConnect($host, $port);

        if (! $conn) {
            throw new \Exception('连接不存在');
        }
        $err = fwrite($conn, json_encode(array_merge($data, ['id' => 0])) . "\n");

        if ($err === false) {
            throw new \Exception('调用方法失败' . __METHOD__);
        }

        stream_set_timeout($conn, 0, 3000);
        $line = fgets($conn);
        if ($line === false) {
            return NULL;
        }
        return json_decode($line,true);
    }

    /**
     * @see https://github.com/Gopusher/comet/wiki/api-zh#%E6%B6%88%E6%81%AF%E5%8F%91%E9%80%81%E6%8E%A5%E5%8F%A3
     *
     * @param $host
     * @param $port
     * @param $token
     * @param array $connections
     * @param $msg
     * @return mixed|null
     * @throws \Exception
     */
    public function SendToConnections($host, $port, $token, array $connections, $msg)
    {
        $data = array(
            'method' => "Server.SendToConnections",
            'params' => [[
                'connections'   => array_values(array_unique($connections)),
                'msg'           => $msg,
                'token'         => $token
            ]],
        );

        return $this->execute($host, $port, $data);
    }

    /**
     * https://github.com/Gopusher/comet/wiki/api-zh#%E4%B8%8B%E7%BA%BF%E5%AE%A2%E6%88%B7%E7%AB%AF%E6%8E%A5%E5%8F%A3
     *
     * @param $host
     * @param $port
     * @param $token
     * @param array $connections
     * @return mixed|null
     * @throws \Exception
     */
    public function KickConnections($host, $port, $token, array $connections)
    {
        $data = array(
            'method' => "Server.KickConnections",
            'params' => [[
                'connections'   => array_values(array_unique($connections)),
                'token'         => $token
            ]],
        );

        return $this->execute($host, $port, $data);
    }

    public function __construct()
    {
        foreach (self::$obj as $conn) {
            fclose($conn);
        }
    }
}
