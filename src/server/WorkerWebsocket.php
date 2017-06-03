<?php
/**
 * Created by PhpStorm.
 * User: linux
 * Date: 17/5/27
 * Time: 下午7:03
 */

namespace base\server;

use base\server\BaseCallback;

abstract class WorkerWebsocket extends BaseCallback {

    public function onWorkerStart(\swoole\server $server, $wokerId) {
        // TODO: Implement onWorkerStart() method.
    }

    /**
     * @param \swoole\server $server
     * @param $frame
     * @return mixed
     */
    abstract function onMessage(\swoole\server $server, $frame);
}