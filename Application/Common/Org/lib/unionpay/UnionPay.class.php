<?php

header('Content-type:text/html;charset=utf-8');
include_once __DIR__ . '/lib/common.php';
include_once __DIR__ . '/lib/SDKConfig.php';
include_once __DIR__ . '/lib/secureUtil.php';
include_once __DIR__ . '/lib/httpClient.php';

class UnionPay {

    /**
     * 获取手机端支付参数
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     * @param type $notify_url
     * @return type
     */
    public function getAppUnionPayData($payId, $title, $product, $payFee, $plat, $notify_url) {
        $product = str_replace(array('，', "'", ' ', '+', '！', '!'), "", $product);
        $payFee = sprintf("%.2f", $payFee);
        $txnTime = date('YmdHis');
        $data = array(
            'version' => '5.0.0', //版本号
            'encoding' => 'utf-8', //编码方式
            'certId' => getSignCertId(), //证书ID
            'txnType' => '01', //交易类型	
            'txnSubType' => '01', //交易子类
            'bizType' => '000201', //业务类型
            'backUrl' => $notify_url, //后台通知地址	
            'signMethod' => '01', //签名方法
            'channelType' => '08', //渠道类型，07-PC，08-手机
            'accessType' => '0', //接入类型
            'merId' => MER_ID, //商户代码，请改自己的测试商户号
            'orderId' => $payId, //商户订单号，8-40位数字字母
            'txnTime' => $txnTime, //订单发送时间
            'txnAmt' => strval(($payFee * 100)), //交易金额，单位分
            'currencyCode' => '156', //交易币种
            //'orderDesc' => $product, //订单描述，可不上送，上送时控件中会显示该信息
            'orderDesc' => '青团id:'.$payId,

        );
        S("unionpay_{$payId}",$txnTime,7200*12);
        sign($data);
        $result = sendHttpRequest($data, SDK_App_Request_Url);
        $result = coverStringToArray($result);
        return isset($result['tn']) && trim($result['tn']) ? $result['tn'] : '';
    }

    /**
     * 获取wap支付参数
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     * @param type $notify_url
     * @return type
     */
    public function getWapUnionPayData($payId, $title, $product, $payFee, $plat, $notify_url, $sysn_url) {
        $product = str_replace(array('，', "'", ' ', '+', '！', '!'), "", $product);
        $payFee = sprintf("%.2f", $payFee);
        $txnTime = date('YmdHis');
        $data = array(
            'version' => '5.0.0', //版本号
            'encoding' => 'utf-8', //编码方式
            'certId' => getSignCertId(), //证书ID
            'txnType' => '01', //交易类型	
            'txnSubType' => '01', //交易子类
            'bizType' => '000201', //业务类型
            'frontUrl' => $sysn_url, //前台通知地址
            'backUrl' => $notify_url, //后台通知地址	
            'signMethod' => '01', //签名方法
            'channelType' => '08', //渠道类型，07-PC，08-手机
            'accessType' => '0', //接入类型
            'merId' => MER_ID, //商户代码，请改自己的测试商户号
            'orderId' => $payId, //商户订单号
            'txnTime' => $txnTime, //订单发送时间
            'txnAmt' => strval(($payFee * 100)), //交易金额，单位分
            'currencyCode' => '156', //交易币种
            'defaultPayType' => '0001', //默认支付方式	
        );
        S("unionpay_{$payId}",$txnTime,7200*12);
        sign($data);
        $html_form = create_html($data, SDK_FRONT_TRANS_URL);
        die($html_form);
    }

