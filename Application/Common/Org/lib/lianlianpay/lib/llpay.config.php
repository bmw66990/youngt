<?php

/* *
 * 配置文件
 * 版本：1.1
 * 日期：2014-04-16
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//商户编号是商户在连连钱包支付平台上开设的商户号码，为18位数字，如：201304121000001004
//$llpay_config['oid_partner'] = '201408071000001546';
$llpay_config['oid_partner'] = '201504291000304506';

//安全检验码，以数字和字母组成的字符
 //$llpay_config['key'] = '201408071000001546_test_20140815';
//$llpay_config['key'] = '201504291000304506_youngt_304506';
$llpay_config['key'] = '201504291000304506_qtwyoungt_506';

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//版本号
$llpay_config['version'] = '1.1';

//请求应用标识 为wap版本，不需修改
$llpay_config['app_request'] = '3';


//签名方式 不需修改
$llpay_config['sign_type'] = strtoupper('MD5');

//订单有效时间  分钟为单位，默认为10080分钟（7天） 
$llpay_config['valid_order'] ="10080";

//字符编码格式 目前支持 gbk 或 utf-8
$llpay_config['input_charset'] = strtolower('utf-8');

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$llpay_config['transport'] = 'http';

// 注册手机号
$llpay_config['register_iphone'] = '15202455001';
// 注册手机号
$llpay_config['register_time'] = '201504291000';

?>