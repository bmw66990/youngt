<?php

class Alipay {

    private static $instance;
    private $path;
    private $config;
    private $submit;
    private $notify;

    private function __construct() {
        $this->path = dirname(__FILE__) . '/alipay';

        require_once($this->path . '/alipay.config.php');
        $this->config = $alipay_config;

        require_once($this->path . '/lib/alipay_submit.class.php');
        $this->submit = new AlipaySubmit($alipay_config);

        require_once($this->path . '/lib/alipay_notify.class.php');
        $this->notify = new AlipayNotify($alipay_config);
    }

    public static function getInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 提交到支付宝
    public function doPay($data, $option, $action = 'Buy') {

        //支付类型
        $payment_type = "1";
        //必填，不能修改
        //服务器异步通知页面路径
        $notify_url = $option['notify_url'];
        //需http://格式的完整路径，不能加?id=123这类自定义参数
        //页面跳转同步通知页面路径
        $return_url = $option['return_url'];
        //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
        //卖家支付宝帐户
        $seller_email = 'youngt_com@163.com';
        //必填
        //商户订单号
        $out_trade_no = $data['out_trade_no'];
        //商户网站订单系统中唯一订单号，必填
        //订单名称
        $subject = $data['subject'];
        //必填
        //付款金额
        $total_fee = $data['total_fee'];
        //必填
        //订单描述
        $body = $data['body'];


        //商品展示地址
        $show_url = $data['show_url'];
        //需以http://开头的完整路径，例如：http://www.xxx.com/myorder.html
        //防钓鱼时间戳
        $anti_phishing_key = $this->submit->query_timestamp();
        //若要使用请调用类文件submit中的query_timestamp函数
        //客户端的IP地址
        $exter_invoke_ip = "";
        //非局域网的外网IP地址，如：221.0.0.1


        /*         * ********************************************************* */

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "create_direct_pay_by_user",
            "partner" => trim($this->config['partner']),
            "payment_type" => $payment_type,
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "seller_email" => $seller_email,
            "out_trade_no" => $out_trade_no,
            "subject" => $subject,
            "total_fee" => $total_fee,
            "show_url" => $show_url,
            "anti_phishing_key" => $anti_phishing_key,
            "exter_invoke_ip" => $exter_invoke_ip,
            "_input_charset" => trim(strtolower($this->config['input_charset']))
        );
        if ($action == 'code') {
            $parameter['qr_pay_mode'] = "0";
            $parameter['return_url'] = $option['return_CodeUrl'];
        }
        //建立请求
        if ($action == 'code') {
            $html_text = $this->submit->buildRequestForms($parameter);
            return $html_text;
        } else {
            $html_text = $this->submit->buildRequestForm($parameter, "get", "正在前往支付页面...");
            header('Content-Type: text/html; charset=utf-8');
            die($html_text);
        }
    }

    // 获取异步验证结果数据
    public function verifyNotify() {
        return $this->notify->verifyNotify();
    }

    // 获取同步验证结果数据
    public function verifyReturn() {
        return $this->notify->verifyReturn();
    }

    // 写入文件
    public function logResult($word = '') {
        require_once($this->path . '/lib/alipay_core.function.php');
        logResult($word);
    }

    public function getOrderQuery($orderId) {
        //支付宝查询
        $trade_no = '';
        $out_trade_no = $orderId;
        $parameter = array(
            "service" => "single_trade_query",
            "partner" => trim($this->config['partner']),
            "trade_no" => $trade_no,
            "out_trade_no" => $out_trade_no,
            "_input_charset" => trim(strtolower($this->config['input_charset']))
        );
        $alipaySubmit = new AlipaySubmit($this->config);
        $html_text = $alipaySubmit->buildRequestHttp($parameter);
        $data = json_decode(json_encode(simplexml_load_string($html_text)), true);
        return isset($data['response']['trade']) ? $data['response']['trade'] : false;
    }

    /**
     * 支付宝退款
     * @param type $data
     * @return type
     */
    public function doAlipayRefund($data) {
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
        if (!isset($data['seller_email']) || !trim($data['seller_email'])) {
            return array('error' => 'seller_email 不能为空！');
        }
        list($_, $order_id) = explode('U', $data['refund_no']);
        $batch_no = date('Ymd') . $order_id;
        $detail_data = "{$data['trade_no']}^{$data['refund_money']}^协商退款";
        $parameter = array(
            "service" => "refund_fastpay_by_platform_pwd",
            "partner" => trim($this->config['partner']),
            "notify_url" => $data['notify_url'],
            "seller_email" => $data['seller_email'],
            "refund_date" => date('Y-m-d H:i:s'),
            "batch_no" => $batch_no,
            "batch_num" => 1,
            "detail_data" => $detail_data,
            "_input_charset" => trim(strtolower($this->config['input_charset']))
        );

        $alipaySubmit = new AlipaySubmit($this->config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        header('Content-Type: text/html; charset=utf-8');
        die($html_text);
    }
    
    /**
     * 阿里批量退款
     * @param type $data
     * @return type
     */
    public function doAlipayBetchRefund($data) {
       
       
        if (!isset($data['notify_url']) || !trim($data['notify_url'])) {
            return array('error' => 'notify_url 不能为空！');
        }
        if (!isset($data['seller_email']) || !trim($data['seller_email'])) {
            return array('error' => 'seller_email 不能为空！');
        }
        if (!isset($data['batch_max_order_id']) || !trim($data['batch_max_order_id'])) {
            return array('error' => 'batch_max_order_id 不能为空！');
        }
        if (!isset($data['batch_num']) || !$data['batch_num']) {
            return array('error' => 'batch_num 不能为空！');
        }
        if (!isset($data['batch_data']) || !$data['batch_data']) {
            return array('error' => 'batch_data 不能为空！');
        }
        
        $detail_data = array();
        foreach($data['batch_data'] as $v){
            if(!isset($v['trade_no']) || !isset($v['trade_no'])){
                continue;
            }
            $detail_data[]= "{$v['trade_no']}^{$v['refund_money']}^协商退款";
        }
        $data['batch_num'] = count($detail_data);
        $detail_data = implode('#', $detail_data);
        $batch_no = date('Ymd') . $data['batch_max_order_id'];
        $parameter = array(
            "service" => "refund_fastpay_by_platform_pwd",
            "partner" => trim($this->config['partner']),
            "notify_url" => $data['notify_url'],
            "seller_email" => $data['seller_email'],
            "refund_date" => date('Y-m-d H:i:s'),
            "batch_no" => $batch_no,
            "batch_num" => $data['batch_num'],
            "detail_data" => $detail_data,
            "_input_charset" => trim(strtolower($this->config['input_charset']))
        );

        $alipaySubmit = new AlipaySubmit($this->config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        header('Content-Type: text/html; charset=utf-8');
        die($html_text);
    }

}

?>