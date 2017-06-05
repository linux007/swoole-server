<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/5/26
 * Time: 15:56
 */

namespace base\component\log;

use base\component\config\Config;


class Log {
    static $foreground_colors = array(
        'bold'         => '1',    'dim'          => '2',
        'black'        => '0;30', 'dark_gray'    => '1;30',
        'blue'         => '0;34', 'light_blue'   => '1;34',
        'green'        => '0;32', 'light_green'  => '1;32',
        'cyan'         => '0;36', 'light_cyan'   => '1;36',
        'red'          => '0;31', 'light_red'    => '1;31',
        'purple'       => '0;35', 'light_purple' => '1;35',
        'brown'        => '0;33', 'yellow'       => '1;33',
        'light_gray'   => '0;37', 'white'        => '1;37',
        'normal'       => '0;39',
    );

    static $background_colors = array(
        'black'        => '40',   'red'          => '41',
        'green'        => '42',   'yellow'       => '43',
        'blue'         => '44',   'magenta'      => '45',
        'cyan'         => '46',   'light_gray'   => '47',
    );

    static $options = array(
        'underline'    => '4',    'blink'         => '5',
        'reverse'      => '7',    'hidden'        => '8',
    );
    static $EOF = "\n";

    private static $instance = null;

    /**
     * 系统配置信息
     * @var array
     */
    protected $config = [];
    protected $logFile = null;

    private function __construct() {
    }

    public static function getInstance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setConfig($config = []) {
        $this->config = $config;
        $this->logFile = $config['swoole']['log_file'];
        return $this;
    }

    public function warn($str = '', $newline = true, $background_color = null) {
        $color = 'yellow';
        $str = $newline ? $str . self::$EOF : $str;
        if (isset($this->config['swoole']['daemonize']) && $this->config['swoole']['daemonize']) {
            error_log('[' . date('Y-m-d H:i:s') . '] [warn] - ' . $str, 3, $this->logFile);
            return true;
        }
        echo '[' . date('Y-m-d H:i:s') . '] ' . self::$color('[warn] - ', $background_color) . $str;
    }

    public function info($str = '', $newline = true, $background_color = null) {
        $color = 'normal';
        $str = $newline ? $str . self::$EOF : $str;
        if (isset($this->config['swoole']['daemonize']) && $this->config['swoole']['daemonize']) {
            error_log('[' . date('Y-m-d H:i:s') . '] [info] - ' . $str, 3, $this->logFile);
            return true;
        }
        echo '[' . date('Y-m-d H:i:s') . '] ' . self::$color('[info] - ', $background_color) . $str;
    }

    public function error($str = '', $newline = true, $background_color = null) {
        $color = 'red';
        $str = $newline ? $str . self::$EOF : $str;
        if (isset($this->config['swoole']['daemonize']) && $this->config['swoole']['daemonize']) {
            error_log('[' . date('Y-m-d H:i:s') . '] [error] - ' . $str, 3, $this->logFile);
            return true;
        }
        echo '[' . date('Y-m-d H:i:s') . '] [' . self::$color('error', $background_color) .'- ]'. $str;
    }

    public function debug($str = '', $newline = true, $background_color = null) {
        $color = 'normal';
        $str = $newline ? $str . self::$EOF : $str;
        if (isset($this->config['swoole']['daemonize']) && $this->config['swoole']['daemonize']) {
            error_log('[' . date('Y-m-d H:i:s') . '] [debug] - ' . $str, 3, $this->logFile);
            return true;
        }
        echo '[' . date('Y-m-d H:i:s') . '] ['. self::$color('debug', $background_color) . '] -'. $str;
    }

    public static function __callStatic($foreground_color, $args) {
        $string         = $args[0];
        $colored_string = "";

        // Check if given foreground color found
        if( isset(self::$foreground_colors[$foreground_color]) ) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
        else{
            die( $foreground_color . ' not a valid color');
        }

        array_shift($args);
        foreach( $args as $option ){
            // Check if given background color found
            if(isset(self::$background_colors[$option])) {
                $colored_string .= "\033[" . self::$background_colors[$option] . "m";
            }
            elseif(isset(self::$options[$option])) {
                $colored_string .= "\033[" . self::$options[$option] . "m";
            }
        }

        // Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;

    }


}