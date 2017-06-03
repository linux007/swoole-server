<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/6/1
 * Time: 18:44
 */

namespace base\component\database\drivers;


use base\component\database\Connection;

class PDOMySql {

    protected $_params;
    protected $_isConnected = false;

    protected $pdo;

    public function __construct($params) {
        $this->_params = $params;
    }

    public function connect() {
        if ($this->_isConnected) {
            return false;
        }

        $user = isset($this->_params['user']) ? $this->_params['user'] : null;
        $password = isset($this->_params['password']) ?
            $this->_params['password'] : null;
        try {
            $conn = new \PDO($this->constructPdoDsn($this->_params), $user, $password);
            $conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo = $conn;
//            $conn->exec('SET character_set_connection='.$dbCharset.';SET character_set_client='.$dbCharset.';SET character_set_results='.$dbCharset);
        } catch (\PDOException $e) {

        }

        $this->_isConnected = true;

        return true;
    }

    protected function constructPdoDsn(array $params) {
        $dsn = 'mysql:';
        if (isset($params['host']) && $params['host'] != '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }
        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }
        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }
        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }
        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }

        return $dsn;
    }

    public function fetchAll($statement, $bindValues = []) {
        $sth = $this->perform($statement, $bindValues);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchOne($statement, array $bindValues = []) {
        $sth = $this->perform($statement, $bindValues);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function fetchValue($statement, array $values = []) {
        $sth = $this->perform($statement, $values);
        return $sth->fetchColumn(0);
    }

    public function query($statement, ...$fetch) {
        $this->connect();
        $sth = $this->pdo->query($statement, ...$fetch);
        return $sth;
    }

    public function exec($statement, $bindValues = []) {
        $sth = $this->perform($statement, $bindValues);
        return $sth->rowCount();
    }

    public function fetchAffected($statement, array $values = []) {
        $sth = $this->perform($statement, $values);
        return $sth->rowCount();
    }

    public function perform($statement, $bindValues = []) {
        $this->connect();
        $sth = $this->prepareWithValues($statement, $bindValues);
        $sth->execute();

        return $sth;
    }

    protected function bindValue(\PDOStatement $sth, $key, $val)
    {
        if (is_int($val)) {
            return $sth->bindValue($key, $val, \PDO::PARAM_INT);
        }
        if (is_bool($val)) {
            return $sth->bindValue($key, $val, \PDO::PARAM_BOOL);
        }
        if (is_null($val)) {
            return $sth->bindValue($key, $val, \PDO::PARAM_NULL);
        }
        if (! is_scalar($val)) {
            $type = gettype($val);
            throw new Exception(
                "Cannot bind value of type '{$type}' to placeholder '{$key}'"
            );
        }
        return $sth->bindValue($key, $val);
    }

    public function prepareWithValues($statement, $bindValues = []) {
        $this->connect();

        $sth = $this->pdo->prepare($statement);

        if (empty($bindValues)) {
            // ... use the normal preparation
            return $sth;
        }

        foreach ($bindValues as $key => $value) {
            $this->bindValue($sth, $key, $value);
        }

        return $sth;
    }



}