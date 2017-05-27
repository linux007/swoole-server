<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/5/26
 * Time: 16:17
 */

namespace base\component\config;

define('SERVER_ROOT_PATH', dirname(dirname(dirname(__FILE__))));
class Config {

    /**
     * 系统默认ini 配置文件，初始化配置
     * @var string
     */
    private static $defaultIni = '';

    public static function load($ini = null) {
        $config = [];
        self::$defaultIni = SERVER_ROOT_PATH . '/conf/server.ini';

        $defaultConfig = parse_ini_file(self::$defaultIni, true);

        // 用户自定义配置
        if ($ini) {
            $config = parse_ini_file($ini, true);
        }

        return array_merge($defaultConfig, $config);
    }
}