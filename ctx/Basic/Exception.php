<?php

namespace Ctx\Basic;

use PHPCtx\Ctx\Exceptions\Exception as BasicException;

/**
 * 框架异常
 *
 * @copyright sh7ning 2016.1
 * @author    sh7ning
 */
class Exception extends BasicException
{
    /**
     * 异常处理基类
     *
     * ---以下为异常收集方法---
     * get_class($e) . ':[' .$e->getCode() . ']' . $e->getMessage()
     * '(' . $e->getFile() . ':' . $e->getLine() . ")\n";
     * $e->getTraceAsString()
     * other method: $e->getTrace() $e->__toString()
     * ---end---
     *
     * @param string $message 异常消息
     * @param int $code 错误码
     */
    public function __construct($message = '', $code = 0)
    {
        parent::__construct($message, $code);
    }
}
