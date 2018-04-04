<?php

/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-13
 * Time: 上午11:42
 */
class Http {

    private $ch = null;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->connect();
    }

    public function connect() {

        $this->ch = curl_init();
        // 设置基本属性

        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Accept-Charset: utf-8"));
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
    }

    public function get($url = '', $data = array()) {
        if (!trim($url)) {
            return array('error' => '地址不能为空！');
        }
        if ($data) {
            $data_str = '';
            foreach ($data as $k => $v) {
                trim($data_str) ? $data_str.="&$k=$v" : $data_str.="$k=$v";
            }
            $url.="?$data_str";
        }
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($this->ch, CURLOPT_URL, $url);

        // 发送
        $result = curl_exec($this->ch);

        if (@curl_errno($this->ch)) {
            $result = curl_error($this->ch);
        }
        return $result;
    }

    /**
     * 获取微信token
     * @param type $code
     * @return type
     */
    public function post($url = '', $data = array()) {

        // 参数判断
        if (!trim($url)) {
            return '地址不能为空！';
        }

        if (!$data) {
            $data = '';
        }

        if ($data && is_array($data)) {
            $data_str = '';
            foreach ($data as $k => $v) {
                trim($data_str) ? $data_str.="&$k=$v" : $data_str.="$k=$v";
            }
            $data = $data_str;
        }

        // cookie 传参
        $cookie = '';
        if ($_COOKIE) {
            $data_str = '';
            foreach ($_COOKIE as $k => $v) {
                trim($data_str) ? $data_str.=";$k=$v" : $data_str.="$k=$v";
            }
            $cookie = $data_str;
        }
        curl_setopt($this->ch, CURLOPT_COOKIE, $cookie);

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);

        // 发送
        $result = curl_exec($this->ch);
        if (@curl_errno($this->ch)) {
            $result = curl_error($this->ch);
        }
        return $result;
    }
    
    /**
     * 判断是否为手机
     * @return boolean
     */
    public function is_mobile() {
        if (stristr($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
            return false;
        }
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
            return true;

        //此条摘自TPM智能切换模板引擎，适合TPM开发
        if (isset($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT'])
            return true;
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA']))
        //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
            );
            //从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }

}
