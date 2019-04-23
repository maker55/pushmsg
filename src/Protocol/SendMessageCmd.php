<?php

namespace Maker55\Protocol;

class SendMessageCmd extends AbstractCommand
{
    protected $openid;
    protected $tplid;
    protected $url;
    protected $data;

    public $cmd = 'send';

    public function __construct($opaque, $url = '', $openid = '', $tplid = '', $data = [])
    {
        parent::__construct($opaque);
        $this->openid = $openid;
        $this->tplid = $tplid;
        $this->data = $data;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getBody()
    {
        return json_encode($this->data);
    }

    public function getHeader()
    {
        $header = $this->cmd.' '.$this->openid.' '.$this->tplid;
        if ($this->url) {
            $header .= ' '.$this->url;
        }

        return $header;
    }

    /**
     * @return mixed
     */
    public function getOpenid()
    {
        return $this->openid;
    }

    /**
     * @param mixed $openid
     */
    public function setOpenid($openid): void
    {
        $this->openid = $openid;
    }

    /**
     * @return mixed
     */
    public function getTplid()
    {
        return $this->tplid;
    }

    /**
     * @param mixed $tplid
     */
    public function setTplid($tplid): void
    {
        $this->tplid = $tplid;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param $header
     */
    public function resoveHeader($header)
    {
        if (isset($header[1])) {
            $this->openid = $header[1];
        }
        if (isset($header[2])) {
            $this->tplid = $header[2];
        }
        if (isset($header[3])) {
            $this->url = $header[3];
        }
    }

    /**
     * @param $body
     */
    public function resovelBody($body)
    {
        $this->data = json_decode($body) ?: [];
    }

}