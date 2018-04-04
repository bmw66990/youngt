<?php

date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ERROR);
require_once __DIR__ . "/lib/WxPay.Api.php";
require_once __DIR__ . "/unit/WxPay.NativePay.php";
require_once __DIR__ . "/unit/WxPay.JsApiPay.php";
require_once __DIR__ . "/unit/WxPay.MchPay.php";

/*require_once __DIR__ . "/MD5SignUtil.php";
require_once __DIR__ . "/SDKRuntimeException.class.php";
require_once __DIR__ . "/CommonUtil.php";*/

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
   


    /**
     * 构造函数
     */
    public function __construct() {
        $this->wx_pay_unified_order = new \WxPayUnifiedOrder();
        $this->wx_pay_native = new \NativePay();
        $this->wx_pay_js = new \JsApiPay();
        $this->wx_mchpay = new \WxMchPay();
    }

    /**
     * 企业付款测试
     */
    public function rebate($data)
    {      
        if(!isset($data['openid']) || !$data['openid']){
            return array('code'=>-1,'error'=>'缺少openid参数');
        }
        if(!isset($data['jine']) || !$data['jine']){
            return array('code'=>-1,'error'=>'缺少jine参数');
        }
          
        /*// mch_appid
        $this->wx_mchpay->setParameter('mch_appid', $appid);
        // mchid
        $this->wx_mchpay->setParameter('mchid', $mchid);
        // nonce_str
        $this->wx_mchpay->setParameter('nonce_str', $this->great_rand());*/
        // openid
        $this->wx_mchpay->setParameter('openid', $data['openid']);
        // 商户订单号
        $this->wx_mchpay->setParameter('partner_trade_no', 'test-'.time());
        // 校验用户姓名选项
        $this->wx_mchpay->setParameter('check_name', 'NO_CHECK');
        // 企业付款金额  单位为分
        $this->wx_mchpay->setParameter('amount', $data['jine']);
        // 企业付款描述信息
        $this->wx_mchpay->setParameter('desc', '用户提现');
        // 调用接口的机器IP地址  自定义
        $this->wx_mchpay->setParameter('spbill_create_ip', get_client_ip()); # getClientIp()
        // 收款用户姓名
        // $mchPay->setParameter('re_user_name', 'Max wen');
        // 设备信息
        // $mchPay->setParameter('device_info', 'dev_server');
        $postXml = $this->wx_mchpay->createXml();     
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';        
        $responseXml = $this->wx_mchpay->postXmlSSLCurl($url, $postXml);
        $responseObj = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return objectToArray($responseObj);
    }    

    /**
     * jsAPI支付
     * @param $data
     * @param $option
     * @return array
     * @throws WxPayException
     */
    public function doPaynew($dataa , $option) {   
        $money = ($dataa['money']*100);        
        $this->wx_pay_unified_order->SetTotal_fee($money);  
        $this->wx_pay_unified_order->SetBody($dataa['product_name']);         
        $this->wx_pay_unified_order->SetOut_trade_no($dataa['out_trade_no']);   
        $this->wx_pay_unified_order->SetTime_start(date("YmdHis"));
        $this->wx_pay_unified_order->SetTime_expire(date("YmdHis", time() + 600));
        $this->wx_pay_unified_order->SetNotify_url('http://fanli.ree9.com/Wechat/notify');
        $this->wx_pay_unified_order->SetTrade_type("JSAPI");         
        $openId = $this->wx_pay_js->GetOpenid($dataa['order_id'],$money); 
        $this->wx_pay_unified_order->SetOpenid($openId);

        $order = WxPayApi::unifiedOrder($this->wx_pay_unified_order);
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
