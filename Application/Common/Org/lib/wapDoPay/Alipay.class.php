<?php
class Alipay{
	private static $instance;
	private $aliPath;
	private $alipay_config;
	private $alipaySubmit;
	private $alipayNotify;

	private function __construct(){
		$this->aliPath = dirname(__FILE__).'/alipay/';

		require_once($this->aliPath.'alipay.config.php');
		$this->alipay_config = $alipay_config;

		require_once($this->aliPath.'lib/alipay_submit.class.php');
		$this->alipaySubmit = new AlipaySubmit($alipay_config);

		require_once($this->aliPath.'lib/alipay_notify.class.php');
		$this->alipayNotify = new AlipayNotify($alipay_config);
	}

	public static function getInstance(){
		if(!self::$instance instanceof self){
			self::$instance = new self();
		}
		return self::$instance;
	}

	// 提交到支付宝
	public function doPay($data,$option){

		//卖家支付宝帐户
		$seller_email = 'youngt_com@163.com';
		//必填

		//服务器异步通知页面路径
		$notify_url = $option['notify_url'];
		//需http://格式的完整路径，不允许加?id=123这类自定义参数

		//页面跳转同步通知页面路径
		$call_back_url = $option['return_url'];
		//需http://格式的完整路径，不允许加?id=123这类自定义参数

		//操作中断返回地址
		$merchant_url = $option['return_CodeUrl'];
		//用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数


		/**************************调用授权接口alipay.wap.trade.create.direct获取授权码token**************************/
	
		//返回格式
		$format = "xml";
		//必填，不需要修改

		//返回格式
		$v = "2.0";
		//必填，不需要修改

		//请求号
		$req_id = date('Ymdhis');
		//必填，须保证每次请求都是唯一

		//请求业务参数详细
		$req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' .$seller_email . '</seller_account_name><out_trade_no>' . $data['out_trade_no'] . '</out_trade_no><subject>' . $data['subject'] . '</subject><total_fee>' . $data['total_fee'] . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url><out_user>'.$data['muser_id'].'</out_user></direct_trade_create_req>';
		//必填

		/************************************************************/

		//构造要请求的参数数组，无需改动
		$para_token = array(
				"service" => "alipay.wap.trade.create.direct",
				"partner" => trim($this->alipay_config['partner']),
				"sec_id" => trim($this->alipay_config['sign_type']),
				"format"	=> $format,
				"v"	=> $v,
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset" => trim(strtolower($this->alipay_config['input_charset']))
		);
		//建立请求
		$alipaySubmit = new AlipaySubmit($this->alipay_config);
		$html_text = $alipaySubmit->buildRequestHttp($para_token);

		//URLDECODE返回的信息
		$html_text = urldecode($html_text);

		//解析远程模拟提交后返回的信息
		$para_html_text = $alipaySubmit->parseResponse($html_text);

		//获取request_token
		$request_token = $para_html_text['request_token'];


		/**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

		//业务详细
		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
		//必填

		//构造要请求的参数数组，无需改动
		$parameter = array(
				"service" => "alipay.wap.auth.authAndExecute",
				"partner" => trim($this->alipay_config['partner']),
				"sec_id" => trim($this->alipay_config['sign_type']),
				"format"	=> $format,
				"v"	=> $v,
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset" => trim(strtolower($this->alipay_config['input_charset']))
		);

		//建立请求
		$alipaySubmit = new AlipaySubmit($this->alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter, 'get', 'doPay');
		echo $html_text;
	}

	// 获取异步验证结果数据
	public function verifyNotify(){
		return $this->alipayNotify->verifyNotify();
	}

	// 获取同步验证结果数据
	public function verifyReturn(){
		return $this->alipayNotify->verifyReturn();
	}

	// 解密数据
	public function decrypt($prestr){
		return $this->alipayNotify->decrypt($prestr);
	}

	// 写入文件
	public function logResult($word=''){
		require_once($this->aliPath.'lib/alipay_core.function.php');
		logResult($word);
	}
}
?>