<?php
/**
* 	配置账号信息
*/


if (!defined('SSLCERT_PATH')) {
    define('SSLCERT_PATH', dirname(__DIR__) . '/cert/apiclient_cert.pem');
}

if (!defined('SSLKEY_PATH')) {
    define('SSLKEY_PATH', dirname(__DIR__) . '/cert/apiclient_key.pem');
}

class WxPayConf_pub
{

	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = 'wx5aaa6db815374f64';//wx71ef1edff818d209
	//受理商ID，身份标识
	const MCHID = '1386918802';//1239642502
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = '85e917b69a1d2440a34ec50d557aafb3';
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = 'bee335ac4a3f5ee2878e2dcba1835a1a';//bee335ac4a3f5ee2878e2dcba1835a1a
	
	//=======【JSAPI路径设置】===================================
	//获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
	const JS_API_CALL_URL = 'http://fanli.ree9.com/Wechat';
	
	//=======【证书路径设置】=====================================
	//证书路径,注意应该填写绝对路径
	const SSLCERT_PATH = '../cert/apiclient_cert.pem';
	const SSLKEY_PATH = '../cert/apiclient_key.pem';
	
	//=======【异步通知url设置】===================================
	//异步通知url，商户根据实际开发过程设定
	const NOTIFY_URL = 'http://fanli.ree9.com/Wechat/notify';

	//=======【curl超时设置】===================================
	//本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
	const CURL_TIMEOUT = 30;
}
	
?>