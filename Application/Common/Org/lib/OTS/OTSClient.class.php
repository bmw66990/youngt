<?php

// 设置时区
date_default_timezone_set('UTC');

// 引入类库
require_once __DIR__ . '/ots_protocol_buffer/ots_protocol_buffer.proto.php';

class OTSClient {

    private $ch = null;
    private $x_ots_headers = array();
    private $timeout = 65;
    private $ots_url='';
    private $ots_access_key_id='';
    private $ots_access_key_secret='';
    private $ots_instancename='';
    private $ots_apiversion='';

    function __construct($ots_url,$ots_access_key_id,$ots_access_key_secret,$ots_instancename,$ots_apiversion='2014-08-08') {
        $this->ots_url = $ots_url;
        $this->ots_instancename = $ots_instancename;
        $this->ots_apiversion = $ots_apiversion;
        $this->ots_access_key_secret = $ots_access_key_secret;
        $this->ots_access_key_id = $ots_access_key_id;
        $this->connect();
    }

    function __destruct() {
        if ($this->ch) {
            @curl_close($this->ch);
        }
    }

    /**
     * 连接ots
     */
    public function connect() {
        $this->ch = curl_init();
        // 设置基本属性
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout); // 设置超时限制防止死循环
        // 判断是否以https请求
        if (strpos($this->ots_url, 'https') !== false) {
            curl_setopt($this->ch, CURLOPT_SSLVERSION, 3);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        // 设置头信息
        $this->x_ots_headers=array(
         //   'x-ots-date'=>date("D M j H:i:s Y"),
            'x-ots-apiversion'=>$this->ots_apiversion,
            'x-ots-accesskeyid'=>$this->ots_access_key_id,
            'x-ots-instancename'=>$this->ots_instancename,
        );
    }

    /**
     * 计算签名并返回头信息
     * @param type $operation
     * @return string
     */
    private function sign($operation = '') {

        $headerArr = array();
        foreach ($this->x_ots_headers as $n => $v) {
            $headerArr[] = trim($n) . ":" . trim($v);
        }
        $sign_arr = $headerArr;
        sort($sign_arr, SORT_STRING);
        $string_header = implode("\n", $sign_arr);
        $stringToSign = $operation . "\nPOST\n\n" . $string_header . "\n";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->ots_access_key_secret, true));
        $headerArr[] = 'x-ots-signature:' . trim($signature);
        
        return $headerArr;
    }

    /**
     * 发送数据
     * @param string $operation
     * @param type $data
     * @return type
     */
    public function send($operation = '', $data = null) {

        // 参数判断
        if (!trim($operation)) {
            return array('error' => '操作行为不能为空！');
        }
        if (!$data) {
            return array('error' => '传输数据不能为空！');
        }
        if (strpos($operation, '/') === false) {
            $operation = '/' . $operation;
        }
        if (is_object($data)) {
            try{
              $data = $data->serializeToString();
            } catch (Exception $e){
              return array('error'=>$e->getMessage());
            }  
        }

        // 设置x-ots-contentmd5
        $this->x_ots_headers['x-ots-contentmd5'] = @base64_encode(md5($data, true));
        $this->x_ots_headers['x-ots-date'] = date("D M j H:i:s Y");

        // 计算签名，并返回请求头信息
        $headerArr = $this->sign($operation);

        // 设置头信息
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headerArr);

        // 设置操作行为
        curl_setopt($this->ch, CURLOPT_URL, $this->ots_url . $operation);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);

        // 发送
        $result = curl_exec($this->ch);
        
        if (@curl_errno($this->ch)) {
           $result = array('error'=>'错误提示：'.curl_error($this->ch));
        }
        return $result;
    }

}
