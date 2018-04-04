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
 * 交易查询-验签
 *
 * @author wylitu
 *
 */
class WepayQuery {

	public function query($tradeNum) {
		$params = $this->prepareParms ($tradeNum);
		$data = json_encode ($params);
		list ( $return_code, $return_content ) = HttpUtils :: http_post_data (ConfigUtil::get_val_by_key("serverQueryUrl"), $data);
		$return_content = str_replace("\n", '', $return_content);
		$return_data = json_decode ($return_content,true);

		// 执行状态 成功
		$res_data ['errorMsg'] = null;
		$res_data ['queryDatas'] =null;

		if ($return_data['resultCode'] == 0) {
			$mapResult = $return_data['resultData'];
			// 有返回数据
			if (null != $mapResult) {
				$data = $mapResult["data"];
				$sign = $mapResult["sign"];

				// 1.解密签名内容
				$decryptStr = RSAUtils::decryptByPublicKey($sign);

				// 2.对data进行sha256摘要加密
				$sha256SourceSignString = hash ( "sha256",$data);

				// 3.比对结果
				if ($decryptStr == $sha256SourceSignString) {
					/**
					 * 验签通过
					 */
					// 解密data
					$decrypData = TDESUtil::decrypt4HexStr(base64_decode(ConfigUtil::get_val_by_key("desKey")),$data);
					// 注意 结果为List集合
					$decrypData = json_decode ( $decrypData, true );
					//var_dump($decrypData);
					// 错误消息
					if (count ( $decrypData ) < 1) {
						$res_data ['errorMsg'] = decrypData;
						$res_data ['queryDatas'] =null;
					} else {
						$res_data ['queryDatas'] = $decrypData;
					}
				} else {
					/**
					 * 验签失败 不受信任的响应数据
					 * 终止
					 */
					$res_data ['errorMsg'] ="验签失败!";
				}
			} else {
				$res_data ['errorMsg'] ="请求失败!";
			}
		} else {
			$res_data ['errorMsg'] = $return_data ['resultMsg'];
			$res_data ['queryDatas'] = null;
		}

		return $res_data;
	}

	public function prepareParms($tradeNum) {

		$tradeJsonData = "{\"tradeNum\": \"". $tradeNum."\"}";

		// 1.对交易信息进行3DES加密
		$tradeData = TDESUtil::encrypt2HexStr(base64_decode(ConfigUtil::get_val_by_key("desKey")),$tradeJsonData);

		// 2.对3DES加密的数据进行签名
		$sha256SourceSignString = hash ( "sha256", $tradeData );
		$sign = RSAUtils::encryptByPrivateKey ( $sha256SourceSignString);

		$params = array ();
		$params ["version"] = $_POST ["version"];
		$params ["merchantNum"] = $_POST ["merchantNum"];
		$params ["merchantSign"] = $sign;
		$params ["data"] = $tradeData;
		return $params;
	}
}

// $webQuerySign = new WebQuerySign ();
// $webQuerySign->query ();

?>