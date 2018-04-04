<?php
class Tenpay
{
	private static $instance;
	private $tenPath;
	private $partner;
	private $key;

	private function __construct(){
		$this->tenPath = dirname(__FILE__).'/tenpay/';
		$this->partner = '1215943701';
		$this->key ='653eebc44b94017588059bded99bf161';
	}

	public static function getInstance(){
		if(!self::$instance instanceof self){
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function dopay($data, $option){
		require_once($this->tenPath.'RequestHandler.class.php');
		require_once($this->tenPath.'client/ClientResponseHandler.class.php');
		require_once($this->tenPath.'client/TenpayHttpClient.class.php');
		$reqHandler = new RequestHandler();

		$reqHandler->init();
		$reqHandler->setKey($this->key);
		//设置初始化请求接口，以获得token_id
		$reqHandler->setGateUrl('http://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_init.cgi');
		$httpClient = new TenpayHttpClient();
		$resHandler = new ClientResponseHandler();

		$reqHandler->setParameter("total_fee", $data['total_fee']);  //总金额
		//用户ip
		$reqHandler->setParameter("spbill_create_ip", get_client_ip());//客户端IP
		$reqHandler->setParameter("ver", "2.0");//版本类型
		$reqHandler->setParameter("bank_type", "0"); //银行类型，财付通填写0
		$reqHandler->setParameter("callback_url", $option['return_url']);//交易完成后跳转的URL
		$reqHandler->setParameter("bargainor_id", $this->partner); //商户号
		$reqHandler->setParameter("sp_billno", $data['out_trade_no']); //商户订单号
        $reqHandler->setParameter("fee_type", 1);
		$reqHandler->setParameter("notify_url", $option['notify_url']);//接收财付通通知的URL，需绝对路径
		$reqHandler->setParameter("desc", $data['product_name']);
        $reqHandler->setParameter("time_start", date('YmdHis'));

//        dump($reqHandler->getRequestURL());
//        dump($reqHandler->getAllParameters());exit;

		$httpClient->setReqContent($reqHandler->getRequestURL());

		//后台调用
		if($httpClient->call()) {
			$resHandler->setContent($httpClient->getResContent());
			//获得的token_id，用于支付请求
			$token_id = $resHandler->getParameter('token_id');

			$reqHandler->setParameter("token_id", $token_id);

			//请求的URL
			//$reqHandler->setGateUrl("https://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_gate.cgi");
			//此次请求只需带上参数token_id就可以了，$reqUrl和$reqUrl2效果是一样的
			//$reqUrl = $reqHandler->getRequestURL(); 
			$reqUrl = "http://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_gate.cgi?token_id=" . $token_id;
				
		}
		 echo "<script>window.location.href='".$reqUrl."';</script>";
	}


	function VerifyNotify(){
		require_once ($this->tenPath."ResponseHandler.class.php");
		require_once ($this->tenPath."WapNotifyResponseHandler.class.php");

		/* 创建支付应答对象 */
		$resHandler = new WapNotifyResponseHandler();
		$resHandler->setKey($this->key);

		//判断签名
		if($resHandler->isTenpaySign()) {
			
			//商户订单号
			$data['sp_billno'] = $resHandler->getParameter("sp_billno");
			
			//财付通交易单号
			$data['transaction_id'] = $resHandler->getParameter("transaction_id");
			//金额,以分为单位
			$data['total_fee'] = $resHandler->getParameter("total_fee");
			
			//支付结果
			$pay_result = $resHandler->getParameter("pay_result");

			if( "0" == $pay_result  ) {
				$data['total_fee']=$data['total_fee']/100;
				$data['status']=1;
				return $data;
			}
			else
			{
				echo 'fail';
			} 
			
		} else {
			//回调签名错误
			echo "fail";
		}


	}


	function VerifyReturn(){
		require_once ($this->tenPath."ResponseHandler.class.php");
		require_once ($this->tenPath."WapResponseHandler.class.php");


		/* 创建支付应答对象 */
		$resHandler = new WapResponseHandler();
		$resHandler->setKey($this->key);

		//判断签名
		if($resHandler->isTenpaySign()) {

			//商户订单号
			$data['sp_billno'] = $resHandler->getParameter("sp_billno");
			//财付通交易单号
			$data['transaction_id'] = $resHandler->getParameter("transaction_id");
			//金额,以分为单位
			$data['total_fee'] = $resHandler->getParameter("total_fee");
			//支付结果
			$pay_result = $resHandler->getParameter("pay_result");

			if( "0" == $pay_result  ) {
				$data['status']=1;
				return $data;
			
			} else {
				//当做不成功处理
				$string =  "<br/>" . "支付失败" . "<br/>";
			}
			
		} else {
			$string =  "<br/>" . "认证签名失败" . "<br/>";
		}

	}
}
?>