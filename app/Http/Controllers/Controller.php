<?php

namespace App\Http\Controllers;

use Ctx\Ctx;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var Ctx
     */
    protected $ctx;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(Request $request)
    {
        $this->ctx = Ctx::getInstance();

        $this->request = $request;
    }

    protected function success($data = [])
    {
        return response()->json([
            'code'      => 0,   //错误代码 0：正确，-1：服务器错误，1：请求错误
            'data'      => $data, //返回数据体
            'error'     => "",//返回消息
        ]);
    }
}
