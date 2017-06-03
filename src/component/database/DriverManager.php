<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/6/1
 * Time: 17:27
 */

namespace base\component\database;

use Aura\SqlQuery\Exception;

class DriverManager {

    private static $_driverMap = [
        'pdo' => 'base\component\database\drivers\PDOMysql',
    ];
    public static function getConnection($selectDB, $params) {
        if ( !isset($params[$selectDB]) ) {
            throw new \Exception('Invalid `$selectDB` section');
        }

        $params = $params[$selectDB];

        if ( !isset($params['driver']) ) {
            $params['driver'] = 'pdo';
        }
        if ( !in_array($params['driver'], array_keys(self::$_driverMap)) ) {
            throw new Exception('unknown Driver `$params[\'driver\']`');
        }
        $driverKey = $params['driver'];

        $className = self::$_driverMap[$driverKey];
        $driver = new $className($params);

        //connection
        $wrapperClass = 'base\component\database\Connection';

        if ( isset($params['wrapperClass']) ) {
            $wrapperClass = $params['wrapperClass'];
        }

        return new $wrapperClass($selectDB, $driver);
    }
}