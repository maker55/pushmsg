<?php

namespace Maker55\Server;

interface Handler
{
    function onStart(\Swoole\Server $server);
    function onWorkerStart(\Swoole\Server $server,$workerId);
    function onConnect(\Swoole\Server $server, $fd, $from_id);

    function onReceive(\Swoole\Server $server, $fd, $from_id, $data);

    function onClose(\Swoole\Server $server, $fd, $from_id);

    function onShutdown(\Swoole\Server $server, $workerId);

    function onTask(\Swoole\Server $server, $taskId, $fromId, $data);

    function onFinish(\Swoole\Server $server, $taskId, $data);

    function onTimer(\Swoole\Server $server, $interval);
}