<?php

namespace Maker55\Protocol;

class ResultCmd extends AbstractCommand
{
    protected $code;
    protected $message;

    public $cmd = 'response';

    public function __construct($opaque, $code = '', $message = '')
    {
        parent::__construct($opaque);
        $this->code = $code;
        $this->message = $message;
    }

    public function getBody()
    {
        return json_decode(is_array($this->message) ? $this->message : ['message' => $this->message]);
    }

    public function getHeader()
    {
        return $this->cmd.' '.$this->code;
    }

    /**
     * @param $header
     */
    public function resoveHeader($header)
    {
        if (isset($header[1])) {
            $this->code = $header[1];
        }

    }

    /**
     * @param $body
     */
    public function resovelBody($body)
    {
        $this->message = json_decode($body) ?: [];
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

}