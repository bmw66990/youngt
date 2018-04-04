<?php

require_once (__DIR__ . "/lib/RequestHandler.class.php");
require_once (__DIR__ . "/lib/client/ClientResponseHandler.class.php");
require_once (__DIR__ . "/lib/client/TenpayHttpClient.class.php");

class TenPayRefund {

   // const TEN_PAY_REFUND_URL = 'https://mch.tenpay.com/refundapi/gateway/refund.xml';
    const TEN_PAY_REFUND_URL = 'https://api.mch.tenpay.com/refundapi/gateway/refund.xml';

    private $reqHandler = null;
    private $httpClient = null;
    private $resHandler = null;
    private $refund_info = array(
        'wx' => array(
            'partner' => '1218668001',
            'partner_key' => '7819d8740e32ed775c22149a87ae9f99',
            'op_user_passwd' => 'youngt0731',
        ),
        'pc' => array(
            'partner' => '1210021001',
            'partner_key' => 'abcdefg1234567ABCDEFG0000000yyyy',
            'op_user_passwd' => 'youngt0731',
        ),
        'wap' => array(
            'partner' => '1215943701',
            'partner_key' => '653eebc44b94017588059bded99bf161',
            'op_user_passwd' => 'youngt0731',
        ),
    );

    public function __construct() {
        $this->reqHandler = new RequestHandler();
        $this->httpClient = new TenpayHttpClient();
        $this->resHandler = new ClientResponseHandler();

        // 初始化信息
        $this->reqHandler->init();
        $this->reqHandler->setGateUrl(self::TEN_PAY_REFUND_URL);
    }

    /**
     * 财付通退款
     */
    public function doTenPayRefund($data) {

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
        if (!isset($data['plat']) || !trim($data['plat']) || !isset($this->refund_info[trim($data['plat'])])) {
            return array('error' => 'plat 不合法！');
        }
        if(strtolower(trim($data['plat']))=='wap'){
          //  return array('error' => '财付通wap自动退款已经关闭，请手动处理！');
        }

        $partner_info = $this->refund_info[trim($data['plat'])];

        $this->reqHandler->setKey($partner_info['partner_key']);
        $this->reqHandler->setParameter("partner", $partner_info['partner']);
        $this->reqHandler->setParameter("transaction_id", $data['trade_no']);
        $this->reqHandler->setParameter("out_refund_no", $data['refund_no']);
        $this->reqHandler->setParameter("total_fee", $data['total_money'] * 100);
        $this->reqHandler->setParameter("refund_fee", $data['refund_money'] * 100);
        $this->reqHandler->setParameter("op_user_id", $partner_info['partner']);
        $this->reqHandler->setParameter("op_user_passwd", md5($partner_info['op_user_passwd']));
        $this->reqHandler->setParameter("service_version", "1.1");
        $cert_path = __DIR__.'/cert/';
        if(file_exists($cert_path."{$partner_info['partner']}.pem")){
            if(strtolower(trim($data['plat']))=='wap'){
               $this->httpClient->setCertInfo($cert_path."{$partner_info['partner']}.pem", $partner_info['partner']);
            }else{
               $this->httpClient->setCertInfo($cert_path."{$partner_info['partner']}.pem", $partner_info['partner'].'_pem'); 
            } 
        }
        if(file_exists($cert_path."{$partner_info['partner']}_ca.pem")){
            $this->httpClient->setCaInfo($cert_path."{$partner_info['partner']}_ca.pem");
        }
        $this->httpClient->setTimeOut(30);
        $this->httpClient->setReqContent($this->reqHandler->getRequestURL());
        $res_refund = false;
        try {
            $res_refund = @$this->httpClient->call();
        } catch (Exception $e) {
            $res_refund = false;
        }
        if ($res_refund) {
            //设置结果参数
            $this->resHandler->setContent($this->httpClient->getResContent());
            $this->resHandler->setKey($partner_info['partner_key']);

            //判断签名及结果
            //只有签名正确并且retcode为0才是请求成功
            if ($this->resHandler->isTenpaySign() && $this->resHandler->getParameter("retcode") == "0") {
                $r_data = array(
                    'trade_no' => $this->resHandler->getParameter("transaction_id"),
                    'refund_money' => sprintf("%.2f", $this->resHandler->getParameter("refund_fee") / 100),
                    'refund_mark' => '退款成功！退款流水号：' . $this->resHandler->getParameter("out_refund_no") . ',退款时间：' . date('Y-m-d H:i:s'),
                    'refund_ptime' => time(),
                    'refund_etime' => time(),
                );
                return $r_data;
            }
            return array('error' => '退款失败！原因:' . iconv('GBK', 'UTF-8', $this->resHandler->getParameter("retmsg")));
        }
        return array('error' => '退款失败！' . $this->httpClient->getErrInfo());
    }

}
