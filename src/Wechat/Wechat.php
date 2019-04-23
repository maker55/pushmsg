<?php

namespace Maker55\Wechat;

class Wechat
{
    const BASE_URL = 'https://api.weixin.qq.com';
    const ACCESS_TOKEN_URL = '/cgi-bin/token';
    const TEMPLATE_MESSAGE_SEND_URL = '/cgi-bin/message/template/send';

    public $appid = '';
    public $appSecret = '';
    public $token = '';
    public $encodingAesKey = '';

    private $cache;

    const CACHE_ACCESS_TOKEN_PREFIX = 'm_accesstoken';

    private $accessToken;

    public function __construct($appid, $appSecret)
    {
        $this->appid = $appid;
        $this->appSecret = $appSecret;
    }

    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    public function setAccessToken($result)
    {
        if (! isset($result['access_token'])) {
            throw new \Exception('不和合法accesstoken');
        }
        $this->accessToken = $result;
        if ($this->cache) {
            $this->cache->set(self::CACHE_ACCESS_TOKEN_PREFIX.'.'.$this->appid, serialize($result));
        }

    }

    public function getAccessToken($force = false)
    {
        if ($this->cache) {
            $this->accessToken = unserialize($this->cache->get(self::CACHE_ACCESS_TOKEN_PREFIX.'.'.$this->appid));
        }

        if ($force || ! $this->accessToken || $this->accessToken['expires_in'] < (time() - 200)) {
            $result = $this->request(self::ACCESS_TOKEN_URL, [
                'appid'      => $this->appid,
                'secret'     => $this->appSecret,
                'grant_type' => 'client_credential'
            ]);

            if (! $result) {
                return false;
            }

            $result['expires_in'] = time() + $result['expires_in'];

            $this->setAccessToken($result);

            return isset($result['access_token']) ? $result['access_token'] : false;
        }

        return $this->accessToken['access_token'];
    }

    private function request($url, $option, $method = 'get', $reflaseAccessToken = true)
    {
        $method = strtolower($method);
        $httpClient = new Http();
        $result = $httpClient->$method($this->buildUrl($url), $option);

        if (isset($result['errcode']) && $result['errcode']) {
            if (in_array($result['errcode'], [40001, 42001]) && $reflaseAccessToken) {
                $option['access_token'] = $this->getAccessToken(true);
                return $this->request($url, $option, $method, false);
            }
        }

        return $result;
    }

    public function sendTemplateMessage($message)
    {
        $result = $this->request(
            $this->buildUrl(self::TEMPLATE_MESSAGE_SEND_URL, [
                'access_token' => $this->getAccessToken(),
            ]), json_encode($message->parse(), JSON_UNESCAPED_UNICODE), 'post');

        return $result;
    }

    public function buildUrl($url, $param = [])
    {
        if (stripos($url, 'http://') === false && stripos($url, 'https://') === false) {
            $url = self::BASE_URL.$url;
        }

        if ($param) {
            $param = is_array($param) ? http_build_query($param) : $param;
            $url .= (strpos($url, '?') == false ? '?' : '').$param;
        }

        return $url;
    }
}