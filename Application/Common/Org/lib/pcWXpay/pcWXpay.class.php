<?php

date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ERROR);
require_once __DIR__ . "/lib/WxPay.Api.php";
require_once __DIR__ . "/unit/WxPay.NativePay.php";
require_once __DIR__ . "/unit/WxPay.JsApiPay.php";

class PcWXpay {

    private $wx_pay_unified_order = null;
    private $wx_pay_native = null;
    private $wx_pay_js = null;

    const WX_QR_CODE_URL = 'http://paysdk.weixin.qq.com/example/qrcode.php';
    // 微信退款链接
    const WX_ORDER_REFUND_URL = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

    /**
     * 构造函数
     */
    public function __construct() {
        $this->wx_pay_unified_order = new \WxPayUnifiedOrder();
        $this->wx_pay_native = new \NativePay();
        $this->wx_pay_js = new \JsApiPay();
    }

    /**
     * 扫码支付
     * @param $payId
     * @param $title
     * @param $product
     * @param $payFee
     * @param $plat
     * @param $notify_url
     * @return string
     */
    public function createWXpayCode($payId, $title, $product, $payFee, $plat, $notify_url) {
        // 过滤$product
        $product = str_replace("'", "", $product);
        $product = str_replace(" ", "", $product);
        $product = str_replace("+", "", $product);
        $now_time = time();

        $this->wx_pay_unified_order->SetBody($product);
        $this->wx_pay_unified_order->SetAttach($product);
        $this->wx_pay_unified_order->SetOut_trade_no($payId);
        $this->wx_pay_unified_order->SetTotal_fee($payFee);
        $this->wx_pay_unified_order->SetTime_start(date("YmdHis", $now_time));
        $this->wx_pay_unified_order->SetTime_expire(date("YmdHis", $now_time + 3600));
        $this->wx_pay_unified_order->SetGoods_tag(md5($title));
        $this->wx_pay_unified_order->SetNotify_url($notify_url);
        $this->wx_pay_unified_order->SetTrade_type("NATIVE");
        $this->wx_pay_unified_order->SetProduct_id($payId);
        $result = $this->wx_pay_native->GetPayUrl($this->wx_pay_unified_order);
        $result['GetPrePayUrl'] = $this->wx_pay_native->GetPrePayUrl($payId);     
        $url = self::WX_QR_CODE_URL;
        if (isset($result["code_url"]) && trim($result["code_url"])) {
            $url = "{$url}?data={$result["code_url"]}";
        }
        return $url;
    }

    /**
     * jsAPI支付
     * @param $data
     * @param $option
     * @return array
     * @throws WxPayException
     */
    public function doPay($data, $option) {
        $openId = $this->wx_pay_js->GetOpenid();
        $this->wx_pay_unified_order->SetBody($data['product_name']);
        $this->wx_pay_unified_order->SetOut_trade_no($data['out_trade_no']);
        $this->wx_pay_unified_order->SetTotal_fee($data['total_fee']);
        $this->wx_pay_unified_order->SetTime_start(date("YmdHis"));
        $this->wx_pay_unified_order->SetTime_expire(date("YmdHis", time() + 600));
        $this->wx_pay_unified_order->SetNotify_url($option['notify_url']);
        $this->wx_pay_unified_order->SetTrade_type("JSAPI");
        $this->wx_pay_unified_order->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($this->wx_pay_unified_order);
        $jsApiParameters = $this->wx_pay_js->GetJsApiParameters($order);
        return array(
            'pay_data' => $jsApiParameters,
            'wx_data' => $this->wx_pay_js->data
        );
    }

    /**
     * 订单查询
     * @param $orderId
     * @return 成功时返回
     * @throws WxPayException
     */
    public function orderQuery($orderId) {
        $input = new \WxPayOrderQuery();
        $input->SetOut_trade_no($orderId);
        $res = WxPayApi::orderQuery($input);
        if(isset($res['err_code'])){
            return false;
        }
        return $res;
    }

    /**
     * 微信退款
     * @param type $data
     */
    public function doWxPayRefund($data) {

        $input = new WxPayRefund();
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
        $input->SetTransaction_id($data['trade_no']);
        $input->SetTotal_fee($data['total_money'] * 100);
        $input->SetRefund_fee($data['refund_money'] * 100);
        $input->SetOut_refund_no($data['refund_no']);
        $input->SetOp_user_id(WxPayConfig::MCHID);
        $input->SetAppid(WxPayConfig::APPID); //公众账号ID
        $input->SetMch_id(WxPayConfig::MCHID); //商户号
        $input->SetNonce_str(WxPayApi::getNonceStr()); //随机字符串
        if (isset($data['app_id']) && trim($data['app_id'])) {
            $input->SetAppid($data['app_id']); //公众账号ID
        }
        if (isset($data['mch_id']) && trim($data['mch_id'])) {
            $input->SetMch_id($data['mch_id']); //商户号
            $input->SetOp_user_id($data['mch_id']);
        }
        $md5_key = '';
        if(isset($data['md5_key']) && trim($data['md5_key'])){
            $md5_key = $data['md5_key'];
        }

        $input->SetSign($md5_key); //签名
        $xml = $input->ToXml();
        $response = WxPayApi::postXmlCurl($xml, self::WX_ORDER_REFUND_URL, true, 6);
        $result = WxPayResults::Init($response);

        return $result;
    }

}
