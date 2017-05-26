<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/5/26
 * Time: 16:17
 */

namespace base\component;


class Config {

    /**
     * 系统默认ini 配置文件，初始化配置
     * @var string
     */
    private static $defaultIni = '../conf/server.ini';

    public static function load($ini = null) {
        $config = [];
        $defaultConfig = parse_ini_file(self::$defaultIni);

        // 用户自定义配置
        if ($ini) {
            $config = parse_ini_file($ini);
        }

        return array_merge($defaultConfig, $config);
    }
}