    /**
     * 手机支付回调校验
     */
    public function getAppUnionPayVerify() {
        if (isset($_POST ['signature'])) {
            $verify_res = verify($_POST);
            if ($verify_res || (isset($_POST['respCode']) && trim($_POST['respCode'])=='00')) {
                $data = array(
                    'orderId' => ternary($_POST['orderId'], ''),
                    'txnAmt' => sprintf("%.2f", ternary($_POST['txnAmt'], '0') * 0.01),
                    'queryId' => ternary($_POST['queryId'], $_POST['origQryId']),
                );
                if (isset($_POST['origQryId']) && trim($_POST['origQryId'])) {
                    list($_,$order_id) = explode('U', $_POST['orderId']);
                    $data = array(
                        'order_id'=>$order_id,
                        'trade_no' => ternary($_POST['origQryId'], ''),
                        'refund_money' => sprintf("%.2f", ternary($_POST['txnAmt'] / 100, '')),
                        'refund_mark' => "申请退款成功，退款流水号：" . ternary($_POST['queryId'], ''),
                    );
                }
                return $data;
            }
        }
        return false;
    }

    /**
     * 查询订单
     * @param type $order_id
     * @param type $order_time
     */
    public function getUnionPayQuery($order_id, $order_time) {
        $order_time = substr($order_time, 0, 14);
        if(!$order_time){
            $order_time = S("unionpay_{$order_id}");
            if(!$order_time){
                 $order_time = date('YmdHis');
            }
        }
        $data = array(
            'version' => '5.0.0', //版本号
            'encoding' => 'utf-8', //编码方式
            'certId' => getSignCertId(), //证书ID	
            'signMethod' => '01', //签名方法
            'txnType' => '00', //交易类型	
            'txnSubType' => '00', //交易子类
            'bizType' => '000000', //业务类型
            'accessType' => '0', //接入类型
            'channelType' => '07', //渠道类型
            'orderId' => $order_id, //请修改被查询的交易的订单号
            'merId' => MER_ID, //商户代码，请修改为自己的商户号
            'txnTime' => $order_time, //请修改被查询的交易的订单发送时间
        );
        sign($data);
        $result = sendHttpRequest($data, SDK_SINGLE_QUERY_URL);
        $result = coverStringToArray($result);
        if(isset($result['respCode']) && trim($result['respCode']) == '00'){
            return $result;
        }
        return false;
    }

    /**
     * 银联支付退款
     * @param type $data
     * @return type
     */
    public function doUnionRefund($data = array()) {
        if (!isset($data['trade_no']) || !trim($data['trade_no'])) {
            return array('error' => 'trade_no 不能为空！');
        }
        if (!isset($data['total_money']) || !trim($data['total_money'])) {
            return array('error' => 'total_money 不能为空！');
        }
        if (!isset($data['refund_money']) || !trim($data['refund_money'])) {
            return array('error' => 'refund_money 不能为空！');
        }
        if (!isset($data['refund_no']) || !trim($data['refund_no'])) {
            return array('error' => 'refund_no 不能为空！');
        }
        if (!isset($data['notify_url']) || !trim($data['notify_url'])) {
            return array('error' => 'notify_url 不能为空！');
        }

        $uData = array(
            'version' => '5.0.0', //版本号
            'encoding' => 'utf-8', //编码方式
            'certId' => getSignCertId(), //证书ID	
            'signMethod' => '01', //签名方法
            'txnType' => '04', //交易类型       'txnType' => '31', //交易类型		
            'txnSubType' => '00', //交易子类
            'bizType' => '000201', //业务类型
            'accessType' => '0', //接入类型
            'channelType' => '07', //渠道类型
            'orderId' => $data['refund_no'], //商户订单号，重新产生，不同于原消费
            'merId' => MER_ID, //商户代码，请修改为自己的商户号
            'origQryId' => $data['trade_no'], //原消费的queryId，可以从查询接口或者通知接口中获取
            'txnTime' => date('YmdHis'), //订单发送时间，重新产生，不同于原消费
            'txnAmt' => strval($data['refund_money'] * 100), //交易金额，退货总金额需要小于等于原消费
            'backUrl' => $data['notify_url'], //后台通知地址	
        );

        if (isset($data['pay_time']) && trim($data['pay_time']) && date('Y-m-d', $data['pay_time']) == date('Y-m-d')) {
            $uData['txnType'] = '31';
        }

        // 签名
        sign($uData);
        $res = sendHttpRequest($uData, SDK_BACK_TRANS_URL);
        $res = coverStringToArray($res);
        if (verify($res)) {
            return $res;
        }
        return false;
    }

}
