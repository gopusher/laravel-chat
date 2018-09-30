<?php

namespace Ctx;

use PHPCtx\Ctx\Ctx as BasicCtx;

/**
 * Context 上下文
 *
 * @property \Ctx\Service\Im\Ctx $Im
 * @property \Ctx\Service\CometRpc\Ctx $CometRpc
 * @property \Ctx\Service\Util\Ctx $Util
 */
class Ctx extends BasicCtx
{
    protected static $ctxInstance;

    /**
     * ctx命名空间
     */
    protected $ctxNamespace = 'Ctx';
}
