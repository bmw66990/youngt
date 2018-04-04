<?php

class ChinaBank
{
	
	/**
	 * 下单接口
	 */
	// const ORDER_CREATE_URL = 'https://116.228.21.162:8603/merFrontMgr/orderBusinessServlet';//测试地址

	// /**
	//  * 订单查询接口
	//  */
	// const ORDER_QUERY_URL = 'https://116.228.21.162:8603/merFrontMgr/orderQuerySerlet';//测试地址

	// /**
	//  * 下单回调接口
	//  */
	// const ORDER_CALLBACK_URL = 'http://115.29.190.187/paysdk/unionpay/notify_url.php?m=notify';


	private static $instance;
	private $merchantId;
	private $URL;
	private $UMSURL;
	private $teamId;
	private $private_path;
	private $public_path;

	private function __construct(){

		$this->merchantId = '898310059994018';

		$this->teamId='01061410';

//		$this->private_path=dirname(__FILE__).'/UMS/private.pem';
//
//		$this->public_path=dirname(__FILE__).'/UMS/public.pem';
		
		$this->URL='https://mpos.quanminfu.com:6004/merFrontMgr/orderBusinessServlet';

		$this->UMSURL="https://mpos.quanminfu.com:8018/umsFrontWebQmjf/umspay";
	}

	public static function getInstance(){
		if(!self::$instance instanceof self){
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 签名数据
	 * 
	 * @param  string $data    要签名的数据
	 * @param  string $private 私钥文件
	 * @return string          签名的16进制数据
	 *
	 */
	function sign($data) {
		$p = openssl_pkey_get_private(file_get_contents($this->private_path));
		openssl_sign($data, $signature, $p);
		openssl_free_key($p);
		return bin2hex($signature);
	}

	/**
	 * 验签
	 * 
	 * @param  string $data		待验证数据
	 * @param  string $sign		签名数据
	 * @param  string $public 	公钥文件//验签公钥文件应为全民捷付提供的公钥文件
	 * @return bool        		验签状态
	 */
	function verify($data, $sign) {
		$p = openssl_pkey_get_public(file_get_contents($this->public_path));
		$verify = openssl_verify($data, hex2bin($sign), $p);
		openssl_free_key($p);
		return $verify > 0;
	}

	/**
	 * 以post方式发送请求请求
	 * 
	 * @param  array $data 	请求参数
	 * @param  string $url  请求地址
	 * @return array 		请求结果
	 */
	function post($data, $url){
		//dump($data); exit;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//忽略证书错误信息
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		//数据载体格式
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('jsonString' => json_encode($data))));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = json_decode(curl_exec($ch), true);
		if (!$result['Signature']&&$this->logDebug) {
			$err=curl_error($ch);
			if($err){
				echo "连接错误:".$err,'<br/>';
			}else{
			 	echo '服务器返回:';
			 	var_dump($content);
			 	echo "<br/>";
			}
		}
		curl_close($ch);
		return $result;
	}
	/**
	 * 下单
	 * 
	 * @param  string  $title   订单标题
	 * @param  number  $amt     订单金额,单位分
	 * @param  string  $orderId 订单id,由商户产生
	 * @param  string  $type    交易类型
	 * @param  integer $exp     过期时间
	 * @return mixed
	 */
	function order($datas, $type = 'NoticePay', $exp = 0) {
		header('Content-type: text/html; charset=utf-8');
		$time = time();
		$data = array(
			'TransCode' => '201201',//const value
			'OrderTime' => date('His', $time),//date hhmmss
			'EffectiveTime' => strval($exp ? $exp - $time : 0),
			'OrderDate' => date('Ymd', $time),//yyyymmdd
			'MerOrderId' => $datas['out_trade_no'],
			'TransType' => $type,//NoticePay or Pay
			'TransAmt' => strval($datas['total_fee']),
			'MerId' => $this->merchantId,
			'MerTermId' => $this->teamId,
			'NotifyUrl' => $datas['notify_url'],
			'OrderDesc' => $datas['product_name'],
			'MerSign' => ''
		);

		//签名
		$data['MerSign'] .= $data['OrderTime'];
		$data['MerSign'] .= $data['EffectiveTime'];
		$data['MerSign'] .= $data['OrderDate'];
		$data['MerSign'] .= $data['MerOrderId'];
		$data['MerSign'] .= $data['TransType'];
		$data['MerSign'] .= $data['TransAmt'];
		$data['MerSign'] .= $data['MerId'];
		$data['MerSign'] .= $data['MerTermId'];
		$data['MerSign'] .= $data['NotifyUrl'];
		$data['MerSign'] .= $data['OrderDesc'];

		$data['MerSign'] = $this->sign($data['MerSign']);
		//发送请求

        var_dump($data);

		foreach ($data as $key => $val) {
			$str.='"'.$key.'":'.'"'.$val.'",';
		}
		$result = $this->post($data, $this->URL);

        var_dump($result);

		foreach ($result as $keys => $vals) {
			$strs.='"'.$keys.'":'.'"'.$vals.'",';
		}
		//file_put_contents('xiangying.txt', $strs);
		$param['merSign']=$this->sign($result['TransId'].$result['ChrCode']);
		$param['ChrCode']=$result['ChrCode'];
		$param['TranId']=$result['TransId'];
		$param['url']=$datas['callback_url'];

        var_dump($param);exit;

		$shtml=$this->buildRequestForm($param,'post','去支付');
        echo $shtml;
	}

