<?php

return [
    'wechat'   => [
        'appid'  => 'wxabd7fde8b6ebc8e7',
        'secret' => '3f28e0751e2e932b5101f9fc26a77e31',
    ],
    'swoole'   => [
        'host'            => '0.0.0.0',
        'port'            => 9991,
        'task_worker_num' => 8,
        'log_file'        => '/tmp/swoole/swoole.log',
        'worker_num'      => 2
    ],
    'database' => [
        'host'     => '127.0.0.1',
        'password' => '12312',
        'user'     => 'root',
        'port'     => 3306,
        'charset'  => 'utf-8',
        'database' => 'msg'
    ],
    'redis'    => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'password' => ''
    ]
];