<?php

/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-13
 * Time: 上午11:42
 */

namespace Common\Org;

/**
 * Class Pay
 *
 * @package Common\Org
 */
class PayNew {
    // 回调地址
    const CALL_BACK_HANDLE_URL = 'http://cc.youngt.com/api/PayCallBack/payCallbackHandle/payAction/';
    //const CALL_BACK_HANDLE_URL = 'http://csapp.youngt.com/api/PayCallBack/payCallbackHandle/payAction/';
    /***
     *
     * 升级后微信支付原青团客户端微信字符
     */
    /**
     * @param $payId
     * @param $title
     * @param $product
     * @param $payFee
     * @param $plat    微信支付
     */
    public function setNewWXPayData($payId, $title, $product, $payFee, $plat) {
        // 过滤$product
        $product = str_replace("'", "", $product);
        $product = str_replace(" ", "", $product);
        $product = str_replace("+", "", $product);
        $product = cutStr($product, 30, 0, 0);

        // 价格单位转化
        $payFee = $payFee * 100;

        // 生成package
        $data = array(
            'trade_type' => 'APP',
            'body' => $product,
            'notify_url' => self::CALL_BACK_HANDLE_URL . 'newwxpay',
            'out_trade_no' => $payId,
            'spbill_create_ip' => get_client_ip(),
            'total_fee' => strval($payFee),
        );
        include_once(__DIR__ . '/lib/NewpcWXpay/pcWXpay.class.php');
        $wxNotify = new \PcWXpay();
        return $wxNotify->doWxPayApp($data);
    }
    public function getNewPCWXpayVerify() {
        include __DIR__ . '/lib/NewpcWXpay/pcWXpayNotify.class.php';
        $pcWXpayNotify = new \PcWXpayNotify();
        $pcWXpayNotify->Handle(false);
        $code = $pcWXpayNotify->GetReturn_code();
        $msg = $pcWXpayNotify->GetReturn_msg();
        if (strtolower(trim($code)) == 'success' && strtolower(trim($msg)) == 'ok') {
            $pars = $pcWXpayNotify->getOrderData();
            $data = array(
                'out_trade_no' => isset($pars['out_trade_no']) ? $pars['out_trade_no'] : '',
                'transaction_id' => isset($pars['transaction_id']) ? $pars['transaction_id'] : '',
                'total_fee' => isset($pars['total_fee']) ? $pars['total_fee'] * 0.01 :'',
            );
            return $data;
        }
        return false;
    }

    /**
     * 第三方退款
     * @param type $type
     * @param type $pay_id
     * @param type $trade_no
     * @param type $refund_money
     * @param type $refund_no
     * $type,$pay_id,$trade_no,$refund_money,$refund_no
     */
    public function thirdPartyRefund($data = array()) {

        if (!isset($data['type']) || !trim($data['type'])) {
            return array('error' => '第三方类型不能为空！');
        }
        if (!isset($data['order_no']) || !trim($data['order_no'])) {
            return array('error' => 'order_no 不能为空！');
        }
        if (!isset($data['pay_time']) || !trim($data['pay_time'])) {
            return array('error' => 'pay_time 不能为空！');
        }
        if (!isset($data['refund_money']) || !trim($data['refund_money'])) {
            return array('error' => 'refund_money 不能为空！');
        }
        if (!isset($data['vid']) || !trim($data['vid'])) {
            return array('error' => 'vid 不能为空！');
        }
        if (!isset($data['refund_no']) || !trim($data['refund_no'])) {
            return array('error' => 'refund_no 不能为空！');
        }
        $type = strtolower(trim($data['type']));

        switch ($type) {
            case 'wapwechatpay':
            case 'pcwxpaycode' :
            case 'wechatpay':
            case 'wxpay':
                if ($type == 'wapwechatpay' || $type == 'pcwxpaycode'){
                    include __DIR__ . '/lib/wapWXpay/pcWXpay.class.php';
                }
                if ($type == 'wechatpay' || $type == 'wxpay') {
                    include_once(__DIR__ . '/lib/NewpcWXpay/pcWXpay.class.php');
                }
                $pc_wx_pay = new \PcWXpay();
                $res = $pc_wx_pay->doWxPayRefund($data);

                if (isset($res['result_code']) && $res['result_code'] == 'SUCCESS') {
                    $r_data = array(
                        'pay_id' => ternary($data['order_no'], ''),
                        'trade_no' => ternary($data['vid'], ''),
                        'refund_money' => sprintf("%.2f", ternary($data['refund_money'] / 100, '')),
                        'refund_mark' => "申请退款成功，退款流水号：{$res['refund_id']}",
                        'refund_ptime' => time(),
                        'refund_etime' => time(),
                    );
                    return $r_data;
                }
                return array('error' => ternary($res['result_code'], '') . '-' . ternary($res['err_code_des'], ''));
                break;
            default:
                return array('error' => $type . ' 该形式不支持接口退款！请到相应的后台系统手动退款！');
                break;
        }
        die;
    }


}
