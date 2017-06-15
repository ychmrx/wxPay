<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/28
 * Time: 10:30
 */

namespace Yinjiang\WxPay;


class WxPay
{
    protected $key;
    protected $mch_id;
    protected $appid;
    protected $notify_url;

    public static function getConfig()
    {
        $config = require __DIR__ . '/config/config.php';
        return $config;
    }

    public static function getSign($params)
    {
        $key = $params['key'];
        unset($params['key']);
        ksort($params);
        $params['key'] = $key;
        $url = urldecode(http_build_query($params));
        $sign = strtoupper(md5($url));
        return $sign;
    }

    public static function getCurl($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        return $result;
    }

    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            $xml .= "<$key>$val</$key>";
        }
        $xml .= "</xml>";
        return $xml;
    }

    public static function doRequest($xml, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $return = curl_error($ch);
        } else {
            $return = $response;
        }
        curl_close($ch);
        return $return;
    }

    public static function getQrCode($out_trade_no, $money, $body = '', $product_id = 1)
    {
        $money = $money * 100;
        $params = array(
            'nonce_str' => md5(time()),
            'body' => $body,
            'out_trade_no' => $out_trade_no,
            'total_fee' => $money,
            'spbill_create_ip' => isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '127.0.0.1',
            'trade_type' => 'NATIVE',
            'product_id' => $product_id
        );
        $config = self::getConfig();
        $params = array_merge($params, $config);
        $sign = self::getSign($params);
        $params['sign'] = $sign;
        unset($params['key']);
        $xml = self::arrayToXml($params);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $response = self::doRequest($xml, $url);
        $response = self::doResolveResult($response);
        $url = 'http://paysdk.weixin.qq.com/example/qrcode.php?data=' . $response['code_url'];
        return $url;
    }

    public static function getOrderQuery($out_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $config = self::getConfig();
        $params = array(
            'appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'key' => $config['key'],
            'out_trade_no' => $out_trade_no,
            'nonce_str' => md5(time())
        );
        $sign = self::getSign($params);
        $params['sign'] = $sign;
        unset($params['key']);
        $xml = self::arrayToXml($params);
        $response = self::doRequest($xml, $url);
        $response = self::doResolveResult($response);
        if (isset($response['trade_state']) && $response['trade_state'] == 'SUCCESS') {
            return true;
        } else {
            return false;
        }
    }

    public static function  doResolveResult($response)
    {
        $remove = array('[CDATA[', ']]', '<!', '-');
        $response = str_replace($remove, '', $response);
        $response = str_replace('></', '</', $response);
        $xml = simplexml_load_string($response);
        $result = json_decode(json_encode($xml), TRUE);
        return $result;
    }

}

