<?php

namespace Maker55\Protocol;

abstract class AbstractCommand
{
    protected $cmd = '';

    protected $opaque;

    public $fd;

    public function __construct($opaque, $fd = null)
    {
        $this->opaque = $opaque;
        $this->fd = $fd;
    }

    abstract function getBody();

    abstract function getHeader();

    public function encode()
    {
        $header = $this->getHeader();
        $body = $this->getBody();

        $pack = pack('N1a*', strlen($header), $header).pack('N1', $this->opaque).pack('N1a*', strlen($body), $body);

        return pack('N1', strlen($pack)).$pack;
    }

    public function setFd($fd)
    {
        $this->fd = $fd;
    }

    public function getFd()
    {
        return $this->fd;
    }

    public function setOpaque($opaque)
    {
        $this->opaque = $opaque;
    }

    public function getOpaque()
    {
        return $this->opaque;
    }

    public function resoveHeader($header)
    {
    }

    public function resovelBody($body)
    {
    }
}