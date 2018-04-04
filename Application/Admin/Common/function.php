<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-06-09
 * Time: 15:04
 */
function getOrderPayType($id) {
    $str="";
    switch ($id){
        case 'aliapp':
            $str = "支付宝客户端支付";
            break;
        case 'aliwap':
            $str = "支付宝wap支付";
            break;
        case 'alipay':
            $str = "支付宝pc支付";
            break;
        case 'tenapp':
            $str = "财付通客户端支付";
            break;
        case 'tenpay':
            $str = "财付通pc支付";
            break;
        case 'tenwap':
            $str = "财付通wap支付";
            break;
        case 'wechatpay':
            $str = "财付通微信支付";
            break;
        case 'umspay':
            $str = "全民付";
            break;
        case 'credit':
            $str = "余额支付";
            break;
        default :
            $str = "未知";
    }
    return $str;
}


function getTeamUrl($id) {
    return rtrim(C('YOUNGT_URL'), '\/') . '/team-' . $id . '.html';
}

/**
 * 权限检测
 * @param type $access_name
 * @return boolean
 */
function auth_check_access($access_name=array()){
    if(is_string($access_name)){
        $access_name = @explode(',', $access_name);
    }
     $module_name = strtolower(MODULE_NAME);
     $auth = new \Common\Org\Auth();
     $auth_config =  C('AUTH_CONFIG');
     $user = session(C('SAVE_USER_KEY'));
     $uid = ternary($user['id'], '');
    if($access_name){
        foreach($access_name as $v){
             $name = strtolower($v);
            
            if(strpos($v, $module_name) === false){
                 $name = "$module_name/$name";
            }
            if($auth->auth_check_access($uid, $auth_config,$name)){
                return $v;
            }
        }
    }
    return false;
}

/**
 * 获取订单来源
 */
function getOrderFrom($str){
    switch (strtolower($str)) {
        case 'pc':
            $data = '电脑PC';
            break;
        case 'newandroid':
        case 'android':
            $data = '安卓客户端';
            break;
        case 'ios':
        case 'newios':
            $data = 'iOS客户端';
            break;
        case 'mobile.youngt.com':
        case 'm.youngt.com':
            $data = '手机WAP';
            break;
        default:
            $data = '未知';
    }
    return $data;
}


?>