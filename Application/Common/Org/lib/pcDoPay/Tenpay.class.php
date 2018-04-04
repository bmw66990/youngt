<?php

class tenpay {

    private static $instance;
    private $path;
    private $key;
    private $partner;

    private function __construct() {
        // 设置库路径
        $this->path = dirname(__FILE__) . '/tenpay';
        $this->partner = "1210021001";     //财付通商户号 
        $this->key = "abcdefg1234567ABCDEFG0000000yyyy"; //财付通密钥
    }

    public static function getInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 提交到财付通
    public function doPay($data, $option) {

        require_once ($this->path . '/classes/RequestHandler.class.php');
        $reqHandler = new RequestHandler();
        $reqHandler->init();
        $reqHandler->setKey($this->key);
        $reqHandler->setGateUrl("https://gw.tenpay.com/gateway/pay.htm");

        //用户ip
        $reqHandler->setParameter("spbill_create_ip", get_client_ip()); //客户端IP
        $reqHandler->setParameter("return_url", $option['return_url']); //支付成功后返回
        $reqHandler->setParameter("partner", $this->partner);
        $reqHandler->setParameter("out_trade_no", $data['out_trade_no']);
        $reqHandler->setParameter("notify_url", $option['notify_url']);
        $reqHandler->setParameter("body", $data['desc']);
        $reqHandler->setParameter("bank_type", $data['bank_type']);     //银行类型，默认为财付通
        $reqHandler->setParameter("fee_type", "1");               //币种
        $reqHandler->setParameter("total_fee", $data['total_fee']);
        //系统可选参数
        $reqHandler->setParameter("sign_type", "MD5");       //签名方式，默认为MD5，可选RSA
        $reqHandler->setParameter("service_version", "1.0");    //接口版本号
        $reqHandler->setParameter("input_charset", "utf-8");      //字符集
        $reqHandler->setParameter("sign_key_index", "1");       //密钥序号
        $reqUrl = $reqHandler->getRequestURL();
        //建立请求
        $html_text = $this->buildRequestForm($reqHandler, "post", "正在前往支付页面...");
        header('Content-Type: text/html; charset=utf-8');
        echo $html_text;
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $params 请求参数数组
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
    function buildRequestForm($reqHandler, $method, $button_name) {
        //待请求参数数组
        $params = $reqHandler->getAllParameters();
        $sHtml = "<div class='go-payment' style=\"display:none\"><form id='tenpaysubmit' name='tenpaysubmit' action='" . $reqHandler->getGateUrl() . "' method='" . $method . "'>";
        foreach ($params as $key => $val) {
            $sHtml.= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' value='" . $button_name . "'></form></div>";
        $sHtml = $sHtml . "<script>document.forms['tenpaysubmit'].submit();</script>";
        return $sHtml;
    }

    public function verifyNotify() {
        require ($this->path . "/classes/ResponseHandler.class.php");
        require ($this->path . "/classes/RequestHandler.class.php");
        require ($this->path . "/classes/client/ClientResponseHandler.class.php");
        require ($this->path . "/classes/client/TenpayHttpClient.class.php");


        /* 创建支付应答对象 */
        $resHandler = new ResponseHandler();
        $resHandler->setKey($this->key);

        //判断签名
        if ($resHandler->isTenpaySign()) {
            //通知id
            $notify_id = $resHandler->getParameter("notify_id");

            //通过通知ID查询，确保通知来至财付通
            //创建查询请求
            $queryReq = new RequestHandler();
            $queryReq->init();
            $queryReq->setKey($this->key);
            $queryReq->setGateUrl("https://gw.tenpay.com/gateway/verifynotifyid.xml");
            $queryReq->setParameter("partner", $this->partner);
            $queryReq->setParameter("notify_id", $notify_id);
            //通信对象
            $httpClient = new TenpayHttpClient();
            $httpClient->setTimeOut(5);
            //设置请求内容
            $httpClient->setReqContent($queryReq->getRequestURL());

            //后台调用
            if ($httpClient->call()) {
                //设置结果参数
                $queryRes = new ClientResponseHandler();
                $queryRes->setContent($httpClient->getResContent());
                $queryRes->setKey($this->key);         
                
                //判断签名及结果
                //只有签名正确,retcode为0，trade_state为0才是支付成功
                if ($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" && $queryRes->getParameter("trade_state") == "0" && $queryRes->getParameter("trade_mode") == "1") {
                    //取结果参数做业务处理
                    $res['out_trade_no'] = $queryRes->getParameter("out_trade_no");
                    //财付通订单号
                    $res['transaction_id'] = $queryRes->getParameter("transaction_id");
                    //金额,以分为单位
                    $res['total_fee'] = $queryRes->getParameter("total_fee");
                    //如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
                    //$discount = $queryRes->getParameter("discount");
                    //var_dump($res);
                    return $res;

                } else {
                    return false;
                }         
            } else {
                //通信失败
                //echo "fail：通信失败";
                return false;
            }
        } else {
            //回调签名错误
            //echo "fail：签名失败";
            return false;
        }
    }

    // 获取同步验证结果数据
    public function verifyReturn() {

        require ($this->path . "/classes/ResponseHandler.class.php");
        require ($this->path . "/classes/RequestHandler.class.php");
        require ($this->path . "/classes/client/ClientResponseHandler.class.php");
        require ($this->path . "/classes/client/TenpayHttpClient.class.php");

        /* 创建支付应答对象 */
        $resHandler = new ResponseHandler();
        $resHandler->setKey($this->key);

        //判断签名
        if ($resHandler->isTenpaySign()) {

            //通知id
            $notify_id = $resHandler->getParameter("notify_id");

            //通过通知ID查询，确保通知来至财付通
            //创建查询请求
            $queryReq = new RequestHandler();
            $queryReq->init();
            $queryReq->setKey($this->key);
            $queryReq->setGateUrl("https://gw.tenpay.com/gateway/verifynotifyid.xml");
            $queryReq->setParameter("partner", $this->partner);
            $queryReq->setParameter("notify_id", $notify_id);

            //通信对象
            $httpClient = new TenpayHttpClient();
            $httpClient->setTimeOut(5);
            //设置请求内容
            $httpClient->setReqContent($queryReq->getRequestURL());

            //后台调用
            if ($httpClient->call()) {
                //设置结果参数
                $queryRes = new ClientResponseHandler();
                $queryRes->setContent($httpClient->getResContent());
                $queryRes->setKey($this->key);

                //判断签名及结果
                //只有签名正确,retcode为0，trade_state为0才是支付成功
                if ($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" && $queryRes->getParameter("trade_state") == "0" && $queryRes->getParameter("trade_mode") == "1") {
                    //取结果参数做业务处理
                    $res['out_trade_no'] = $queryRes->getParameter("out_trade_no");
                    //财付通订单号
                    $res['transaction_id'] = $queryRes->getParameter("transaction_id");
                    //金额,以分为单位
                    $res['total_fee'] = $queryRes->getParameter("total_fee");
                    // //如果有使用折扣券，discount有值，total_fee+discount=原请求的total_fee
                    // $discount = $queryRes->getParameter("discount");
                    return $res;
                    //------------------------------
                    //处理业务开始
                    //------------------------------
                    //处理数据库逻辑
                    //注意交易单不要重复处理
                    //!!!注意判断返回金额!!!
                    //------------------------------
                    //处理业务完毕
                    //------------------------------
                } else {
                    //错误时，返回结果可能没有签名，写日志trade_state、retcode、retmsg看失败详情。
                    //echo "验证签名失败 或 业务错误信息:trade_state=" . $queryRes->getParameter("trade_state") . ",retcode=" . $queryRes->getParameter("retcode"). ",retmsg=" . $queryRes->getParameter("retmsg") . "<br/>" ;
                    //  echo "<br/>" . "支付失败" . "<br/>";
                    return false;
                }

            } else {
                //通信失败
                //echo "fail";
                //后台调用通信失败,写日志，方便定位问题，这些信息注意保密，最好不要打印给用户
                // echo "<br>订单通知查询失败:" . $httpClient->getResponseCode() . "," . $httpClient->getErrInfo() . "<br>";
            
                return false;
            }
        }
    }

    // 写入日志
    public function logResult($word) {
        //file_put_contents('pay.txt', $word . "\n", FILE_APPEND);
    }

    /**
     * 订单查询
     * @param $orderId
     * @param $tradeNo
     * @return array
     */
    public function getOrderQuery($orderId, $tradeNo) {
        require_once ($this->path . "/classes/RequestHandler.class.php");
        require_once ($this->path . "/classes/client/ClientResponseHandler.class.php");
        require_once ($this->path . "/classes/client/TenpayHttpClient.class.php");
        /* 创建支付请求对象 */
        $reqHandler = new \RequestHandler();

        //通信对象
        $httpClient = new TenpayHttpClient();

        //应答对象
        $resHandler = new ClientResponseHandler();

        //-----------------------------
        //设置请求参数
        //-----------------------------
        $reqHandler->init();
        $reqHandler->setKey($this->key);

        $reqHandler->setGateUrl("https://gw.tenpay.com/gateway/normalorderquery.xml");
        $reqHandler->setParameter("partner", $this->partner);
        //out_trade_no和transaction_id至少一个必填，同时存在时transaction_id优先
        $reqHandler->setParameter("out_trade_no", $orderId);
        // $reqHandler->setParameter("transaction_id", $tradeNo);
        //-----------------------------
        //设置通信参数
        //-----------------------------
        $httpClient->setTimeOut(5);
        //设置请求内容
        $httpClient->setReqContent($reqHandler->getRequestURL());
        if ($httpClient->call()) {
            //设置结果参数
            $rs = $httpClient->getResContent();

            $resHandler->setContent($rs);
            $resHandler->setKey($this->key);

            //判断签名及结果
            //只有签名正确并且retcode为0才是请求成功
            if ($resHandler->isTenpaySign() && $resHandler->getParameter("retcode") == "0") {
                //商户订单号
                $out_trade_no = $resHandler->getParameter("out_trade_no");
                //财付通订单号
                $transaction_id = $resHandler->getParameter("transaction_id");
                //金额,以分为单位
                $total_fee = $resHandler->getParameter("total_fee");
                //支付结果
                $trade_state = $resHandler->getParameter("trade_state");
                $res['out_trade_no'] = $out_trade_no;
                $res['transaction_id'] = $transaction_id;
                $res['total_fee'] = $total_fee;
                $res['trade_state'] = $trade_state;
                return $res;
            }
        }
        return false;
    }

}

?>