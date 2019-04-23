<?php

namespace Maker55\Lib;

class Redis
{
    private $redis;

    protected $dbId = 0;

    static private $instance = [];

    protected $option = [
        'timeout' => 200,
        'db_id'   => 0,
    ];

    protected $expireTime;

    protected $host;

    protected $port;

    protected $auth;

    protected $key;

    const COMMEND = [
        'hGet', 'hSet', 'hExists', 'hLen', 'hSetNx', 'hIncrBy', 'hKeys',
        'hVals', 'hGetAll', 'zAdd', 'zinCry', 'zRem', 'zRange', 'zRevRange', 'zRangeByScore',
        'zCount', 'zScore', 'zRank', 'zRevRank', 'zRemRangeByScore', 'zCard', 'rPush', 'rPushx',
        'lPush', 'lPushx', 'lLen', 'lRange', 'lIndex', 'lSet', 'lRem', 'lPop', 'rPop', 'set', 'get', 'setex',
        'setnx', 'mset', 'sMembers', 'sDiff', 'sAdd', 'scard', 'srem', 'flushDB', 'info', 'save', 'bgSave', 'lastSave',
        'keys', 'exists', 'expire', 'ttl', 'exprieAt', 'close', 'dbSize', 'randomKey', 'getDbId', 'watch', 'unwatch',
        'multi', 'exec', 'discard', 'ping', 'auth'
    ];

    private function __clone()
    {
    }

    private function __construct($config, $option = [])
    {
        $this->option = array_merge($this->option, $option);
        $this->redis = new \Redis();
        $this->port = $config['port'] ? $config['port'] : 6379;
        $this->host = $config['host'];

        $this->redis->connect($this->host, $this->port, $this->option['timeout']);

        if (isset($config['auth']) && $config['auth']) {
            $this->auth = $config['auth'];
        }

        $this->expireTime = time() + $this->option['timeout'];
    }

    public static function getInstance($config, $option = [])
    {
        $option = is_array($option) ? $option : ['db_id' => $option];
        $option['db_id'] = (isset($option['db_id']) && $option['db_id']) ? $option['db_id'] : 0;

        $key = md5(implode('', $config).$option['db_id']);

        if (! isset(static::$instance[$key]) || ! (static::$instance[$key] instanceof self)) {
            static::createInstance($config, $option, $key);
        } else if (time() > static::$instance[$key]->expireTime) {
            static::$instance[$key]->close();
            static::createInstance($config, $option, $key);
        }
        return static::$instance[$key];
    }

    private static function createInstance($config, $option, $key)
    {
        static::$instance[$key] = new self($config, $option);
        static::$instance[$key]->key = $key;
        static::$instance[$key]->dbId = $option['db_id'];

        if ($option['db_id'] != 0) {
            static::$instance[$key]->select($option['db_id']);
        }
    }

    public function getRedis()
    {
        return $this->redis;
    }

    public function select($dbId)
    {
        $this->dbId = $dbId;
        return $this->redis->select($dbId);
    }

    public function hdel($key, $field)
    {
        $fieldArr = explode(',', $field);
        $delNum = 0;

        foreach ($fieldArr as $row) {
            $row = trim($row);
            $delNum += $this->redis->hDel($key, $row);
        }

        return $delNum;
    }

    public function hMset($key, $value)
    {
        if (! is_array($value))
            return false;
        return $this->redis->hMset($key, $value);
    }

    public function hMget($key, $field)
    {
        if (! is_array($field))
            $field = explode(',', $field);
        return $this->redis->hMget($key, $field);
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getConnInfo()
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'auth' => $this->auth
        ];
    }

    public function __call($name, $arguments)
    {
        return $this->redis->$name(...$arguments);
    }
}