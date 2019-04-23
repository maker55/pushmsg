<?php

namespace Maker55\Server;

class Server
{
    protected $host;

    protected $port;

    protected $mode = SWOOLE_PROCESS;

    protected $sock_type = SWOOLE_SOCK_TCP;

    protected $listen = [];

    private $swoole;

    protected $handler = null;

    /**
     * @var 保存master的pid和manager的pid,以","　分割;
     */
    private $pidFile;

    protected $processname = 'swooleprocess';

    protected $setting = [
        'reactor_num'           => 2,
        'worker_num'            => 4,
        'open_tcp_nodelay'      => true,
        'task_worker_num'       => 8,
        'backlog'               => 128,
        'enable_reuse_port'     => true,
        'dispatch_mode'         => 1,
        'daemonize'             => 1,
        'open_length_check'     => true,
        'package_eof'           => PHP_EOL,
        'open_eof_check'        => true,
        'package_max_length'    => 2465792,
        'package_length_type'   => 'N',
        'package_length_offset' => 4,
        'package_body_offset'   => 0,
        'log_file'              => '/tmp/swoole/swoole.log'
    ];

    public function __construct($config = [])
    {
        $this->setHost();

        $this->port = isset($config['port']) ?: 9991;
        $this->host = isset($config['host']) ?: '127.0.0.1';

        $this->procSetting($config);
    }

    private function procSetting($config)
    {
        foreach (['worker_num', 'task_worker_num'] as $key => $value) {
            if (isset($config[$key])) {
                $this->setting[$key] = $config[$key] ?: 4;
            }
        }
    }

    public function initRun()
    {
        $this->pidFile = '/tmp/swoole/'.$this->port.'_pid';
    }

