<?php

namespace Maker55\Protocol;

class Decoder
{
    public static $cmdMap = [
        'send' => SendMessageCmd::class,
        'response'=>ResultCmd::class
    ];

    private $maxPackLen = 2465792;

    public function __construct()
    {

    }

    public function decode($message, $fd=null)
    {
        $pack = $this->parse($message);

        if (strlen($message) > $this->maxPackLen || $pack['packlen'] > $this->maxPackLen) {
            throw new \Exception('数据包长度超过限制大小');
        }

        $cmd = current($pack['header']);
        if (! $cmd || ! isset(self::$cmdMap[$cmd])) {
            throw new \Exception('无效的数据包');
        }

        $command = new self::$cmdMap[$cmd]($pack['opaque']);
        $command->setFd($fd);
        $command->resoveHeader($pack['header']);
        $command->resovelBody($pack['body']);

        return $command;
    }

    public function parse($message)
    {
        $offset = 0;
        $packLen = $this->readInt($message, $offset);

        $header = $this->readString($message, $offset, $this->readInt($message, $offset));

        $opaque = $this->readInt($message, $offset);

        $body = $this->readString($message, $offset, $this->readInt($message, $offset));

        return [
            'packlen' => $packLen,
            'opaque'  => $opaque,
            'header'  => $header ? explode(' ', $header) : [],
            'body'    => $body
        ];
    }

    public function readInt($message, &$offset)
    {
        $result = unpack('N1val', substr($message, $offset, 4));
        $offset += 4;
        return $result['val'] ?: 0;
    }

    public function readString($message, &$offset, $len)
    {
        $result = unpack('a*val', substr($message, $offset, $len));
        $offset += $len;
        return $result['val'] ?: '';
    }

}
