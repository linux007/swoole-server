<?php
/**
 * Created by PhpStorm.
 * User: linux
 * Date: 17/5/28
 * Time: 下午12:50
 */

namespace base\console;

use base\console\BaseServer;
use base\server\Server;

use function Composer\Autoload\includeFile;
use Symfony\Component\Debug\Exception\ClassNotFoundException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;


class ServerCommand  extends BaseServer {

    public function handleStart() {
        $serverDefinition = $this->getServerDefinition();
        $pidfile = $this->getPidFile();

        if ( file_exists($pidfile) ) {
            if ( \swoole_process::kill(file_get_contents($pidfile), 0) ) {
                throw new \UnexpectedValueException('The pidfile exists, it seems the server is already started');
            }
            unlink($pidfile);
        }

        $className = $serverDefinition['server']['classname'];
        if ($pos = strrpos($className, '\\')) {
            $classFile = substr($className, $pos + 1);
            include APP_PATH . '/server/' . $classFile . '.php';
        }

        if ( !class_exists($className) ) {
            $previous = new \ErrorException();
            throw new ClassNotFoundException($className . ' class not found', $previous);
        }

        $worker = new $className();

        $server = Server::getInstance()->createWorkerServer()->setCallback($worker);

        $server->run();

        $pid = file_get_contents($pidfile);
        if (swoole_process::kill($pid, 0)) return 0;

        return 1;
    }


    public function handleStop() {
        $pidfile = $this->getPidFile();

        if ( !file_exists($pidfile) ) {
            throw new FileNotFoundException('the pidfile of server not found!');
        }

        $pid = file_get_contents($pidfile);

        if ( swoole_process::kill($pid, 0) && posix_kill($pid, 15)) {
            do {
                usleep(100000);
            } while(file_exists($pidfile));
            return 0;
        }

        return 1;
    }

    public function handleReload() {

        $pidfile = $this->getPidFile();

        if ( !file_exists($pidfile) ) {
            throw new FileNotFoundException('the pidfile of server not found!');
        }

        $pid = file_get_contents($pidfile);

        if (file_exists($pidfile) && posix_kill($pid, 10)) {
            return 0;
        }
        return 1;
    }

    public function handleRestart() {
        $this->handleStop();

        return $this->handleStart();
    }
}