<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/5/26
 * Time: 15:42
 */

namespace base\server;


abstract class BaseCallback {

    /**
     * 当前swoole server 对象
     * @var
     */
    protected $server;

    public function setServer(swoole\server $server) {
        $this->server = $server;
    }

}