<?php

/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-13
 * Time: 上午11:42
 */

namespace Common\Org;

/**
 * Class Http
 *
 * @package Common\Org
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
            $result = array('error' => '错误提示：' . curl_error($this->ch));
        }
        return $result;
    }
    
    /**
     * 获取微信token
     * @param type $code
     * @return type
     */
    public function post($url = '', $data = array()){
        
        // 参数判断
        if(!trim($url)){
            return array('error'=>'地址不能为空！');
        }
        
        if ($data && is_array($data)) {
            $data_str = '';
            foreach ($data as $k => $v) {
                trim($data_str) ? $data_str.="&$k=$v" : $data_str.="$k=$v";
            }
            $data=$data_str;
        }
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, trim($data));
        
         // 发送
        $result = curl_exec($this->ch);

        if (@curl_errno($this->ch)) {
            $result = array('error' => '错误提示：' . curl_error($this->ch));
        }
        return $result;
    }
    
}
