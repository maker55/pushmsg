<?php

namespace Maker55\Wechat;

class Http
{
    private $ch = null;

    private $option = [
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => true,
    ];

    public function __construct($option = [])
    {
        $this->ch = curl_init();
        if ($option && is_array($option)) {
            $this->option = array_merge($this->option, $option);
        }
    }

    public function setHeader($header)
    {
        if (is_array($header)) {
            $this->option[CURLOPT_HTTPHEADER] = $header;
        }
    }

    public function setTimeout($time)
    {
        $this->option[CURLOPT_TIMEOUT] = $time ? $time : 5;
    }

    public function setProxy($proxy)
    {
        if ($proxy) {
            $this->option[CURLOPT_PROXY] = $proxy;
        }
        return $this;
    }

    public function setProxyPort($port)
    {
        if (is_int($port)) {
            $this->option[CURLOPT_PROXYPORT] = $port;
        }

        return $this;
    }

    public function setReferer($referer = "")
    {
        if (!empty($referer)) {
            $this->option[CURLOPT_REFERER] = $referer;
        }

        return $this;
    }

    private function request($url, $param = [], $dataType = 'json', $method = 'get')
    {
        if (strpos($url, 'https://') !== false) {
            $this->option +=  [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1
            ];
        }

        if ($param) {
            $param = is_array($param) ? http_build_query($param) : $param;
            if ($method == 'post') {
                $this->option[CURLOPT_POSTFIELDS] = $param;
            } else if ($method == 'get') {
                $url .= (strpos($url, '?') == false ? '?' : '') . $param;
            }
        }

        $this->option[CURLOPT_URL] = $url;

        curl_setopt_array($this->ch, $this->option);

        $content = curl_exec($this->ch);

        $status = curl_getinfo($this->ch);

        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($dataType == 'json') {
                $content = json_decode($content, true);
            }

            return $content;
        } else {
            return false;
        }

    }

    public function get($url, $param = [], $dataType = "json")
    {
        return $this->request($url, $param, $dataType);
    }

    public function post($url, $param = [], $dataType = "json")
    {
        return $this->request($url, $param, $dataType, 'post');
    }
}