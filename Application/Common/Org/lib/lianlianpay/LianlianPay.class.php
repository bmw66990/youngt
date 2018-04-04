<?php

require_once (__DIR__ . "/lib/llpay_submit.class.php");
require_once (__DIR__ . "/lib/llpay_notify.class.php");
require_once (__DIR__ . "/lib/llpay_apipost_submit.class.php");

class LianlianPay {

    const LIANLIAN_PAY_QUERY_URL = 'https://yintong.com.cn/traderapi/orderquery.htm';
    const LIANLIAN_PAY_REFUND_URL = 'https://yintong.com.cn/traderapi/refund.htm';

    /**
     * 连连支付 支付参数获取
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     * @param type $notify_url
     */
    public function getLianlianPayData($payId, $title, $product, $payFee, $user_info, $notify_url) {
        require_once (__DIR__ . "/lib/llpay.config.php");

        // 风险控股参数
        $now_time = time();
        $risk_item = array(
            'frms_ware_category' => '1005', // 
            'user_info_bind_phone' => ternary($user_info['mobile'], $llpay_config['register_iphone']), // 绑定手机号
            'user_info_dt_register' => isset($user_info['create_time']) && trim($user_info['create_time']) ? date('YmdHis', $user_info['create_time']) : $llpay_config['register_time'], // 注册时间
            'user_info_mercht_userno' => ternary($user_info['id'], $now_time), // 
        );
        $data = array(
            'oid_partner' => isset($llpay_config['oid_partner']) ? $llpay_config['oid_partner'] : '',
            'no_order' => $payId,
            'busi_partner' => '101001',
            'name_goods' => $product,
            'money_order' => $payFee,
            'sign_type' => $llpay_config['sign_type'],
            'notify_url' => $notify_url,
            'risk_item' => json_encode($risk_item),
            'valid_order' => $llpay_config['valid_order'],
            'dt_order' => date('YmdHis', $now_time),
        );
        $llpay_submit = new \LLpaySubmit($llpay_config);
        $llpay_data = $llpay_submit->buildRequestPara($data);
        $llpay_data = array_merge($llpay_data, array(
            'user_id' => ternary($user_info['id'], $now_time),
            'flag_modify' => '0',
        ));
        return json_encode($llpay_data);
    }

    /**
     * 连连支付回调校验
     */
    public function getAppLianlianPayVerify() {
        require_once (__DIR__ . "/lib/llpay.config.php");
        $llpayNotify = new LLpayNotify($llpay_config);
        $llpayNotify->verifyNotify();
        if ($llpayNotify->result) {
            $result_pay = $llpayNotify->notifyResp['result_pay']; //支付结果，SUCCESS：为支付成功
            if ($result_pay == 'SUCCESS') {
                $data = array(
                    'no_order' => ternary($llpayNotify->notifyResp['no_order'], ''),
                    'oid_paybill' => ternary($llpayNotify->notifyResp['oid_paybill'], ''),
                    'money_order' => ternary($llpayNotify->notifyResp['money_order'], '')
                );
                return $data;
            }
        }
        return false;
    }
    
    public function getAppLianlianPayRefundVerify(){
          require_once (__DIR__ . "/lib/llpay.config.php");
          $llpayNotify = new LLpayNotify($llpay_config);
          $data = @json_decode(file_get_contents("php://input"),true);
          $sign = ternary($data['sign'], '');
          if($llpayNotify->getSignVeryfy($data, $sign)){
               list($trade_no, $order_id) = @explode('U', $data['no_refund'], 2);
                    $r_data = array(
                        'order_id' => $order_id,
                        'trade_no' => $trade_no,
                        'refund_money' => sprintf("%.2f", ternary($res['money_refund'], '')),
                        'refund_mark' => "申请退款成功，退款流水号：" . ternary($data['oid_refundno'], ''),
                    );
                    return $r_data;
          }
    }

    /**
     * 订单查询
     * @param type $orderId
     * @param type $tradeNo
     * @return type
     */
    public function getAppLianLianQuery($orderId, $tradeNo) {
        require_once (__DIR__ . "/lib/llpay.config.php");
        $data = array(
            "oid_partner" => trim($llpay_config['oid_partner']),
            "sign_type" => trim($llpay_config['sign_type']),
            "no_order" => $orderId,
        );
        //建立请求
        $llpaySubmit = new LLpaySubmitQuery($llpay_config);
        $res = $llpaySubmit->buildRequestJSON($data, self::LIANLIAN_PAY_QUERY_URL);
        if (is_string($res)) {
            $res = json_decode($res, true);
        }
        if(isset($res['ret_code']) && trim($res['ret_code']) == '0000'){
             return $res;
        }
       return false;
    }

    /**
     * 连连支付退款操作
     * @param type $data
     * @return type
     */
    public function doLianlianPayRefund($data = array()) {

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

        require_once (__DIR__ . "/lib/llpay.config.php");
        $data = array(
            "oid_partner" => trim($llpay_config['oid_partner']),
            "sign_type" => trim($llpay_config['sign_type']),
            "no_refund" => $data['refund_no'],
            "dt_refund" => date('YmdHis'),
            "money_refund" => $data['refund_money'],
            "no_order" => $data['pay_id'],
            "oid_paybill" => $data['trade_no'],
            "notify_url" => $data['notify_url']
        );
        //建立请求
        $llpaySubmit = new LLpaySubmitQuery($llpay_config);
        $res = $llpaySubmit->buildRequestJSON($data, self::LIANLIAN_PAY_REFUND_URL);
        if (is_string($res)) {
            $res = json_decode($res, true);
        }
        return $res;
    }

}
