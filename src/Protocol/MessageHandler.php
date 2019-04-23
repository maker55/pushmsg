<?php

namespace Maker55\Protocol;

use Maker55\Lib\DB;
use Maker55\Server\Handler;
use Maker55\Wechat\Message;
use Maker55\Wechat\Wechat;

class MessageHandler implements Handler
{
    private $decoder = null;
    public $templates;
    public $templatesTable;
    protected $redis;
    protected $timer;
    static $runningTaskNum;
    const QUEUE_NAME = 'msg.queue';
    public $config;

    public function __construct($config = [])
    {
        $this->config = $config;
        self::$runningTaskNum = new \Swoole\Atomic(0);
        $this->templates = $this->initTemplatesTable();
    }

    function onStart(\Swoole\Server $server)
    {

    }

    function onWorkerStart(\Swoole\Server $server, $workerId)
    {
        if (! $server->taskworker) {
            $conf = $this->config['redis'];
            $this->redis = \Maker55\Lib\Redis::getInstance(['host' => $conf['host'], 'port' => $conf['port']]);
            if ($conf['password']) {
                $this->redis->auth($conf['password']);
            }
            $this->timer = swoole_timer_tick(10, function () use ($server) {
                if (self::$runningTaskNum->get() < $server->setting['task_worker_num']) {
                    if ($message = $this->redis->lpop(self::QUEUE_NAME)) {
                        $server->task($message);
                        self::$runningTaskNum->add(1);
                    }
                }
            });
        }
    }

    function onConnect(\Swoole\Server $server, $fd, $from_id)
    {
        // TODO: Implement onConnect() method.
    }

    function onReceive(\Swoole\Server $server, $fd, $from_id, $data)
    {
        try {
            $command = $this->decoder->decode($data, $fd);
            switch ($command->cmd) {
                case 'send':
                    if (self::$runningTaskNum->get() < $server->setting['task_worker_num']) {
                        self::$runningTaskNum->add(1);
                        $server->task(serialize($command));
                    } else {
                        $this->redis->rpush(self::QUEUE_NAME, serialize($command));
                    }
            }
        } catch (\Exception $e) {
            $resultCmd = new ResultCmd($command->getOpaque(), HttpStatus::INTERNAL_SERVER_ERROR, $e->getMessage());
            $server->send($command->getFd(), $resultCmd->encode());
        }
    }

    function onClose(\Swoole\Server $server, $fd, $from_id)
    {
        // TODO: Implement onClose() method.
    }

    function onShutdown(\Swoole\Server $server, $workerId)
    {
        // TODO: Implement onShutdown() method.
    }

    function onTask(\Swoole\Server $server, $taskId, $fromId, $data)
    {
        try {
            $command = unserialize($data);
            $conf = $this->config['wechat'];
            $wechat = new Wechat($conf['appid'], $conf['secret']);
            $wechat->setCache($this->redis);
            $tempalte = $this->templatesTable->get($command->getTplid());
            if (! $tempalte || ! $tempalte['tpl']) {
                $server->finish(['fd' => $command->fd, 'code' => 'not exit tpl']);
                return;
            }

            $message = new Message($command->getOpenid(), $command->getTplid(), $command->getUrl(), $command->getData());
            $message->setTemplate(unserialize($tempalte['tpl']));
            $result = $wechat->sendTemplateMessage($message);

            if (! $result || $result['code'] !== 0) {
                $resultCmd = new ResultCmd($command->getOpaque(), HttpStatus::SUCCESS, $result);
                $server->send($command->getFd(), $resultCmd->encode().PHP_EOL);
            } else {
                $resultCmd = new ResultCmd($command->getOpaque(), HttpStatus::SUCCESS, []);
                $server->send($command->getFd(), $resultCmd->encode());
            }

            self::$runningTaskNum->sub(1);

        } catch (\Exception $e) {
            $server->send($command->getFd(), new ResultCmd($command->getOpaque(), HttpStatus::INTERNAL_SERVER_ERROR, $e->getMessage()));
            $server->close($command->getFd());
        }
    }

    function onFinish(\Swoole\Server $server, $taskId, $data)
    {
        //$server->send($data['fd'], json_encode($data).PHP_EOL);
        return true;
    }

    function onTimer(\Swoole\Server $server, $interval)
    {
        // TODO: Implement onTimer() method.
    }

    /**
     * @return $decoder
     */
    public function getDecoder()
    {
        return $this->decoder;
    }

    /**
     * @param  $decoder
     */
    public function setDecoder($decoder)
    {
        $this->decoder = $decoder;
    }

    /**
     *
     */
    public function initTemplatesTable()
    {
        $this->templatesTable = new \Swoole\Table(22);
        $this->templatesTable->column('tpl', \Swoole\Table::TYPE_STRING, 2048);
        $this->templatesTable->create();
        $conf = $this->config['database'];
        $db = DB::getInstance($conf['host'], $conf['user'], $conf['password'], $conf['database'], $conf['charset']);
        if ($result = $db->query('select * from msg_template order by dateline desc  limit 20')) {
            foreach ($result as $value) {
                $this->templatesTable->set($value['templateid'], ['tpl' => $value['data']]);
            }
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
}
