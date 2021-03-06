<?php
/**
 * Created by PhpStorm.
 * User: linux
 * Date: 17/5/28
 * Time: 下午12:35
 */

namespace base\console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use base\component\config\Config;


class BaseServer extends Command {

    protected $name = 'server';
    protected $description = 'server management';

    /**
     * 指定app自己的配置文件
     * @var string
     */
    protected $appIni = '';

    protected $appConfig = [];

    public function __construct()
    {
        parent::__construct($this->name);

        $this->setDescription($this->description);
    }

    protected function getServerDefinition()
    {
        if ( empty($this->appConfig)) {
            $appIni = $this->appIni;
            $this->appConfig = Config::load($appIni);
        }

        return $this->appConfig;
    }

    public function setAppIni($ini) {
        $this->appIni = $ini;
    }

    public function getAppIni() {
        return $this->appIni;
    }

    protected function getPidFile()
    {
        $serverDefinition = $this->getServerDefinition();
        return $serverDefinition['swoole']['pid_file'];
    }

    protected function configure()
    {
        $this->addArgument('operation', InputArgument::REQUIRED, 'the operation: start, reload, restart or stop');

        $this
            ->setName($this->name)
            ->setDescription('Start Swoole HTTP Server.')
//            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host for server', '127.0.0.1')
//            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port for server', 9501)
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Switch debug mode on/off');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = $input->getArgument('operation');

        if (!in_array($operation, ['start', 'reload', 'restart', 'stop'])) {
            $output->writeln("<info>Usage:  php server $this->getName() {start|stop|restart|reload}</info>");
            throw new InvalidArgumentException('The <operation> argument is invalid');
        }
        return call_user_func_array([$this, 'handle' . $operation], [$input, $output]);
    }

}