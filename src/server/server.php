<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/5/24
 * Time: 10:26
 */

namespace base\server;

use base\component\Config;
use base\server\BaseCallback;

define('SWOOLE_HTTP_SERVER', 1);
define('SWOOLE_WEBSOCKET_SERVER', 2);
define('SWOOLE_TASK_SERVER', 3);

class Server {

    /**
     * ini 全局配置
     * https://wiki.swoole.com/wiki/page/274.html
     */
    protected $config = [];
    /**
     * swoole server 对象
     * @var swoole\server | swoole\http\server | swoole\websocket\server
     */
    public $server = null;
    /**
     * server 类型
     * 0 -- 系定义协议server
     * 1 -- http
     * 2 -- websock & http
     * @var  swoole\server | swoole\http\server | swoole\websocket\server
     */
    protected $serverType = 0;

    /**
     * 当前主服务对象
     * @var null
     */
    public $master = null;
    /**
     * 当前服务进程对象 extends BaseCallback
     * @var null
     */
    public $worker = null;

    /**
     * 所有工作进程对象
     * @var array
     */
    protected $workers = [];

    /**
     * swoole 可选配置参数列表
     * @var array
     */
    private $swSettings = [];

    /**
     * 服务启动模式
     * @see https://wiki.swoole.com/wiki/page/353.html
     * @var  SWOOLE_BASE | SWOOLE_PROCESS
     */
    protected $serverMode;

    public function __construct($ini = null) {
        $this->config = Config::load($ini);
        $this->serverMode = isset($this->config['mode']) ? constant($this->config['mode']) : SWOOLE_PROCESS;

    }

    protected function systemCheck() {

    }


    protected function init() {

    }

    protected function createWorkerServer() {
        switch ($this->serverType) {
            case SWOOLE_HTTP_SERVER:
                $className = '\\Swoole\\Http\\Server';
                break;
            case SWOOLE_WEBSOCKET_SERVER:
                $className = '\\Swoole\\WebSocket\\Server';
                break;
            default:
                // 自定义端口类型服务
                $className = '\\Swoole\\Server';
                break;
        }

        $_G = $this->config;
        $swSettings = array_merge($_G['swoole'], $_G['server']['settings']);
        $socketType = isset($_G['socket']) ? constant($_G['socket']) : SWOOLE_TCP;

        $this->server = new $className($_G['host'], $_G['port'], $this->serverMode, $socketType);
        $this->server->set($swSettings);
        //端口监听

        if (isset($_G['listen']) && count($_G['listen']) > 0) {
            foreach ($_G['listen'] as $listen_server => $item) {
                // init swoole host & port
                $_host = isset($item['host']) ? $item['host'] : $_G['swoole']['host'];
                $_port = isset($item['port']) ? $item['port'] : $_G['swoole']['port'];
                $port_server = $this->server->listen($_host, $_port, constant($item['socket']));
                unset($_host, $_port);
            }
        }

        // 绑定回调

    }

    private function bindCallback($server) {
//        if ( !($server instanceof swoole\server\port) ) {
//            $this->server->on('ManagerStart', [$this, 'onManagerStart']);
//            $server->on('WorkerStart',  [$this, 'onWorkerStart']);
//            $server->on('WorkerStop',   [$this, 'onWorkerStop']);
//            $server->on('PipeMessage',  [$this, 'onPipeMessage']);
//            $server->on('Start',        [$this, 'onStart']);
//            $server->on('Finish',       [$this, 'onFinish']);
////            $server->on('Task',         [$this, 'onTask']);
//            $server->on('Connect',      [$this, 'onConnect']);
//            $server->on('Close',        [$this, 'onClose']);
//
//        }

        $callbackHandle = array(
            'onWorkerStop',
            'onWorkerError',

            'onConnect',
            'onClose',

//            'onTask',
//            'onFinish',
            'onManagerStart',
            'onManagerStop',

            'onPipeMessage',
        );

        $this->server->on('Start', array($this->worker, 'onStart'));
        $this->server->on('Shutdown', array($this->worker, 'onShutdown'));
        $this->server->on('WorkerStart', array($this->worker, 'onWorkerStart'));

        //可以是websocket | http
        if ($this->server instanceof swoole\http\server) {
            $this->server->on('Request', [$this->worker, 'onRequest']);
        }

        if ($this->server instanceof swoole\websocket\server) {
            $this->server->on('Message', [$this->worker, 'onMessage']);

            //设置onHandShake回调函数后不会再触发onOpen事件
            if (isset($this->config['handShake']) && $this->config['handShake']) {
                $this->server->on('HandShake', [$this->worker, 'onHandShake']);
            } else {
                $this->server->on('Open', [$this->worker, 'onOpen']);
            }
        }
        // 兼容 swoole\server | swoole\server\port
        if ($server->type == SWOOLE_TCP) {
            $server->on('Receive', [$this->worker, 'onReceive']);
        }

        if ($server->type == SWOOLE_UDP) {
            $server->on('Receive', [$this->worker, 'onReceive']);
            $server->on('Packet', [$this->worker, 'onPacket']);
        }

        foreach($callbackHandle as $handler) {
            if(method_exists($this->worker, $handler)) {
                $this->server->on(\substr($handler, 2), array($this->worker, $handler));
            }
        }


    }

    public function onWorkerStart($server, $workerId) {
        $this->setProcessName("[worker#$workerId]");
        //初始化worker 进程对象

    }

    public function setCallback($callback) {
        if ( !($callback instanceof BaseCallback) ) {
            throw new \Exception('must be a object of base\\server\\BaseCallback');
        }

        // 当前进程对象
        $this->worker = $callback;
        $this->worker->setServer($this->server);
    }

    public function start() {

    }

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



}