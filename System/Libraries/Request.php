<?php
namespace DongPHP\System\Libraries;

use DongPHP\System\Libraries\Http\Curl;

class Request
{
    //蜂助手充值话费
    const FZS_COST = 1001;

    //京东发货订单
    const JD_COST = 2001;

    //红包
    const BONUS_COST = 3001;


    /**
     * 101 appkey 没定义不存在
     * 102 域名没有定义
     * 103 没有找到对应的接口
     * 104 参数不能为空
     */

    public function __construct()
    {
        if (!defined(APP_KEY)) $this->outError(101);
        if (!defined(APP_DOMAIN)) $this->outError(102);
        $this->appkey    = APP_KEY;
        $this->appdomain = APP_DOMAIN;
    }

    public function send($request = 1001, $data = [])
    {
        $url  = $this->getUrl($request);
        $sign = $this->getSign($data);
        return Curl::post($url, ['data' => $data, 'sign' => $sign]);
    }

    //获取当前的url
    private function  getUrl($request = 0)
    {
        $domain = 'http://db.xyzs.com';
        $config = [
            FZS_COST   => 'fzs_cost',
            JD_COST    => 'jd_cost',
            BONUS_COST => 'bonus_cost',
        ];
        if (!isset($config[$request])) return $this->outError(103);
        return $domain . '/' . $config[$request];
    }

    private function  getSign($data = [])
    {
        if (empty($data)) $this->outError('104');
        ksort($data);
        $sign = '';
        foreach ($data as $key => $value) {
            $sign .= strtolower($key) . '=' . strtoupper($value);
        }
        return md5($this->appkey . $sign . $this->$this->appdomain);
    }

    private function outError($code = 404, $msg = '')
    {
        throw new \Exception($msg, $code);
    }
}