    public function register()
    {
        set_error_handler([$this, 'errorHandler']);
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    public function errorHandler($errno, $message, $file, $line)
    {
        $str = <<<EOF
         'errno':$errno
         'errstr':$message
         'errfile':$file
         'errline':$line
EOF;
        if ($this->setting['log_file']) {
            $date = date('Y-m-d H:i:s', time());
            $msg = "[$date] ".$str;
            error_log($msg.PHP_EOL, 3, $this->setting['log_file']);
        }
    }

    public function shutdownHandler()
    {
        $error = error_get_last();
        $fatalError = E_ERROR | E_USER_ERROR | E_CORE_ERROR |
            E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
        if ($error && ($error['type'] & $fatalError)) {
            $this->errorHandler($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }

    private function createMainServer()
    {
        $this->swoole = new \Swoole\Server($this->host, $this->port, $this->mode, $this->sock_type);
        $this->swoole->set($this->setting);
        $this->swoole->on('Start', [$this, 'onMasterStart']);
        $this->swoole->on('ManagerStart', [$this, 'onManagerStart']);
        $this->swoole->on('WorkerStart', [$this, 'onWorkerStart']);

        $this->swoole->on('WorkerStop', [$this, 'onWorkerStop']);

        $this->swoole->on('Receive', [$this, 'onReceive']);
        $this->swoole->on('Connect', [$this, 'onConnect']);
        $this->swoole->on('Task', [$this, 'onTask']);
        $this->swoole->on('Finish', [$this, 'onFinish']);
        $this->swoole->on('Close', [$this, 'onClose']);

    }

    public function onManagerStart(\Swoole\Server $server)
    {
        $this->setProcessName($this->processname.'.manager.'.$this->port);
    }

    public function onMasterStart(\Swoole\Server $server)
    {
        file_put_contents($this->pidFile, $server->master_pid.' '.$server->manager_pid);
        $this->setProcessName($this->processname.'.master.'.$this->port);
        $this->handler->onStart($server);
    }

    public function onWorkerStart(\Swoole\Server $server, $workid)
    {
        if ($server->taskworker) {
            $this->setProcessName($this->processname.'.task.'.$this->port);
        } else {
            $this->setProcessName($this->processname.'.event.'.$this->port);
        }
        $this->handler->onWorkerStart($server, $workid);
    }

    public function onWorkerStop(\Swoole\Server $server, $workid)
    {
        $this->handler->onShutdown($server, $workid);
    }

    public function onReceive(\Swoole\Server $server, $fd, $reactor_id, $data)
    {
        error_log($data, 1, $this->setting['log_file']);
        $this->handler->onReceive($server, $fd, $reactor_id, $data);
    }

    public function onConnect(\Swoole\Server $server, $fd, $reactor_id)
    {
        $this->handler->onConnect($server, $fd, $reactor_id);
    }

    public function onTask(\Swoole\Server $server, $task_id, $src_worker_id, $data)
    {
        $this->handler->onTask($server, $task_id, $src_worker_id, $data);
    }

    public function onFinish(\Swoole\Server $server, $task_id, $data)
    {
        $this->handler->onFinish($server, $task_id, $data);
    }

    public function onClose(\Swoole\Server $server, $fd, $reactorId)
    {
        $this->handler->onClose($server, $fd, $reactorId);
    }

    private function setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } else if (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        } else {
            trigger_error(" not support function cli_set_process_title or swoole_set_process_name.");
        }

    }

    public function setHost()
    {
        $ips = swoole_get_local_ip();
        $this->host = isset($ips['eth1']) ?: (isset($ips['eth0']) ?: '0.0.0.0');
    }

    public function start()
    {
        $this->createMainServer();

        $this->swoole->start();
    }

    public function run($cmd = 'help')
    {
        $this->initRun();

        switch ($cmd) {
            case 'start':
                if ($this->isRunning()) {
                    echo $this->processname.'is running'.PHP_EOL;
                    return;
                }
                $this->start();
                break;
            case 'stop':
                $this->shutdown();
                break;
            case 'restart':
                $this->shutdown();
                sleep(1000);
                $this->start();
                break;
            case 'reload':
                $this->reload();
                break;
        }

    }

    private function reload()
    {
        $managerPid = $this->getManagerPid();
        if (! \swoole_process::kill($managerPid, 0)) {
            echo 'not running 033[31;40m [FAIL] [0m';
            return false;
        } else if (\swoole_process::kill($managerPid, SIGUSR1)) {
            echo 'stop fail please retry again 033[31;40m [FAIL] [0m';
            return false;
        }

    }

    private function shutdown()
    {
        $masterPid = $this->getMasterPid();
        if (! $masterPid || ! \swoole_process::kill($masterPid, 0)) {
            echo 'not running 033[31;40m [FAIL] [0m'.PHP_EOL;
            return false;
        }
        \swoole_process::kill($masterPid);
        $retry = 5;
        $status = false;
        while ($retry--) {
            if (! \swoole_process::kill($masterPid, 0)) {
                unlink($this->pidFile);
                $status = true;
                echo 'stop success 033[31;40m [OK] [0m'.PHP_EOL;
                break;
            }
            \swoole_process::kill($masterPid);
            usleep(10000);
        }

        if (! $status) {
            echo 'stop fail please try again 033[31;40m [FAIL] [0m'.PHP_EOL;
        }
    }

    /**
     * 进程是否在运行
     */
    private function isRunning()
    {
        $masterPid = $this->getMasterPid();

        return $masterPid === false ? false : posix_kill($masterPid, 0);
    }

    private function parseFidfile($file)
    {
        if (file_exists($file)) {
            $content = file_get_contents($this->pidFile);
            return $content ? explode(' ', $content) : false;
        }

        return false;
    }

    private function getMasterPid()
    {
        $pids = $this->parseFidfile($this->pidFile);
        return $pids === false ? false : current($pids);
    }

    private function getManagerPid()
    {
        $pids = $this->parseFidfile($this->pidFile);
        return $pids === false ? false : last($pids);
    }

    public function setHandler($handler)
    {
        $this->handler = $handler;
    }
}

