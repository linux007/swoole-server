<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/5/26
 * Time: 15:42
 */

namespace base\server;
use base\component\log\Log;
use base\component\watcher\Tracker;
use base\component\watcher\Watcher;


abstract class BaseCallback {

    /**
     * 当前swoole server 对象
     * @var
     */
    protected $server;

    /**
     * 服务初始化配置
     * @var
     */
    protected $config;

    public function __construct() {
    }

    public function setServer(\swoole\server $server, $config) {
        $this->server = $server;
        $this->config = $config;
    }

    public function onStart($server) {
        $class = $this->_realClass(get_class($this));

        if ($server instanceof \swoole\http\server) {

            $this->setProcessName($class . ' Http Server:' . $server->port . '[master:'.$server->master_pid.']');
            Log::getInstance()->info(get_class($this) . ' Http Server running master:' . $server->master_pid);
        } elseif ($server instanceof \swoole\websocket\server) {

            $this->setProcessName($class . ' Websocket Server:' . $server->port . '[master:'.$server->master_pid.']');
            Log::getInstance()->info(get_class($this) . ' Websocket Server running master:' . $server->master_pid);
        } else {

            $this->setProcessName($class . ' Server:' . $server->port . '[master:'.$server->master_pid.']');
            Log::getInstance()->info(get_class($this) . ' Server running master:' . $server->master_pid);
        }

    }

    public function onShutDown() {

    }

    public function onManagerStart($server) {
        $this->setProcessName('[manager:' . $server->manager_pid . ']');
        Log::getInstance()->info(get_class($this) . ' Manager:' . $server->manager_pid);
    }

    public function onManagerStop() {

    }

    public function doWork(\swoole\server $server, $workerId) {
        $this->setProcessName('[worker#'.$workerId.']');
        $this->onWorkerStart($server, $workerId);
    }

    /**
     * 设置进程名称
     * @param $name
     */
    protected function setProcessName($name) {
        if (function_exists('\cli_set_process_title')) {
            @cli_set_process_title($name);
        } else {
            if (function_exists('\swoole_set_process_name')) {
                @swoole_set_process_name($name);
            } else {
                trigger_error(__METHOD__ .' failed. require cli_set_process_title or swoole_set_process_name.');
            }
        }
    }

    /**
     * 服务启动前预留接口,
     * @return mixed
     */
    public function beforeStart() {
        if (isset($this->config['server']['autoreload'])  && $this->config['server']['autoreload']) {

            $reloadProcess = new \swoole_process(function($process) {
                $tracker = new Tracker();
                $watcher = new Watcher($tracker);
                $watcher->watch(APPLICATION_PATH);
                swoole_timer_tick(2000, function () use ($watcher) {
                    $changed = $watcher->start();
                    if ($changed) {
                        $pid = file_get_contents($this->config['swoole']['pid_file']);
                        if (posix_kill($pid, SIGUSR1)) {
                            Log::getInstance()->info('reloading server ...');
                        }
                    }
                });
            });
            $this->server->addProcess($reloadProcess);
        }
    }

    /**
     * worker 进程初始化
     * @param \swoole\server $server
     * @param $wokerId
     * @return mixed
     */
    abstract function onWorkerStart(\swoole\server $server, $wokerId);


    private function _realClass($class) {
        if ($pos = strrpos($class, '\\')) return substr($class, $pos + 1);
        return $pos;
    }

}