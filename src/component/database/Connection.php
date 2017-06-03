<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/6/1
 * Time: 18:58
 */

namespace base\component\database;


use Aura\SqlQuery\QueryFactory;
use Symfony\Component\Debug\Exception\UndefinedMethodException;

class Connection {

    protected $_driver;

    protected $_conn;

    protected $_isConnected = false;

    protected $_driverOptions = array();

    protected $queryFactory;

    public function __construct($selectDb, $driver) {
        $this->_driver = $driver;
        $this->queryFactory = new QueryFactory('mysql');
    }

    public function __call($name, $arguments) {
        $this->_driver->connect();

        if ( ! method_exists($this->_driver, $name) ) {
            $class = get_class($this->_driver);
            $message = "Class '{$class}' does not have a method '{$name}'";
            $ErrorException = new \ErrorException();
            throw new UndefinedMethodException($message, $ErrorException);
        }

        return call_user_func_array([$this->_driver, $name], $arguments);
    }


    public function getQueryFactory() {
        return $this->queryFactory;
    }

}