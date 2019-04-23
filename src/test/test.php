<?php

require_once '../../vendor/autoload.php';
error_reporting(E_ERROR);
$config = require_once '../Config/config.php';
$wechat = new \Maker55\Wechat\Wechat($config['wechat']['appid'], $config['wechat']['secret']);

if ($argv[1] != 'send') {
    $server = new \Maker55\Server\Server($config['swoole']);
    var_dump($config);
    exit(1);
    $handler = new \Maker55\Protocol\MessageHandler($config);
    $handler->setDecoder(new \Maker55\Protocol\Decoder());
    $server->setHandler($handler);
    $server->run($argv[1]);
} else {
    $message = new \Maker55\Protocol\SendMessageCmd(1, 'http://testtest.a.com/info/view/562474', 'oodJh5jToh8Jcwnu1S3t1YW_htwc', 'tWeGe9u_qDqLUibyZaUjIFIywZxEYj59JU7ziOX7Sm0');
    $client = new \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    $str = $message->encode();

    $client->on('connect', function (Swoole\Client $cli) use ($str) {
        swoole_timer_tick(1, function () use ($cli, $str) {
            $cli->send($str.PHP_EOL);
        });
    });
    $decoder = new \Maker55\Protocol\Decoder();
    $client->on("receive", function (Swoole\Client $cli, $data) use ($decoder) {
        var_dump($decoder->decode($data));
        echo $data;

    });

    $client->on("error", function (Swoole\Client $cli) {
        echo "error\n";
    });

    $client->on("close", function (Swoole\Client $cli) {
        echo 'closed';
    });

    $client->connect('127.0.0.1', 9991, 30);
    $redis = \Maker55\Lib\Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379]);
}
