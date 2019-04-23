<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/12 0012
 * Time: 11:32
 */

namespace Maker55\Wechat;


class Message
{
    public $url;
    public $openid;
    public $tplid;
    public $template;
    public $data;

    public function __construct($openid = '', $tplid = '', $url = '', $data = [])
    {
        $this->openid = $openid;
        $this->tplid = $tplid;
        $this->url = $url;
        if ($data) {
            $this->setData($data);
        }
    }

    public function parse()
    {
        if (! $this->openid || ! $this->tplid) {
            return false;
        }

        $pattern = ['/\\$\\{OPENID\\}/', '/\\$\\{URL\\}/'];
        $replacement = [$this->openid, $this->url];

        if ($this->data) {
            foreach ($this->data as $key => $value) {
                if ($key && $value && is_string($value)) {
                    $pattern[] = '/\\$\\{'.$key.'\\}/';
                    $replacement[] = $value;
                }
            }
        }

        return preg_replace($pattern, $replacement, $this->template);
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
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
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        if (! is_array($data)) {
            throw new \Exception('数据格式错误');
        }

        $this->data = $data;
    }

    /**
     * @param mixed $template
     */
    public function setTemplate($template): void
    {
        $this->template = $template;
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
    public function setTplid($tplid)
    {
        $this->tplid = $tplid;
    }
}