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
     * @var \swoole\server | \swoole\http\server | \swoole\websocket\server
     */
    public $server = null;
    /**
     * server 类型
     * 0 -- 系定义协议server
     * 1 -- http
     * 2 -- websock & http
     * @var  \swoole\server | \swoole\http\server | \swoole\websocket\server
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

    /**
     * 实例
     * @var
     */
    private static $instance = null;

    private function __construct($ini = null) {
//        $this->config = Config::load($ini);
//        $this->serverMode = isset($this->config['mode']) ? constant($this->config['mode']) : SWOOLE_PROCESS;

        if ( !\extension_loaded('swoole') ) {
            throw new \Exception('no swoole extension. get: https://github.com/swoole/swoole-src"');
        }

        if (version_compare(SWOOLE_VERSION, '1.9.5', '<')) {
            throw new \Exception('the version of swoole must be >= 1.8.7');
        }

    }

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 对象属性初始化
     * @param null $ini
     */
    public function set($ini = null) {
        $this->config = Config::load($ini);
        $this->serverMode = isset($this->config['mode']) ? constant($this->config['mode']) : SWOOLE_PROCESS;
        return $this;
    }

    /**
     * 初始化配置
     * worker_num, buffer, time_zone等
     */
    protected function init() {
        $unixsockBufferSize = isset($this->config['php']['unixsock_buffer_size']) ? $this->config['php']['unixsock_buffer_size']  : 1024 * 1000;
        ini_set('swoole.unixsock_buffer_size', $unixsockBufferSize);

        date_default_timezone_set('PRC');

        // todo

        $swSettings = array_merge($this->config['swoole'], $this->config['server']['settings']);
        $this->config['swSettings'] = $swSettings;
        $defaultCpuNum = function_exists('\\swoole_cpu_num') ? \swoole_cpu_num() : 8;
        $this->config['swSettings']['worker_num'] = isset($swSettings['worker_num']) ? $swSettings['worker_num'] : $defaultCpuNum;
    }


    protected function createWorkerServer() {

        $this->init();

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
//        $swSettings = array_merge($_G['swoole'], $_G['server']['settings']);
        $socketType = isset($_G['socket']) ? constant($_G['socket']) : SWOOLE_TCP;

        $this->server = new $className($_G['host'], $_G['port'], $this->serverMode, $socketType);
        $this->server->set($_G['swSettings']);
        //端口监听

        if (isset($_G['listen']) && count($_G['listen']) > 0) {
            foreach ($_G['listen'] as $listen_server => $item) {
                // init swoole host & port
                $_host = isset($item['host']) ? $item['host'] : $_G['swoole']['host'];
                $_port = isset($item['port']) ? $item['port'] : $_G['swoole']['port'];
                $portServer = $this->server->listen($_host, $_port, constant($item['socket']));
                unset($_host, $_port);
                $this->bindCallback($portServer);
            }
        }

        // 绑定回调
        $this->bindCallback($this->server);

        $this->worker->beforeStart();

        $this->server->start();
    }

    /**
     * 绑定回调事件
     * @param $server  ［绑定回调事件到对象，可以是swoole_server | swoole_listen_port］
     */
    private function bindCallback($server) {

        $callbackHandle = array(
            'onWorkerStop',
            'onWorkerError',

            'onConnect',
            'onClose',

            'onManagerStart',
            'onManagerStop',

            'onPipeMessage',
        );

        if ( !($server instanceof \swoole\server\port) ) {

            $this->server->on('Start', array($this->worker, 'onStart'));
            $this->server->on('Shutdown', array($this->worker, 'onShutdown'));
            $this->server->on('WorkerStart', array($this->worker, 'doWork'));

            //可以是websocket | http
            if ($this->server instanceof \swoole\http\server) {
                $this->server->on('Request', [$this->worker, 'onRequest']);
            }

            if ($this->server instanceof \swoole\websocket\server) {
                $this->server->on('Message', [$this->worker, 'onMessage']);

                //设置onHandShake回调函数后不会再触发onOpen事件
                if (isset($this->config['handShake']) && $this->config['handShake']) {
                    $this->server->on('HandShake', [$this->worker, 'onHandShake']);
                } else {
                    $this->server->on('Open', [$this->worker, 'onOpen']);
                }
            }

            foreach($callbackHandle as $handler) {
                if(method_exists($this->worker, $handler)) {
                    $this->server->on(\substr($handler, 2), [$this->worker, $handler]);
                }
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

    }

    public function setCallback($callback) {
        if ( !($callback instanceof BaseCallback) ) {
            throw new \Exception('must be a object of base\\server\\BaseCallback');
        }

        // 当前进程对象
        $this->worker = $callback;
        $this->worker->setServer($this->server);
    }



}