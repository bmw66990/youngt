<?php
/**
 * 可可托海景区门票对接
 * Created by Sublime_Text_3.
 * User: zhoombin@126.com
 * Date: 16-5-17
 * Time: 上午10:25
 */

namespace Common\Org;

class Ota {
	// 测试接口
	// const SAOP_URL = 'http://139.129.17.46:8088/Service.asmx?WSDL';
	// const MERCHANT_CODE = 'qingtuanwanglvyou';
	// const MERCHANT_KEY  = '891B02E393A0AD73E709F4A1BD0A3B6F';

	// 正式接口
	const SAOP_URL = 'http://121.201.67.33:40005/Service.asmx?WSDL';
	const MERCHANT_CODE = 'qingtuanwang';
	const MERCHANT_KEY  = '54CD5C893C735F273237DA089796C3C1';

	private $client;

	public function __construct() {
		$this->client = new \SoapClient(self::SAOP_URL);
	}

	// 获取产品
	public function products($parkcode) {
		$timestamp = date('Y-m-d H:i:s', NOW_TIME);
		$date = date('Y-m-d', NOW_TIME);

		$params = json_encode(array(
			'parkCode'  => $parkcode,
			'timestamp' => $timestamp,
			'date'		=> $date
		));

		$data = array(
			'merchantCode' => self::MERCHANT_CODE,
			'parameters'   => $params,
			'signature'    => $this->getSign(self::MERCHANT_CODE.self::MERCHANT_KEY.$params.NOW_TIME)
		);
		$result = $this->client->GetProducts($data);
		return json_decode($result->GetProductsResult);
	}

	// 订单锁定
	public function orderLock($procuct, $order) {
		$datetime = date('Y-m-d H:i:s', NOW_TIME);
		$details = array(
			'orderNO' 		=> $order['order_id'],
			'ItemID' 		=> $order['team_id'],
			'ProductCode'	=> $procuct['ProductCode'],
			'ProductID'		=> $procuct['ProductID'],
			'ProductPackID'	=> $procuct['ProductPackID'],
			'ProductPrice'	=> $procuct['ProductPrice'],
			'ProductSellPrice' => $procuct['ProductSellPrice'],
			'ProductCount'	=> $order['quantity'],
			'ProductSDate'	=> $order['use_date'],
			'ProductEDate'	=> $order['use_date'],
		);
		$Order = array(
			'OrderNO'     => $order['order_id'],
			'LinkName'    => $order['link_name'],
			'LinkPhone'   => $order['mobile'],
			'LinkICNO'    => $order['link_cno'],
			'TotalAmount' => $order['price'],
			'CreateTime'  => $datetime
		);
		$postorder = array(
			'Ptime'     => $datetime,
			'parkCode'  => $procuct['ParkCode'],
			'timestamp' => $datetime,
			'Order'     => json_encode($Order),
			'Details'   => json_encode(array($details))
		);

		$params = json_encode($postorder);

		$data = array(
			'merchantCode' => self::MERCHANT_CODE,
			'postOrder'    => json_encode($postorder),
			'signature'    => $this->getSign(self::MERCHANT_CODE.self::MERCHANT_KEY.$params.NOW_TIME)
		);
		$result = $this->client->OrderOccupies($data);
		return json_decode($result->OrderOccupiesResult);
	}

	// 支付成功，结束订单
	public function orderEnd($order_id, $parkcode) {
		$timestamp = date('Y-m-d H:i:s', NOW_TIME);
		$params = array(
			'otaOrderNO' => $order_id,
			'parkCode'   => $parkcode,
			'timestamp'  => $timestamp
		);
		$params = json_encode($params);
		$data = array(
			'otaCode'      => self::MERCHANT_CODE,
			'parameters'   => $params,
			'signature'    => $this->getSign(self::MERCHANT_CODE.self::MERCHANT_KEY.$params.NOW_TIME)
		);
		$result = $this->client->OrderFinish($data);
		return json_decode($result->OrderFinishResult);
	}

	// 释放订单
	public function orderRelease($order_id, $parkcode) {
		$timestamp = date('Y-m-d H:i:s', NOW_TIME);
		$params = array(
			'otaOrderNO' => $order_id,
			'parkCode'   => $parkcode,
			'timestamp'  => $timestamp
		);
		$params = json_encode($params);
		$data = array(
			'otaCode'      => self::MERCHANT_CODE,
			'parameters'   => $params,
			'signature'    => $this->getSign(self::MERCHANT_CODE.self::MERCHANT_KEY.$params.NOW_TIME)
		);
		$result = $this->client->OrderRelease($data);
		return json_decode($result->OrderReleaseResult);
	}

	// 修改订单
	public function orderRefund($info) {
		$timestamp = date('Y-m-d H:i:s', NOW_TIME);
		$params = array(
			'Ptime'       => $timestamp,
			'parkCode'    => $info['parkcode'],
			'timestamp'   => $timestamp,
			'Order'       => json_encode(array('OrderNo'=>$info['order_id'])),
			'Details'     => json_encode(array(array('ProductCode'=>$info['ecode']))),
			'Edittype'    => '2'
			
		);
		$params = json_encode($params);
		$data = array(
			'merchantCode' => self::MERCHANT_CODE,
			'parameters'   => $params,
			'signature'    => $this->getSign(self::MERCHANT_CODE.self::MERCHANT_KEY.$params.NOW_TIME)
		);
		$result = $this->client->ChangeOrderEdit($data);
		return json_decode($result->ChangeOrderEditResult);
	}

	// 获取订单状态
	public function getOrderStatus($order_id, $parkcode, $ecode) {
		$timestamp = date('Y-m-d H:i:s', NOW_TIME);
		$params = array(
			'otaOrderNO' => $order_id,
			'parkCode'   => $parkcode,
			'timestamp'  => $timestamp,
			'postOrder'  => array(array('OrderCode'=>$ecode))
		);
		$params = json_encode($params);
		$data = array(
			'merchantCode' => self::MERCHANT_CODE,
			'parameters'   => $params,
			'signature'    => $this->getSign(self::MERCHANT_CODE.self::MERCHANT_KEY.$params.NOW_TIME)
		);
		$result = $this->client->GetAllOrderStatus($data);
		return json_decode($result->GetAllOrderStatusResult);
	}

	// 验证回调数据
	public function checkData($merCode,$params,$sign) {
		if ($merCode != self::MERCHANT_CODE) {
            return array('ResultCode' => '01','ResultMsg'  => 'merCode error');
        }
        if ($params == '') {
            return array('ResultCode' => '02','ResultMsg'  => 'params is empty');
        }
        $data = json_decode($params);
        if (!$this->checkSign($params,$sign,strtotime($data->timestamp))) {
            return array('ResultCode' => '03','ResultMsg'  => 'sign check error');
        }
        return true;
	}

	// 验证签名
	private function checkSign($params,$sign,$timestamp = NOW_TIME) {
		$newsign = $this->getSign(self::MERCHANT_CODE.self::MERCHANT_KEY.$params.$timestamp);
		return $newsign == $sign;
	}

	// 生成签名
	private function getSign($content) {
		return base64_encode(strtoupper(md5($content)));
	}
	
}
