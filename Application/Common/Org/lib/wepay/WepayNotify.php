<?php
namespace wepay;

use \wepay\common\DesUtils;

include __DIR__.'/common/DesUtils.php';

/**
 * 接收异步通知控制器
 *
 * @author wylitu
 *
 */
class WepayNotify{

	public function xml_to_array($xml){
		$array = (array)(simplexml_load_string ($xml));
		foreach ($array as $key => $item){
			$array[$key] = $this->struct_to_array ($item);
		}
		return $array;
	}
	public function struct_to_array($item){
		if (is_string($item)) {
			$item = array($item);
		} else {
			$item = (array)$item;
			foreach($item as $key => $val){
				$item [$key] = $this->struct_to_array ($val);
			}
		}
		return $item;
	}

	/**
	 * 签名
	 */
	public function generateSign($data, $md5Key) {
		$sb = $data ['VERSION'] [0] . $data ['MERCHANT'] [0] . $data ['TERMINAL'] [0] . $data ['DATA'] [0] . $md5Key;

		return md5 ( $sb );
	}

	public function verify($md5Key, $desKey, $resp) {
		if (null == $resp) {
			return false;
		}
		// 解析XML
		$params = $this->xml_to_array ( base64_decode ( $resp ) );

		// print_r($params);exit;
		// file_put_contents('./tmp/pay_callback.log', 'AAA: '.date('Y-m-d H:i:s').": \n", FILE_APPEND);
  //       file_put_contents('./tmp/pay_callback.log', var_export($params,true), FILE_APPEND);

		$ownSign = $this->generateSign ( $params, $md5Key );
		if ($params ['SIGN'] [0] != $ownSign) {
			return false;
		}

		// file_put_contents('./tmp/pay_callback.log', 'BBB: '.date('Y-m-d H:i:s').": \n", FILE_APPEND);
  //       file_put_contents('./tmp/pay_callback.log', var_export($params,true), FILE_APPEND);

		$des = new DesUtils (); // （秘钥向量，混淆向量）
		$decryptData = $des->decrypt ( $params ['DATA'] [0], $desKey ); // 加密字符串
		if (!$decryptData) {
			return false;
		}

		// file_put_contents('./tmp/pay_callback.log', 'CCC: '.date('Y-m-d H:i:s').": \n", FILE_APPEND);
  //       file_put_contents('./tmp/pay_callback.log', var_export($params,true), FILE_APPEND);

		$params ['data'] = $this->xml_to_array ($decryptData);
		return $params;
	}
}

