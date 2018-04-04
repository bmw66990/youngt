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
    //
    const WX_ORDER_Requl_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    //
    const WECHATPAY_PARTNER_KEY = '7819d8740e32ed775c22149a87ae9f99';
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
        
        $openId = $this->wx_pay_js->GetOpenid($data['order_id']);
        $this->wx_pay_unified_order->SetBody($data['product_name']);
        $this->wx_pay_unified_order->SetOut_trade_no($data['out_trade_no']);
        $this->wx_pay_unified_order->SetTotal_fee((int)($data['total_fee']*100));
        $this->wx_pay_unified_order->SetTime_start(date("YmdHis"));
        $this->wx_pay_unified_order->SetTime_expire(date("YmdHis", time() + 600));
        $this->wx_pay_unified_order->SetNotify_url($option['notify_url']);
        $this->wx_pay_unified_order->SetTrade_type("JSAPI");
        $this->wx_pay_unified_order->SetOpenid($openId);
        //调用统一下单api
        $order = WxPayApi::unifiedOrder($this->wx_pay_unified_order);
        var_dump($order);
        die;
        $jsApiParameters = $this->wx_pay_js->GetJsApiParameters($order);
        $data =  array(
            'pay_data' => json_decode($jsApiParameters,true),
            'wx_data' => $this->wx_pay_js->data,
            'js_pay_config'=>$this->wx_pay_js->js_pay_config, 
        );
       
       return $data;
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
        if (!isset($data['origin']) || !trim($data['origin'])) {
            return array('error' => 'origin 不能为空！');
        }
        if (!isset($data['vid']) || !trim($data['vid'])) {
            return array('error' => 'vid 不能为空！');
        }
        if (!isset($data['refund_no']) || !trim($data['refund_no'])) {
            return array('error' => 'refund_no 不能为空！');
        }
        if (!isset($data['order_no']) || !trim($data['order_no'])) {
            return array('error' => 'order_no 不能为空！');
        }
        $pay_id = $data['order_no'] . '_' . $data['origin'] * 100;
        $input->SetOut_trade_no($pay_id);
        $input->SetTotal_fee($data['origin'] * 100);
        $input->SetRefund_fee($data['origin'] * 100);           
        $input->SetOut_refund_no($data['vid']); 
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

    /**
     * 微信app请求
     * @param type $data
     */
    public function doWxPayApp($data) {
        $input=new WxPayUnifiedOrder();
        $input->SetAppid(WxPayConfig::APPID); //公众账号ID
        $input->SetMch_id(WxPayConfig::MCHID); //商户号
        $input->SetNonce_str(WxPayApi::getNonceStr()); //随机字符串
        $input->SetBody($data['body']);//商品描述
        $input->SetOut_trade_no($data['out_trade_no']);//商户订单号
        $input->SetTotal_fee($data['total_fee']);//总金额
        $input->SetSpbill_create_ip($data['spbill_create_ip']);//终端IP
        $input->SetTrade_type($data['trade_type']);//交易类型
        $input->SetNotify_url($data['notify_url']); //通知地址
        
        $md5_key = '';
        if(isset($data['md5_key']) && trim($data['md5_key'])){
            $md5_key = $data['md5_key'];
        }
        $input->SetSign($md5_key); //签名
        $xml = $input->ToXml();
        $response = WxPayApi::postXmlCurl($xml, self::WX_ORDER_Requl_URL, true, 6);
        $result = WxPayResults::Init($response);

        return $this->kappSigin($result);
    }

    /**
     * app签名 
     * @param type $result
     * @return string
     */
    public function kappSigin($result)
    {
        $data['appid']=WxPayConfig::APPID;
        $data['partnerid']=WxPayConfig::MCHID;
        $data['prepayid']=$result['prepay_id'];
        $data['package']='Sign=WXPay';
        $data['noncestr']=WxPayApi::getNonceStr();
        $data['timestamp']=time();
        ksort($data);
        $signString = '';
        foreach ($data as $key => $val) {
            $signString.="$key=$val&";
        }
        $signString.="key=" .WxPayConfig::KEY;
        $sign = strtoupper(md5($signString));
        $data['sign']=$sign;
        $data['package_value']='Sign=WXPay';
        return $data;
    }

}
