<?php
namespace wepay;

use \wepay\common\RSAUtils;
use \wepay\common\TDESUtil;
use \wepay\common\ConfigUtil;
use \wepay\common\HttpUtils;

include __DIR__.'/common/RSAUtils.php';
include __DIR__.'/common/TDESUtil.php';
include __DIR__.'/common/ConfigUtil.php';
include __DIR__.'/common/HttpUtils.php';

/**
 * 退款-验签
 * @author wylitu
 *
 */
class WepayRefund {
	// 请求接口
	public function execute($parms) {
		$params = $this->prepareParms($parms);
		$data = json_encode ($params);
		list ( $return_code, $return_content ) =  HttpUtils :: http_post_data (ConfigUtil::get_val_by_key("serverRefundUrl"), $data );
		$return_content = str_replace("\n", '', $return_content);
		$return_data = json_decode ($return_content,true);
		return $this->dataVerify($return_data);
	}

	public function refoundVerify() {
		$res = $this->dataVerify($_POST);
		if (!isset($res['errorMsg']) || $res['errorMsg'] == null) {
            $tdata = $res['resultData']['data'];
            list($_, $order_id, $_, $_,) = explode('-', $tdata['tradeNum']);
            $r_data = array(
                'order_id' => $order_id,
                'trade_no' => '0',
                'refund_money' => sprintf("%.2f", ternary($tdata['tradeAmount'] / 100, '')),
                'refund_mark' => "申请退款成功，退款流水号：{$tdata['tradeNum']}",
                'refund_ptime' => time(),
            );
            return $r_data;
        }
        return false;
	}

	// 数据校验
	public function dataVerify($return_data) {
		$res_data ['errorMsg'] = null;
		$res_data ['resultData'] = null;
		//执行状态 成功
		if ($return_data['resultCode'] == 0) {
			$mapResult =  $return_data ['resultData'];

			//有返回数据
			if (null != $mapResult) {
				$data = $mapResult["data"];
				$sign = $mapResult["sign"];
				//1.解密签名内容
				$decryptStr = RSAUtils::decryptByPublicKey($sign);

				//2.对data进行sha256摘要加密
				$sha256SourceSignString = hash ( "sha256",$data);

				//3.比对结果
				if ($decryptStr == $sha256SourceSignString) {
					/**
					 * 验签通过
					 */
					//解密data
					$decrypData = TDESUtil::decrypt4HexStr(base64_decode(ConfigUtil::get_val_by_key("desKey")),$data);

					//退款结果实体
					$resultData= json_decode($decrypData,true);

					//错误消息
					if(null==$resultData){
						$res_data['errorMsg'] = $decrypData;
					}
					else{
						$res_data['resultData'] = $resultData;
					}
				} else {
					/**
					 * 验签失败  不受信任的响应数据
					 * 终止
					 */
					$res_data ['errorMsg'] ="签名失败!";

				}
			} else {
				$res_data['errorMsg'] = "请求失败!";
			}
		} else {
			$res_data['errorMsg'] = $return_data['resultMsg'];
		}
		return $res_data;
	}

	public function  prepareParms($parms){

		$tradeJsonData= "{\"tradeNum\": \"".$parms["tradeNum"]."\",\"oTradeNum\": \"".$parms["oTradeNum"]."\",\"tradeAmount\":\"".$parms["tradeAmount"]."\",\"tradeCurrency\": \"".$parms["tradeCurrency"]."\",\"tradeDate\": \"".$parms["tradeDate"]."\",\"tradeTime\": \"".$parms["tradeTime"]."\",\"tradeNotice\": \"".$parms["tradeNotice"]."\",\"tradeNote\": \"".$parms["tradeNote"]."\"}";

		$tradeData = TDESUtil::encrypt2HexStr(base64_decode(ConfigUtil::get_val_by_key("desKey")),$tradeJsonData);

		$sha256SourceSignString = hash ( "sha256", $tradeData);
        $sign = RSAUtils::encryptByPrivateKey ($sha256SourceSignString);
		$params= array();
		$params["version"] = $parms["version"];
		$params["merchantNum"] = $parms["merchantNum"];
		$params["merchantSign"] = $sign;
		$params["data"] = $tradeData;

		return $params;
	}

}

// $webRefundSign = new WebRefundSign();
// $webRefundSign->execute($parms);

?>