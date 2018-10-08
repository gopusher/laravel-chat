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