	function hex2bin($hex) {
		$n = strlen($hex);
		$bin = "";
		$i = 0;
		while($i < $n) {
			$a = substr($hex, $i, 2);
			$c = pack("H*", $a);
			if ($i == 0) {
				$bin = $c;
			} else {
				$bin .= $c;
			}
			$i+=2;
		}
		return $bin;
	}

	public function buildRequestForm($param, $method, $button_name) {
		//待请求参数数组
		
		$sHtml = "<form style='display:none' id='alipaysubmit' name='alipaysubmit' action='".$this->UMSURL."' method='".$method."'>";
		while (list ($key, $val) = each ($param)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";
		
		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
		
		return $sHtml;
	}


/**
	 * 订单查询
	 * 
	 * @param  string  $title   订单标题
	 * @param  number  $amt     订单金额,单位分
	 * @param  string  $orderId 订单id,由商户产生
	 * @param  string  $type    交易类型
	 * @param  integer $exp     过期时间
	 * @return mixed
	 
	 订单状态0:新订单, 1成功， 2失败，3支付中
	 
	 */
	function seek() {
		$time = time();
		$data = array(
			'TransCode' => '201203',//交易代码
			'ReqTime' => date('YmdHis', $time),//请求时间
			'OrderDate' => date('Ymd', $time),//订单日期
			'MerOrderId' => 'testwap001',//商户订单号
			'TransId' => '662013110525413784',//交易流水
			'MerId' => $this->merchantId, //商户号
			'MerTermId' => $this->termId, //终端号
			'Reserve'   => '',
			'MerSign' => ''
		);

		//签名
		$data['MerSign'] .= $data['ReqTime'];
		$data['MerSign'] .= $data['OrderDate'];
		$data['MerSign'] .= $data['MerOrderId'];
		$data['MerSign'] .= $data['TransId'];
		$data['MerSign'] .= $data['MerId'];
		$data['MerSign'] .= $data['MerTermId'];
		$data['MerSign'] .= $data['Reserve'];
		$data['MerSign'] = $this->sign($data['MerSign']);
		print "<pre>";
		print_r($data);
		print "</pre>";

		//发送请求
		$result = $this->post($data, self::ORDER_QUERY_URL);
		//验签
		$verify = '';
		$verify .= $result['OrderTime'];
		$verify .= $result['OrderDate'];
		$verify .= $result['MerOrderId'];
		$verify .= $result['TransType'];
		$verify .= $result['OrderTime'];
		
		$verify .= $result['TransAmt'];
		$verify .= $result['MerId'];
		$verify .= $result['MerTermId'];
		$verify .= $result['TransId'];
		$verify .= $result['TransState'];
		
		$verify .= $result['RefId'];
		$verify .= $result['Reserve'];
		$verify .= $result['RespCode'];
		$verify .= $result['RespMsg'];
		
		if(!isset($result['Signature']) || !$this->verify($verify, $result['Signature'])){
			//验签失败
			return false;
		}

		
		if(!isset($result['RespCode']) || intval($result['RespCode'])){
			//订单查询失败 详细信息参考$result['RespMsg']
			return false;
		}

		//业务逻辑处理
		//var_dump($result);
		return true;
	}

	/**
	 * 响应回调
	 * 
	 */
	function notify1(){
		$data = &$_POST;

		#验签
		$verify = '';
		$verify .= isset($data['OrderTime']) ? $data['OrderTime'] : '';
		$verify .= isset($data['OrderDate']) ? $data['OrderDate'] : '';
		$verify .= isset($data['MerOrderId']) ? $data['MerOrderId'] : '';
		$verify .= isset($data['TransType']) ? $data['TransType'] : '';
		$verify .= isset($data['TransAmt']) ? $data['TransAmt'] : '';
		$verify .= isset($data['MerId']) ? $data['MerId'] : '';
		$verify .= isset($data['MerTermId']) ? $data['MerTermId'] : '';
		$verify .= isset($data['TransId']) ? $data['TransId'] : '';
		$verify .= isset($data['TransState']) ? $data['TransState'] : '';
		$verify .= isset($data['RefId']) ? $data['RefId'] : '';
		$verify .= isset($data['Account']) ? $data['Account'] : '';
		$verify .= isset($data['TransDesc']) ? $data['TransDesc'] : '';
		$verify .= isset($data['Reserve']) ? $data['Reserve'] : '';
		
		//if(!$this->verify($verify, $data['Signature'])){
			//验签失败!
			//return false;
			
		//根据MerOrderId得到本地订单
		//做一些业务处理

		foreach ($data as $keysd=> $valsd) {
			$strsd.='"'.$keysd.'":'.'"'.$valsd.'",';
		}
		file_put_contents('notify.txt', $strsd);
		
		//响应数据
		$result = array(
			'TransCode' => '201202',
			'MerOrderId' => $data['MerOrderId'],
			'TransType' => 'NoticePay',
			'MerId' => $data['MerId'],
			'MerTermId' => $data['MerTermId'],
			'TransId' => $data['TransId'],
			'MerPlatTime' => date('YmdHis'),
			'MerOrderState' => '11',
			'Reserve' => '',
			'MerSign' => ''
		);
		
		
			switch ($data['TransState']) {
			case 1:
				$result['MerOrderState'] = '00';
				
				break;
			case 4:
				$result['MerOrderState'] = '00';
				
				break;
			case 5:
				
				break;
			case 6:
				
				break;
		}
		
		//签名
		$result['MerSign'] .= isset($result['MerOrderId']) ? $result['MerOrderId'] : '';
		$result['MerSign'] .= isset($result['TransType']) ? $result['TransType'] : '';
		$result['MerSign'] .= isset($result['MerId']) ? $result['MerId'] : '';
		$result['MerSign'] .= isset($result['MerTermId']) ? $result['MerTermId'] : '';
		$result['MerSign'] .= isset($result['TransId']) ? $result['TransId'] : '';
		$result['MerSign'] .= isset($result['MerPlatTime']) ? $result['MerPlatTime'] : '';
		$result['MerSign'] .= isset($result['MerOrderState']) ? $result['MerOrderState'] : '';
		$result['MerSign'] .= isset($result['Reserve']) ? $result['Reserve'] : '';
		
		$result['MerSign'] = $this->sign($result['MerSign']);

		$result['TransAmt']=$data['TransAmt'];
		
		//响应回调
		// exit(json_encode($result));
		return $result;
	}
    /**
     * 响应回调2015 拷贝m.youngt.com
     *
     */
    function notify(){
        $data = &$_POST;

        #验签
        $verify = '';
        $verify .= isset($data['OrderTime']) ? $data['OrderTime'] : '';
        $verify .= isset($data['OrderDate']) ? $data['OrderDate'] : '';
        $verify .= isset($data['MerOrderId']) ? $data['MerOrderId'] : '';
        $verify .= isset($data['TransType']) ? $data['TransType'] : '';
        $verify .= isset($data['TransAmt']) ? $data['TransAmt'] : '';
        $verify .= isset($data['MerId']) ? $data['MerId'] : '';
        $verify .= isset($data['MerTermId']) ? $data['MerTermId'] : '';
        $verify .= isset($data['TransId']) ? $data['TransId'] : '';
        $verify .= isset($data['TransState']) ? $data['TransState'] : '';
        $verify .= isset($data['RefId']) ? $data['RefId'] : '';
        $verify .= isset($data['Account']) ? $data['Account'] : '';
        $verify .= isset($data['TransDesc']) ? $data['TransDesc'] : '';
        $verify .= isset($data['Reserve']) ? $data['Reserve'] : '';

        //if(!$this->verify($verify, $data['Signature'])){
        //验签失败!
        //return false;

        //根据MerOrderId得到本地订单
        //做一些业务处理

        foreach ($data as $keysd=> $valsd) {
            $strsd.='"'.$keysd.'":'.'"'.$valsd.'",';
        }

        //响应数据
        $result = array(
            'TransCode' => '201202',
            'MerOrderId' => $data['MerOrderId'],
            'TransType' => 'NoticePay',
            'MerId' => $data['MerId'],
            'MerTermId' => $data['MerTermId'],
            'TransId' => $data['TransId'],
            'MerPlatTime' => date('YmdHis'),
            'MerOrderState' => '11',
            'Reserve' => '',
            'MerSign' => ''
        );


        switch ($data['TransState']) {
            case 1:
                $result['MerOrderState'] = '00';

                break;
            case 4:
                $result['MerOrderState'] = '00';

                break;
            case 5:

                break;
            case 6:

                break;
        }

        //签名
        $result['MerSign'] .= isset($result['MerOrderId']) ? $result['MerOrderId'] : '';
        $result['MerSign'] .= isset($result['TransType']) ? $result['TransType'] : '';
        $result['MerSign'] .= isset($result['MerId']) ? $result['MerId'] : '';
        $result['MerSign'] .= isset($result['MerTermId']) ? $result['MerTermId'] : '';
        $result['MerSign'] .= isset($result['TransId']) ? $result['TransId'] : '';
        $result['MerSign'] .= isset($result['MerPlatTime']) ? $result['MerPlatTime'] : '';
        $result['MerSign'] .= isset($result['MerOrderState']) ? $result['MerOrderState'] : '';
        $result['MerSign'] .= isset($result['Reserve']) ? $result['Reserve'] : '';

        $result['MerSign'] = $this->sign($result['MerSign']);

        $result['TransAmt']=$data['TransAmt'];

        //响应回调
        // exit(json_encode($result));
        return $result;
    }

	function callBack(){
		$result=$_GET;
		return $result;
	}
}
?>