﻿<?php

// cvn2加密 1：加密 0:不加密
        const SDK_CVN2_ENC = 0;
// 有效期加密 1:加密 0:不加密
        const SDK_DATE_ENC = 0;
// 卡号加密 1：加密 0:不加密
        const SDK_PAN_ENC = 0;

if (defined('WAP_UNION_PAY_MODEL') && WAP_UNION_PAY_MODEL) {
    // 商户号
    define('MER_ID', '802110048991148');
    // 签名证书路径
    define('SDK_SIGN_CERT_PATH', dirname(__DIR__) . '/certs/private_1148.pfx');
    // 签名证书密码
    define('SDK_SIGN_CERT_PWD', '991148');
} else {

    // 商户号
    define('MER_ID', '802110048991147');
    // 签名证书路径
    define('SDK_SIGN_CERT_PATH', dirname(__DIR__) . '/certs/private.pfx');
    // 签名证书密码
    define('SDK_SIGN_CERT_PWD', '991147');
}



// 密码加密证书（这条用不到的请随便配）
define('SDK_ENCRYPT_CERT_PATH', dirname(__DIR__) . '/certs/UpopRsaCert.cer');

// 验签证书路径（请配到文件夹，不要配到具体文件）
// const SDK_VERIFY_CERT_DIR = 'D:/certs/';
define('SDK_VERIFY_CERT_DIR', dirname(__DIR__) . '/certs/');

// 前台请求地址
        const SDK_FRONT_TRANS_URL = 'https://gateway.95516.com/gateway/api/frontTransReq.do';
// 后台请求地址
        const SDK_BACK_TRANS_URL = 'https://gateway.95516.com/gateway/api/backTransReq.do';
// 批量交易
        const SDK_BATCH_TRANS_URL = 'https://gateway.95516.com/gateway/api/batchTrans.do';
//单笔查询请求地址
        const SDK_SINGLE_QUERY_URL = 'https://gateway.95516.com/gateway/api/queryTrans.do';
//文件传输请求地址
        const SDK_FILE_QUERY_URL = 'https://filedownload.95516.com/';
//有卡交易地址
        const SDK_Card_Request_Url = 'https://gateway.95516.com/gateway/api/cardTransReq.do';
//App交易地址
        const SDK_App_Request_Url = 'https://gateway.95516.com/gateway/api/appTransReq.do';
// 前台通知地址 (商户自行配置通知地址)
        const SDK_FRONT_NOTIFY_URL = 'http://localhost:8085/upacp_sdk_php/demo/utf8/FrontReceive.php';
// 后台通知地址 (商户自行配置通知地址)
        const SDK_BACK_NOTIFY_URL = 'http://114.82.43.123/upacp_sdk_php/demo/utf8/BackReceive.php';

//文件下载目录 
//const SDK_FILE_DOWN_PATH = 'd:/file/';
define('SDK_FILE_DOWN_PATH', dirname(__DIR__) . '/logs/');

//日志 目录 
//const SDK_LOG_FILE_PATH = 'd:/logs/';
define('SDK_LOG_FILE_PATH', dirname(__DIR__) . '/logs/');

//日志级别
        const SDK_LOG_LEVEL = 'INFO';
?>