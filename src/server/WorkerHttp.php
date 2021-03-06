<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/5/24
 * Time: 13:32
 */

namespace base\server;

use base\server\BaseCallback;
abstract class WorkerHttp extends BaseCallback {
    /**
     * http server onrequest回调
     * @param \swoole\http\request $request
     * @param \swoole\http\reponse $reponse
     * @return mixed
     */
    abstract function onRequest(\swoole\http\request $request, \swoole\http\response $response);

    /**
     * worker 进程初始化
     * @param \swoole\server $server
     * @param $wokerId
     * @return mixed
     */
    public function onWorkerStart(\swoole\server $server, $wokerId) {

    }
}