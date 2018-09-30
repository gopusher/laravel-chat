<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($request->expectsJson()) {
            $statusCode = 500;
            $headers = [];

            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();
                $headers = $exception->getHeaders();
            }

            return response()->json([
                'code'      => -1,   //错误代码 0：正确，-1：服务器错误，1：请求错误
                'data'      => [], //返回数据体
                'error'     => $exception->getMessage(),//返回消息
//                'error'     => (string) $exception,//返回消息
            ], 200, $headers);
        }

        return parent::render($request, $exception);
    }
}
