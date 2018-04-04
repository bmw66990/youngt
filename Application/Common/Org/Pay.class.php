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
class Pay {

    // 回调地址
    const CALL_BACK_HANDLE_URL = 'http://cc.youngt.com/api/PayCallBack/payCallbackHandle/payAction/';
     //const CALL_BACK_HANDLE_URL = 'http://csapp.youngt.com/api/PayCallBack/payCallbackHandle/payAction/';
    // 支付宝相关信息
    const ALIPAY_PARTNER = '2088501919693874';
    const ALIPAY_SELLER = 'youngt_com@163.com';
    const PATH_ALI_RSA_PRI_PEM = '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDKGIPw0z35pIBsTwVKbfdCwK+TyGDqYq6eiQEUsj7DO4C22wHx
zHnjVTTMqqKwXlifdHH69HCX5vfs/1P2pE9z62KqwIZUwlP06LKPH1RivsTqRfh1
u+7/VHzXK11b0nyEzs2C77C7YEe9ore8bqrvHA+gBSzV2pknPjYpN8st4wIDAQAB
AoGAIl7tAaZLoguSM9l0ouX06Ytv0QiBQxupaxkN8AEon3edOnwg5ojKKhmxsyVK
KgjBtczOHco44/zKKap7EzV6tYTwzM5YItnzca52J45DpqpWkEmw8CFAGcKmxdfz
PQY/Vde2RlsEI73FiGIFIueaAN0+YYYGVwwKvge+DLv/y1ECQQDmaC4HU/y2M+VE
4LK2gVbW2PiNju3uofyIeTVprmxmm0cNJWP2RDEj9Tpwd+G7TggnHJHC3dlvVaIa
JDTYyWLfAkEA4ItIVJNg9PeCHS+RzNhDB3OIPvnRncxGlLH+HeXCmZPiIvIq+tYs
wiZdWwFoByzANE/rkXWAYjggWMGxoBX5fQJBAMmcK3qaES1Vp65nd7me31/MJ2Gm
yaff8ltwxD4fNBdsk/V63EdnUCCIuoQjQlBlbVjb9OewvExhgCCjweJYBb0CQEHa
FzhWkJTHEa2licjdk6rXwxlVApiYlAp/uNrjyxJnQGanRtuRfEbkIXTTEMMp6KRu
29Mo9qHXfAULqSAd0bECQQCjCCQC8LTRGxq4JwuzjGMei81eNlGwnLxXMPVqegzs
fdH/X3YpjbPZuFNRUobdeJk8NWBRGBpgWw6ycOXf3gtY
-----END RSA PRIVATE KEY-----';
    // 财付通相关信息
    const TENPAY_MID = '1215943701';
    const TENPAY_SEC = '653eebc44b94017588059bded99bf161';
    const TENPAY_URL = 'https://cl.tenpay.com/cgi-bin/wappayv2.0/wappay_init.cgi';
    // 全民付相关信息
    const UMSPAY_MERCHANTID = '898310059994018';
    const UMSPAY_TERMID = '01061410';
    const PATH_UMS_PUB_PEM = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDKTlmycsY/3ugiNX6UtAyHbROL
Vc5HpG94DrjOSJ/yOnmEEXBA9FMXvoe0T27Li8LmfWEECUdKTd9m6nLha+XKzOSy
Db/cUu9CPKMTU3rAeuSezyGpKxucjRkn8nFenP7KpcweT/f6HtjAYTtAWWv6k2TU
B1JUKDvH49PXK8OfNQIDAQAB
-----END PUBLIC KEY-----';
    const PATH_UMS_RSA_PRI_PEM = '-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQDKTlmycsY/3ugiNX6UtAyHbROLVc5HpG94DrjOSJ/yOnmEEXBA
9FMXvoe0T27Li8LmfWEECUdKTd9m6nLha+XKzOSyDb/cUu9CPKMTU3rAeuSezyGp
KxucjRkn8nFenP7KpcweT/f6HtjAYTtAWWv6k2TUB1JUKDvH49PXK8OfNQIDAQAB
AoGBAK4K27Xecdmu7evIM77AaurzFtZPGmid+VvC5pBpAFlGnxXUXssypm2N93dS
wtGPyju6b4UmalNtTR1f4zc1OPlIYyd2NW69zl9zlhPRIbM8h4cahjwQVpwdxGYf
QG7v3A+pPhE/o9xDAFJDKNrecwpTSCSO0TVMbbqUIw+wBJ1hAkEA7w8LwW/0R8Lv
705EIFOWWpqoKNyRJ71o+H5rkidBMStY74pZhHXVViSqTvB5hfZpt+B+8lSNjzKB
Ya/INNI0qQJBANiki1yq6aG6TGGjgjf2rv6r/s3W0dT/OHESiKuKtlhbLA3fUY61
yJ3D98LdLCo05Dc6pjU8Fu49QWV1vSzyYa0CQFlPYbDuxnBY35Kjxsfc8nr+9Hvv
izsxhBnyAPYlPDHGY/95zF+0NfNs10OOi5gEqxJLCKI3/HrW/4cjSMSTE6ECQA1z
Yee2AN12uKlTQpGA8mqDeUWEcfC8i9+RXATyjtG7j2epFuI6bSNGeIqTuts//29i
Z9FkUrQl/3pyLfWtct0CQFfTK6gl4uab/xzGTP21QwfgTyb+GdsxBOmnhWQ/41tc
k046OyovCyIzUOqKoyGEHnMEcViHVsXx6CU7B293Ufk=
-----END RSA PRIVATE KEY-----';
    // 微信支付相关信息
    const WECHATAPP_ID = 'wxf3dfbccf1d870fd7';
    const WECHATPAY_PARTNER = '1218668001';
    const WECHATPAY_PARTNER_KEY = '7819d8740e32ed775c22149a87ae9f99';
    const WECHATPAY_APP_SECRET = 'fe822d47109965e68b70acf0411f3fd9';
    const WECHATPAY_SIGN_KEY = '2zc1tgxOxtc7AzSqYYP1pUMRx3VFnRcnKzAfwEc1y8vKRawp8b3jqLr0TcWKftBsoQtsG2IlgdiSkNMgEyQkEe2pfcQBLK3M1KdfMLRtnH2exXoNWvRuKqXZ6JpOFtSq';
    const WECHATPAY_GET_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
    const WECHATPAY_URL = 'https://api.weixin.qq.com/pay/genprepay';

    // 金东支付商户号
    const WEPAY_MERCHANT_NUM = '110200111001';
    // 商户备注
    const WEPAY_MERCHANT_REMARK = '青团生活';
    const WEPAY_DESKEY = 'XebvyAj9kuPQIMg3Yvv3eWiAOJ03MUCo';
    const WEPAY_MD5KEY = 'JOjuqqliNUcia0ah6uaOwvRdsYdbwslx';

    /**
     * 构造函数
     */
    public function __construct() {

    }

    private function __getALiSignString($payId, $title, $product, $payFee, $plat) {
        //组装待签名数据
        $plat = strtolower(trim($plat));
        $signData = "partner=\"" . self::ALIPAY_PARTNER . "\"&";
        $signData .= "seller_id=\"" . self::ALIPAY_SELLER . "\"&";
        $signData .= "out_trade_no=\"$payId\"&";
        $signData .= "subject=\"" . urlencode($title) . "\"&";
        $signData .= "body=\"" . urlencode($product) . "\"&";
        $signData .= "total_fee=\"$payFee\"&";
        $signData .= "notify_url=\"" . urlencode(self::CALL_BACK_HANDLE_URL . 'alipay') . "\"&";
        $signData .= "service=\"mobile.securitypay.pay\"&";
        $signData .= "payment_type=\"1\"&";
        $signData .= "_input_charset=\"utf-8\"";
        return $signData;
    }

    /**
     * 获取支付宝支付参数
     * @param type $payId  支付id
     * @param type $title  项目名称
     * @param type $product 产品短标题
     * @param type $payFee 支付金额
     * @param type $flat 平台
     */
    public function getALiPayData($payId, $title, $product, $payFee, $plat) {

        // 获取签名字符串
        $signString = $this->__getALiSignString($payId, $title, $product, $payFee, $plat);

        $res = openssl_get_privatekey(trim(self::PATH_ALI_RSA_PRI_PEM));
        if (!$res) {
            return false;
        }
        //调用openssl内置签名方法，生成签名$sign
        openssl_sign($signString, $sign, $res);

        //释放资源
        openssl_free_key($res);

        //base64编码
        $sign = urlencode(base64_encode($sign));
        $signString.='&sign="' . $sign . '"&sign_type="RSA"';

        return $signString;
    }

    /**
     * 支付宝回调校验处理
     * @return boolean
     */
    public function getALiCallBackVerify() {
        require_once(__DIR__ . '/lib/AliPay/alipay.config.php');
        require_once(__DIR__ . '/lib/AliPay/lib/alipay_notify.class.php');
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if ($verify_result) {//验证成功
            $result['out_trade_no'] = $_POST['out_trade_no'];

            //支付宝交易号
            $result['trade_no'] = $_POST['trade_no'];

            //交易状态
            $result['trade_status'] = $_POST['trade_status'];

            $result['total_fee'] = $_POST['total_fee'];
            //2015.4.8 退款参数的获取
            if (isset($_POST['refund_status'])) {
                $result = array_merge($result, array(
                    'refund_status' => $_POST['refund_status'],
                    'gmt_refund' => $_POST['gmt_refund'],
                    'pay_id' => $_POST['out_trade_no'],
                    'refund_money' => sprintf("%.2f", $_POST['total_fee']),
                    'refund_mark' => "申请退款成功!时间:{$_POST['gmt_refund']}",
                    'refund_ptime' => time(),
                    'refund_etime' => time(),
                ));
            }
            return $result;
        }
        return false;
    }

    /**
     * 获取财付通支付接口
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     * @param type $callbacData
     * @return string
     */
    public function getTenPayData($payId, $title, $product, $payFee, $plat, $callbacData) {


        // 处理 $product
        if (mb_strlen($product, "UTF-8") > 30) {
            $product = mb_substr($product, 0, 28, "UTF-8");
            $product.='...';
        }

        $data = array(
            'ver' => '2.0',
            'sale_plat' => $plat,
            'bank_type' => '0',
            'desc' => $product,
            'bargainor_id' => self::TENPAY_MID,
            'attach' => '_client',
            'sp_billno' => $payId,
            'total_fee' => $payFee * 100,
            'notify_url' => self::CALL_BACK_HANDLE_URL . 'tenpay',
            'callback_url' => self::CALL_BACK_HANDLE_URL . 'tenpay',
        );
        ksort($data);
        $signString = '';
        foreach ($data as $k => $v) {
            $signString.="$k=$v&";
        }
        $signString.="key=" . self::TENPAY_SEC;
        $sign = strtoupper(md5($signString));

        $data['notify_url'] = urlencode($data['notify_url']);
        $data['callback_url'] = urlencode($data['callback_url']);
        $data['desc'] = urlencode($data['desc']);

        $dataString = '';
        foreach ($data as $k => $v) {
            $dataString.="$k=$v&";
        }
        $dataString.="sign=$sign";

        $url = self::TENPAY_URL . '?' . $dataString;
        return $url;
    }

    /**
     * 财付通支付回调校验处理
     */
    public function getTenCallBackVerify($pars) {

        $tenpaySign = strtolower($pars['sign']);
        unset($pars['sign']);
        unset($pars['_URL_']);
        $vars = array();
        foreach ($pars as $key => $value) {
            if (trim($value) != '') {
                $vars[$key] = $value;
            }
        }
        ksort($vars);
        $url = '';
        foreach ($vars as $key => $value) {
            $url.=$key . '=' . $value . '&';
        }
        $sign = $url . 'key=' . self::TENPAY_SEC;
        $sign = strtolower(md5($sign));
        if (trim($sign) == trim($tenpaySign)) {
            $data = array(
                'sp_billno' => isset($pars['sp_billno']) ? $pars['sp_billno'] : '',
                'transaction_id' => isset($pars['transaction_id']) ? $pars['transaction_id'] : '',
                'total_fee' => isset($pars['total_fee']) ? $pars['total_fee'] * 0.01 : '',
            );
            return $data;
        }

        return false;
    }

    /**
     * 全民付支付数据获取
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     */
    public function getUmsPayData($payId, $title, $product, $payFee, $plat) {

        if (!class_exists('Umspay')) {
            include __DIR__ . '/lib/UmsPay/Umspay.class.php';
        }

        $umspay = new \Umspay(self::UMSPAY_MERCHANTID, self::UMSPAY_TERMID);
        $umspay->order_callback_url = self::CALL_BACK_HANDLE_URL . 'umspay';
        $umspay->public_key = self::PATH_UMS_PUB_PEM;
        $umspay->private_key = self::PATH_UMS_RSA_PRI_PEM;

        $res = $umspay->order($product, $payFee, $payId);
        if (!$res) {
            return false;
        }

        $content = "{$res['sigin']}|{$res['ChrCode']}|{$res['TransId']}|{$res['MerOrderId']}";
        if (strtolower(trim($plat)) == 'ios') {
            $content = array(
                $res['TransId'],
                $res['ChrCode'],
                $res['sigin'],
                $res['MerOrderId'],
            );
        }
        $data = array(
            'content' => $content,
            'transId' => $res['TransId']
        );

        return $data;
    }

    /**
     * 全民付支付回调校验处理
     * @return type
     */
    public function getUmsCallBackVerify() {
        if (!class_exists('Umspay')) {
            include __DIR__ . '/lib/UmsPay/Umspay.class.php';
        }

        $umspay = new \Umspay(self::UMSPAY_MERCHANTID, self::UMSPAY_TERMID);
        $umspay->order_callback_url = self::CALL_BACK_HANDLE_URL . 'umspay';
        $umspay->public_key = self::PATH_UMS_PUB_PEM;
        $umspay->private_key = self::PATH_UMS_RSA_PRI_PEM;
        $result = $umspay->notify();
        if ($result && $_POST['TransState'] == 1) {
            $data = array(
                'MerOrderId' => isset($_POST['MerOrderId']) ? $_POST['MerOrderId'] : '',
                'TransId' => isset($_POST['TransId']) ? $_POST['TransId'] : '',
                'TransAmt' => isset($_POST['TransAmt']) ? $_POST['TransAmt'] * 0.01 : '',
            );
            return $data;
        }
        die(json_encode($result));
    }

    /**
     * 全民付支付数据获取
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     */
    public function getUmsOrderQuery($payId, $trade_no) {
        if (!class_exists('Umspay')) {
            include __DIR__ . '/lib/UmsPay/Umspay.class.php';
        }
        $umspay = new \Umspay(self::UMSPAY_MERCHANTID, self::UMSPAY_TERMID);
        $umspay->public_key = self::PATH_UMS_PUB_PEM;
        $umspay->private_key = self::PATH_UMS_RSA_PRI_PEM;
        $res = $umspay->queryOrder($payId, $trade_no);
        if (!$res) {
            return false;
        }
        return $res;
    }

    /**
     * 获取微信签名
     * @param type $data
     */
    private function __getWXsign($data) {
        $param['appid'] = self::WECHATAPP_ID;
        $param['appkey'] = self::WECHATPAY_SIGN_KEY;
        $param['noncestr'] = $data['noncestr'];
        $param['package'] = $data['package'];
        $param['timestamp'] = $data['timestamp'];
        $param['traceid'] = $data['traceid'];

        $str = '';
        foreach ($param as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }
        return sha1(substr($str, 0, -1));
    }

    /**
     * 获取微信app token
     */
    private function __getWXAppToken() {

        $token = F('wx_access_token');
        if (!$token || $token['expire_time'] < time()) {
            $get_data = array(
                'grant_type' => 'client_credential',
                'appid' => self::WECHATAPP_ID,
                'secret' => self::WECHATPAY_APP_SECRET,
            );
            $http = new \Common\Org\Http();
            $json = @json_decode($http->get(self::WECHATPAY_GET_TOKEN_URL, $get_data), true);
            if (!isset($json['access_token'])) {
                return array('error' => '微信token获取失败,error_code:' . $json['errcode'] . ',error_msg:' . $json['errmsg']);
            }
            $json['expire_time'] = time() + 7000;
            F('wx_access_token', $json);
            $token = $json;
        }
        if (isset($token['access_token'])) {
            return $token['access_token'];
        }
        return false;
    }

    /**
     * 获取微信支付参数
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     */
    public function getWXPayData($payId, $title, $product, $payFee, $plat) {

        // 过滤$product
        $product = str_replace("'", "", $product);
        $product = str_replace(" ", "", $product);
        $product = str_replace("+", "", $product);
        $product = cutStr($product, 30, 0, 0);

        // 价格单位转化
        $payFee = $payFee * 100;

        // 生成package
        $data = array(
            'bank_type' => 'WX',
            'body' => $product,
            'fee_type' => '1',
            'input_charset' => 'UTF-8',
            'notify_url' => self::CALL_BACK_HANDLE_URL . 'wxpay',
            'out_trade_no' => $payId,
            'partner' => self::WECHATPAY_PARTNER,
            'spbill_create_ip' => get_client_ip(),
            'total_fee' => strval($payFee),
        );
        ksort($data);
        $signString = '';
        foreach ($data as $key => $val) {
            $signString.="$key=$val&";
        }
        $signString.="key=" . self::WECHATPAY_PARTNER_KEY;
        $sign = strtoupper(md5($signString));
        $package = '';
        foreach ($data as $key => $val) {
            $package.="$key=" . urlencode($val) . "&";
        }
        $package.="sign=$sign";

        //生成prepayid
        $str = md5(uniqid());
        $postData = array(
            'appid' => self::WECHATAPP_ID,
            'traceid' => time() . '_' . rand(1000, 9999),
            'noncestr' => $str,
            'timestamp' => time(),
            'package' => $package
        );
        $postData['app_signature'] = $this->__getWXsign($postData);
        $postData['sign_method'] = 'sha1';

        // 获取访问微信token
        $http = new \Common\Org\Http();
        $token = $this->__getWXAppToken();
        if (!$token) {
            return array('error' => 'token获取失败！');
        }

        $url = self::WECHATPAY_URL . '?access_token=' . $token;
        $res = @json_decode($http->post($url, json_encode($postData)), true);
        if (!isset($res['prepayid'])) {
            return array('error' => '微信预生成订单失败,' . $res['errmsg'], 'error_code' => $res['errcode']);
        }
        $data = array(
            'AppId' => self::WECHATAPP_ID,
            'PartnerId' => self::WECHATPAY_PARTNER,
            'SignKey' => self::WECHATPAY_SIGN_KEY,
            'AppSecret' => self::WECHATPAY_APP_SECRET,
            'pakage' => $package,
            'prepayid' => $res['prepayid'],
        );
        return $data;
    }

    /**
     * 微信支付回调校验处理
     * @return type
     */
    public function getWXCallBackVerify($pars) {
        $tenpaySign = '';
        if (isset($pars['sign'])) {
            $tenpaySign = strtolower($pars['sign']);
            unset($pars['sign']);
        }
        if (isset($pars['_URL_'])) {
            unset($pars['_URL_']);
        }

        $vars = array();
        foreach ($pars as $key => $value) {
            if (trim($value) != '') {
                $vars[$key] = $value;
            }
        }
        ksort($vars);
        $url = '';
        foreach ($vars as $key => $value) {
            $url.=$key . '=' . $value . '&';
        }
        $sign = $url . 'key=' . self::WECHATPAY_PARTNER_KEY;
        $sign = strtolower(md5($sign));

        if (trim($sign) == trim($tenpaySign)) {
            $data = array(
                'out_trade_no' => isset($pars['out_trade_no']) ? $pars['out_trade_no'] : '',
                'transaction_id' => isset($pars['transaction_id']) ? $pars['transaction_id'] : '',
                'total_fee' => isset($pars['total_fee']) ? $pars['total_fee'] * 0.01 : '',
                'trade_state' => isset($pars['trade_state']) ? $pars['trade_state'] : '',
            );
            return $data;
        }

        return false;
    }

    public function getWeixinOrderQuery($orderId, $tradeNo) {
        $sign = strtoupper(md5("out_trade_no={$orderId}&partner=" . self::WECHATPAY_PARTNER . "&key=" . self::WECHATPAY_PARTNER_KEY));
        $package = "out_trade_no=" . $orderId . "&partner=" . self::WECHATPAY_PARTNER . "&sign=$sign";
        $postData = array(
            'appid' => 'wxf3dfbccf1d870fd7',
            'appkey' => self::WECHATPAY_SIGN_KEY,
            'package' => $package,
            'timestamp' => time()
        );
        $str = '';
        foreach ($postData as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }

        // 获取访问微信token
        $http = new \Common\Org\Http();
        $token = $this->__getWXAppToken();
        if (!$token) {
            return false;
        }
        $url = "https://api.weixin.qq.com/pay/orderquery?access_token=" . $token;

        $data = array(
            'appid' => 'wxf3dfbccf1d870fd7',
            'package' => $package,
            'timestamp' => time()
        );
        $data['app_signature'] = sha1(substr($str, 0, -1));
        $data['sign_method'] = 'sha1';
        $res = $http->post($url, json_encode($data));
        $res = json_decode($res, true);
        if ($res['errcode'] == 0) {
            return $res['order_info'];
        } else {
            return false;
        }
    }

    /**
     * 获取京东支付参数
     * @param type $payId  支付id
     * @param type $title  项目名称
     * @param type $product 产品短标题
     * @param type $payFee 支付金额
     * @param type $flat 平台
     */
    public function getWePayData($payId, $title, $product, $payFee, $plat) {
        $data = array(
            // 'merchantNum'    => '22294531',    // 测试用商户号
            'merchantNum'    => self::WEPAY_MERCHANT_NUM,    // 商户号
            'merchantRemark' => self::WEPAY_MERCHANT_REMARK, // 商户备注
            'tradeNum'  => $payId, // 交易流水号
            'tradeName' => $title, // 商品名称
            'tradeDescription' => $product, // 交易描述
            'tradeTime'   => NOW_TIME, // 交易时间
            'tradeAmount' => $payFee, // 金额
            'currency'    => 'CNY', // 币种
            'notifyUrl'   => self::CALL_BACK_HANDLE_URL . 'wepay', // 异步通知地址
        );
        return $data;
    }

    /**
     * 京东支付回调校验处理
     * @return type
     */
    public function getWeCallBackVerify() {
        if (!class_exists('WepayNotify')) {
            include __DIR__ . '/lib/wepay/WepayNotify.php';
        }
        $wepay = new \wepay\WepayNotify();
        // $_POST['resp'] = 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4NCjxDSElOQUJBTks+CiAgPFZFUlNJT04+MS4wLjA8L1ZFUlNJT04+CiAgPE1FUkNIQU5UPjIyMjk0NTMxPC9NRVJDSEFOVD4KICA8VEVSTUlOQUw+MDAwMDAwMDE8L1RFUk1JTkFMPgogIDxEQVRBPkMrRFVZQ3d4WUltNUp4SVo5am94ZFI5L0hrVklnSitTMldpUzFhYWkrZDA4TEhwakx1YmZsVEpqWFl1all2UzRjMmJYTlVHN3JiaGZpU2MxWmZnS083TUVuZFgydkhhcHZRVnVSUkx1SzFLMHhMamZHVkRONFd0YXJ2dG01UUZGeTlHUVR2NWdZRUprNjhUenZVbVlFZmFuaUtOL0JLSlQ0c0dYQzk4RnpXTndWRFhseVdWaDNtZlRZNjNxUDZFMTVqWnJSenlWZWhOZ2FwMnhwekZSRk14QkN0RUNhQXRXeWdFL0VTUHpndTJxdE04cHN2WGxQYzZDd085d05KL2U3UUE0Q3ZTRFBzUXY4SWpLNUtGdUg0MnQ1MXB1WGRqbDZhcTlhTFJ1YjR5Y3EycllFcnVxTkszajdOd0J1Ymk1SncvVmp5Z3JEKzk2Y3N4ZEhweW9FZVV0Tmlwb09rUERwOGdFanVGMDNsa3dUOHl0K01iNExoQUQzYkhuaENGamI2VGJ3OEtNWmt0VEE4cHpuWU9NWHdISUVXb3RGLzlmWXorRG5GT1dWcllDLzlDVmdaQ0dNVWQwYktIelhTQ3VDY0JyeW5mK1R5Q0NxMkpWbjFUV3dycGlPZkVxNk9KZzwvREFUQT4KICA8U0lHTj43ZGU1NmQyY2NiYWUyOTE5ZjNlMWMxMTMzZjA5YTE0NDwvU0lHTj4KPC9DSElOQUJBTks+';
        $res = $wepay->verify(self::WEPAY_MD5KEY, self::WEPAY_DESKEY, $_POST['resp']);
        if (!$res) {
            return false;
        }
        return array_merge($res['data']['TRADE'],$res['data']['RETURN']);
    }

    /**
     * pc网站支付
     */
    public function pcDoPay($payType, $data, $option = array(), $action = '') {

        if (!trim($payType)) {
            return false;
        }

        //服务器异步通知页面路径
        if (!isset($option['notify_url'])) {
            $option['notify_url'] = self::CALL_BACK_HANDLE_URL . $payType;
        }
        //页面跳转同步通知页面路径
        if (!isset($option['return_url'])) {
            $option['return_url'] = self::CALL_BACK_HANDLE_URL . $payType;
        }
        if (!isset($option['merchant_url'])) {
            $option['merchant_url'] = self::CALL_BACK_HANDLE_URL . $payType;
        }
        // 用户付款中途退出返回商户的地址
        if (!isset($option['return_CodeUrl'])) {
            $option['return_CodeUrl'] = self::CALL_BACK_HANDLE_URL . $payType;
        }

        switch (strtolower(trim($payType))) {
            case 'pcalipay':
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/pcDoPay/Alipay.class.php';
                }
                if (trim($action)) {
                    return \Alipay::getInstance()->doPay($data, $option, trim($action));
                }
                \Alipay::getInstance()->doPay($data, $option);
                break;
            case 'pctenpay':
                if (!class_exists('Tenpay')) {
                    include __DIR__ . '/lib/pcDoPay/Tenpay.class.php';
                }
                \Tenpay::getInstance()->dopay($data, $option);
                break;
            default:
                break;
        }
        return true;
    }

    /**
     * pc支付异步回调校验
     */
    public function pcPayVerify($payType) {
        if (!trim($payType)) {
            return false;
        }

        switch (strtolower(trim($payType))) {
            case 'pcalipay':
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/pcDoPay/Alipay.class.php';
                }
                $verify_result = \Alipay::getInstance()->verifyNotify();
                $result = array();
                if ($verify_result) {//验证成功
                    $result['out_trade_no'] = isset($_POST['out_trade_no']) ? $_POST['out_trade_no'] : '';

                    //支付宝交易号
                    $result['trade_no'] = isset($_POST['trade_no']) ? $_POST['trade_no'] : '';

                    //交易状态
                    $result['trade_status'] = isset($_POST['trade_status']) ? $_POST['trade_status'] : '';

                    $result['total_fee'] = isset($_POST['total_fee']) ? $_POST['total_fee'] : '';

                    //2015.4.8 退款参数的获取
                    if (isset($_POST['refund_status'])) {
                        $result = array_merge($result, array(
                            'refund_status' => $_POST['refund_status'],
                            'gmt_refund' => $_POST['gmt_refund'],
                            'pay_id' => $_POST['out_trade_no'],
                            'refund_money' => sprintf("%.2f", $_POST['total_fee']),
                            'refund_mark' => "申请退款成功!时间:{$_POST['gmt_refund']}",
                            'refund_ptime' => time(),
                            'refund_etime' => time(),
                        ));
                    }

                    return $result;
                }
                break;
            case 'alipay_refund':
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/pcDoPay/Alipay.class.php';
                }
                $verify_result = \Alipay::getInstance()->verifyNotify();
                if ($verify_result) {
                    // $order_id = substr($_POST['batch_no'], 8);
                    // 交易号^退款金额^处理结果
                    list($trade_no, $refund_money, $is_success) = @ explode('^', $_POST['result_details'], 3);
                    //TRADE_HAS_FINISHED
                    if (isset($_POST['success_num']) && $_POST['success_num'] > 0 && strtolower(trim($is_success)) == 'success') {
                        $result = array(
                            //   'order_id' => $order_id,
                            'trade_no' => $trade_no,
                            'refund_money' => sprintf("%.2f", $refund_money),
                            'refund_mark' => "申请退款成功，退款流水号[batch_no]：" . ternary($_POST['batch_no'], '') . ',支付宝返回详情：' . ternary($_POST['result_details'], '') . ',操作时间：' . date('Y-m-d H:i:s'),
                            'refund_ptime' => time(),
                            'refund_etime' => time(),
                        );
                        return $result;
                    }
                }
                break;
            case 'alipay_betch_refund':
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/pcDoPay/Alipay.class.php';
                }
                $verify_result = \Alipay::getInstance()->verifyNotify();
                if ($verify_result) {
                    // $order_id = substr($_POST['batch_no'], 8);
                    // 交易号^退款金额^处理结果
                    list($trade_no, $refund_money, $is_success) = @ explode('^', $_POST['result_details'], 3);
                    $result_detail = @explode('#', $_POST['result_details']);
                    if (isset($_POST['success_num']) && $_POST['success_num'] > 0 && $result_detail && count($result_detail) > 0) {
                        $result_data = array();
                        foreach ($result_detail as $v) {
                            list($trade_no, $refund_money, $is_success) = @ explode('^', $v, 3);
                            if (strtolower(trim($is_success)) == 'success') {
                                $result_data[] = array(
                                    'trade_no' => $trade_no,
                                    'refund_money' => sprintf("%.2f", $refund_money),
                                    'refund_mark' => "申请退款成功，退款流水号[batch_no]：" . ternary($_POST['batch_no'], '') . ',支付宝返回详情：' . ternary($_POST['result_details'], '') . ',操作时间：' . date('Y-m-d H:i:s'),
                                    'refund_ptime' => time(),
                                    'refund_etime' => time(),
                                );
                            }
                        }
                        return $result_data;
                    }
                }
                break;
            case 'pctenpay':
                if (!class_exists('Tenpay')) {
                    include __DIR__ . '/lib/pcDoPay/Tenpay.class.php';
                }
                $result = \Tenpay::getInstance()->verifyNotify();
                if ($result) {
                    $data = array(
                        'sp_billno' => isset($result["out_trade_no"]) ? $result["out_trade_no"] : '',
                        'transaction_id' => isset($result["transaction_id"]) ? $result["transaction_id"] : '',
                        'total_fee' => $result["total_fee"] / 100,
                    );
                    return $data;
                }
                break;
            default:
                break;
        }
        return false;
    }

    /**
     * pc支付返回值
     */
    public function pcPayVerifyReturn($payType) {
        if (!trim($payType)) {
            return false;
        }
        switch (strtolower(trim($payType))) {
            case 'pcalipay':
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/pcDoPay/Alipay.class.php';
                }
                $verify_result = \Alipay::getInstance()->verifyReturn();
                $result = array();
                if ($verify_result) {//验证成功
                    $result['out_trade_no'] = isset($_POST['out_trade_no']) ? $_POST['out_trade_no'] : '';

                    //支付宝交易号
                    $result['trade_no'] = isset($_POST['trade_no']) ? $_POST['trade_no'] : '';

                    //交易状态
                    $result['trade_status'] = isset($_POST['trade_status']) ? $_POST['trade_status'] : '';

                    $result['total_fee'] = isset($_POST['total_fee']) ? $_POST['total_fee'] : '';
                    //2015.4.8 退款参数的获取
                    if (isset($_POST['refund_status'])) {
                        $result = array_merge($result, array(
                            'refund_status' => $_POST['refund_status'],
                            'gmt_refund' => $_POST['gmt_refund'],
                            'pay_id' => $_POST['out_trade_no'],
                            'refund_money' => sprintf("%.2f", $_POST['total_fee']),
                            'refund_mark' => "申请退款成功!时间:{$_POST['gmt_refund']}",
                            'refund_ptime' => time(),
                            'refund_etime' => time(),
                        ));
                    }
                    return $result;
                }

                break;
            case 'pctenpay':
                if (!class_exists('Tenpay')) {
                    include __DIR__ . '/lib/pcDoPay/Tenpay.class.php';
                }
                $result = \Tenpay::getInstance()->verifyReturn();
                if ($result) {
                    $data = array(
                        'sp_billno' => isset($result["out_trade_no"]) ? $result["out_trade_no"] : '',
                        'transaction_id' => isset($result["transaction_id"]) ? $result["transaction_id"] : '',
                        'total_fee' => $result["total_fee"] / 100,
                    );
                    return $data;
                }
                break;
            default:
                break;
        }
        return false;
    }

    /**
     * 订单查询
     */
    public function orderQuery($payType, $orderId, $tradeNo = '', $is_all = true) {
        $payType = strtolower(trim($payType));
        $data = array();
        switch ($payType) {
            case 'alipay':
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/pcDoPay/Alipay.class.php';
                }
                $data = \Alipay::getInstance()->getOrderQuery($orderId);
                if ($data) {
                    $pay_result = 'N';
                    if (isset($data['trade_status']) && trim($data['trade_status']) == 'TRADE_SUCCESS') {
                        $pay_result = 'Y';
                    }
                    $data = array(
                        'pay_id' => ternary($data['out_trade_no'], ''),
                        'trade_no' => ternary($data['trade_no'], ''),
                        'pay_money' => ternary($data['total_fee'], ''),
                        'pay_result' => $pay_result,
                    );
                }
                break;
            case 'tenpay':
                if (!class_exists('Tenpay')) {
                    include __DIR__ . '/lib/pcDoPay/Tenpay.class.php';
                }
                $data = \Tenpay::getInstance()->getOrderQuery($orderId, $tradeNo);
                if ($data) {
                    $pay_result = 'N';
                    if (isset($data['trade_state']) && trim($data['trade_state']) == '0') {
                        $pay_result = 'Y';
                    }
                    $data = array(
                        'pay_id' => ternary($data['out_trade_no'], ''),
                        'trade_no' => ternary($data['transaction_id'], ''),
                        'pay_money' => sprintf('%.2f', ternary($data['total_fee'], 0) / 100),
                        'pay_result' => $pay_result,
                    );
                }
                break;
            case 'umspay':
                $data = $this->getUmsOrderQuery($orderId, $tradeNo);
                if ($data) {
                    $pay_result = 'N';
                    if (isset($data['TransState']) && trim($data['TransState']) == '1') {
                        $pay_result = 'Y';
                    }
                    $data = array(
                        'pay_id' => ternary($data['MerOrderId'], ''),
                        'trade_no' => ternary($data['TransId'], ''),
                        'pay_money' => sprintf('%.2f', ternary($data['TransAmt'], 0) / 100),
                        'pay_result' => $pay_result,
                    );
                }
                break;
            case 'wechatpay':
            case 'wxpay':
                $data = $this->getWeixinOrderQuery($orderId, $tradeNo);
                if ($data) {
                    $pay_result = 'N';
                    if (isset($data['trade_state']) && trim($data['trade_state']) == '0') {
                        $pay_result = 'Y';
                    }
                    $data = array(
                        'pay_id' => ternary($data['out_trade_no'], ''),
                        'trade_no' => ternary($data['transaction_id'], ''),
                        'pay_money' => sprintf('%.2f', ternary($data['total_fee'], 0) / 100),
                        'pay_result' => $pay_result,
                    );
                }
                break;
            case 'wapwechatpay':
            case 'pcwxpaycode':
                if (!class_exists('PcWXpay')) {
                    include __DIR__ . '/lib/pcWXpay/pcWXpay.class.php';
                }
                $wxquery = new \PcWXpay();
                $data = $wxquery->orderQuery($orderId);
                if ($data) {
                    $pay_result = 'N';
                    if (isset($data['trade_state']) && trim($data['trade_state']) == 'SUCCESS') {
                        $pay_result = 'Y';
                    }
                    $data = array(
                        'pay_id' => ternary($data['out_trade_no'], ''),
                        'trade_no' => ternary($data['transaction_id'], ''), 'pay_money' => sprintf('%.2f', ternary($data['total_fee'], 0) / 100),
                        'pay_result' => $pay_result,
                    );
                }
                break;
            case 'unionpay':
            case 'wapunionpay':
                if ($payType == 'wapunionpay') {
                    define('WAP_UNION_PAY_MODEL', true);
                }
                if (!class_exists('UnionPay')) {
                    include __DIR__ . '/lib/unionpay/UnionPay.class.php';
                }
                $unionPay = new \UnionPay();
                $data = $unionPay->getUnionPayQuery($orderId, $tradeNo);
                if ($data) {
                    $pay_result = 'N';
                    if (isset($data['origRespCode']) && trim($data['origRespCode']) == '00') {
                        $pay_result = 'Y';
                    }
                    $data = array(
                        'pay_id' => ternary($data['orderId'], ''),
                        'trade_no' => ternary($data['queryId'], ''),
                        'pay_money' => sprintf('%.2f', ternary($data['txnAmt'], 0) / 100),
                        'pay_result' => $pay_result,
                    );
                }
                break;
            case 'lianlianpay':
                if (!class_exists('LianlianPay')) {
                    include __DIR__ . '/lib/lianlianpay/LianlianPay.class.php';
                }
                $lianlianPay = new \LianlianPay();
                $data = $lianlianPay->getAppLianLianQuery($orderId, $tradeNo);
                if ($data) {
                    $pay_result = 'N';
                    if (isset($data['ret_code']) && trim($data['ret_code']) == '0000' && isset($data['result_pay']) && trim($data['result_pay']) == 'SUCCESS') {
                        $pay_result = 'Y';
                    }
                    $data = array(
                        'pay_id' => ternary($data['no_order'], ''),
                        'trade_no' => ternary($data['oid_paybill'], ''),
                        'pay_money' => sprintf('%.2f', ternary($data['money_order'], 0)),
                        'pay_result' => $pay_result,
                    );
                }
                break;
            case 'wepay':
                if (!class_exists('WepayQuery')) {
                    include __DIR__ . '/lib/wepay/WepayQuery.php';
                }
                $wepayquery = new \wepay\WepayQuery();
                $rdata = $wepayquery->query($orderId);
                if (!isset($rdata['errorMsg']) || $rdata['errorMsg'] == null) {
                    $data = array(
                        'pay_id' => ternary($rdata['queryDatas']['data']['tradeNum'], ''),
                        'trade_no' => '',
                        'pay_money' => sprintf('%.2f', $rdata['queryDatas']['data']['tradeAmount']/100, 0),
                        'pay_result' => $rdata['queryDatas']['data']['tradeStatus'] == 0 ? 'Y' : 'N',
                    );
                } else {
                    $data = null;
                }
            default:
                break;
        }

        // 是否全部查
        $data['service_name'] = order_service($payType);
        if ($is_all) {
            if (!isset($data['trade_no']) || !trim($data['trade_no'])) {
                foreach (array('alipay',
            'tenpay', 'umspay', 'wechatpay', 'pcwxpaycode', 'unionpay', 'wapunionpay') as $v) {
                    $data = $this->orderQuery($v, $orderId, $tradeNo, false);
                    if (isset($data['trade_no']) && trim($data['trade_no'])) {
                        $data['service_name'] = order_service($v);
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * pc微信扫码支付
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     * @return type
     */
    public function getPCWXpayData($payId, $title, $product, $payFee, $plat) {
        $notify_url = self::CALL_BACK_HANDLE_URL . 'pcwxpaycode';
        if (!class_exists('PcWXpay')) {
            include __DIR__ . '/lib/pcWXpay/pcWXpay.class.php';
        }
        $pc_wx_pay = new \PcWXpay();
        $res = $pc_wx_pay->createWXpayCode($payId, $title, $product, $payFee, $plat, $notify_url);
        return $res;
    }

    /**
     * 回调校验
     * @return type
     */
    public function getPCWXpayVerify() {
        if (!class_exists('PcWXpayNotify')) {
            include __DIR__ . '/lib/pcWXpay/pcWXpayNotify.class.php';
        }
        $pcWXpayNotify = new \PcWXpayNotify();
        $pcWXpayNotify->Handle(false);
        $code = $pcWXpayNotify->GetReturn_code();
        $msg = $pcWXpayNotify->GetReturn_msg();
        if (strtolower(trim($code)) == 'success' && strtolower(trim($msg)) == 'ok') {
            $pars = $pcWXpayNotify->getOrderData();
            $data = array(
                'out_trade_no' => isset($pars['out_trade_no']) ? $pars['out_trade_no'] : '',
                'transaction_id' => isset($pars['transaction_id']) ? $pars['transaction_id'] : '',
                'total_fee' => isset($pars['total_fee']) ? $pars['total_fee'] * 0.01 :
                        '',
            );
            return $data;
        }
        return $res;
    }

    /**
     * 返回数据整理
     * @param type $data
     * @return type/
     */
    function setpcWXReturnValue($data, $payAction = '') {
        $payAction = strtolower(trim($payAction));
        switch ($payAction) {
            case 'pcwxpaycode':
            case 'wapwechatpay':
                if (!class_exists('PcWXpayNotify')) {
                    include __DIR__ . '/lib/pcWXpay/pcWXpayNotify.class.php';
                }
                $pcWXpayNotify = new \PcWXpayNotify();

                if (isset($data['error']) && trim($data['error'])) {
                    $pcWXpayNotify->SetReturn_code("FAIL");
                    $pcWXpayNotify->SetReturn_msg("支付回调失败！");
                    $data ['error'] = $pcWXpayNotify->ToXml();
                }
                if (isset($data['message']) && trim($data['message'])) {
                    $pcWXpayNotify->SetReturn_code("SUCCESS");
                    $pcWXpayNotify->SetReturn_msg("OK");

                    $data['message'] = $pcWXpayNotify->ToXml();
                }
                break;
            case 'lianlianpay':
            case 'lianlianpay_refund':
                if (isset($data['error']) && trim($data['error'])) {
                    $data['error'] = "{'ret_code':'9999','ret_msg':'回调支付失败'}";
                }
                if (isset($data['message']) && trim($data['message'])) {
                    $data ['message'] = "{'ret_code':'0000','ret_msg':'交易成功'}";
                }
                break;
            default:
                break;
        }
        return $data;
    }

    /**
     * wap支付
     */
    public function wapDoPay($payType, $data, $option = array(), $action = '') {

        if (!trim($payType)) {
            return false;
        }

        //服务器异步通知页面路径
        if (!isset($option['notify_url'])) {
            $option['notify_url'] = self::CALL_BACK_HANDLE_URL . $payType;
        }
        //页面跳转同步通知页面路径
        if (!isset($option['return_url'])) {
            $option['return_url'] = self::CALL_BACK_HANDLE_URL . $payType;
        }
        if (!isset($option['merchant_url'])) {
            $option['merchant_url'] = self::CALL_BACK_HANDLE_URL . $payType;
        }
        // 用户付款中途退出返回商户的地址
        if (!isset($option['return_CodeUrl'])) {
            $option['return_CodeUrl'] = self::CALL_BACK_HANDLE_URL . $payType;
        }

        switch (strtolower(trim($payType))) {
            case 'wapalipay':
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/wapDoPay/Alipay.class.php';
                }
                if (trim($action)) {
                    return \ Alipay::getInstance()->doPay($data, $option, trim($action));
                }
                \Alipay::getInstance()->doPay($data, $option);
                break;
            case 'waptenpay':
                if (!class_exists('Tenpay')) {
                    include __DIR__ . '/lib/wapDoPay/Tenpay.class.php';
                }
                \Tenpay::getInstance()->dopay($data, $option);
                break;
            case 'wapumspay':
                if (!class_exists('Umspay')) {
                    include __DIR__ . '/lib/UmsPay/Umspay.class.php';
                }
                $umspay = new \Umspay(self::UMSPAY_MERCHANTID, self::UMSPAY_TERMID);
                $umspay->order_callback_url = self::CALL_BACK_HANDLE_URL . 'wapumspay';
                $umspay->public_key = self::PATH_UMS_PUB_PEM;
                $umspay->private_key = self::PATH_UMS_RSA_PRI_PEM;

                $result = $umspay->order($data['product_name'], $data['total_fee'], $data['out_trade_no']);

                $param['merSign'] = $umspay->sign($result['TransId'] . $result['ChrCode']);
                $param['ChrCode'] = $result['ChrCode'];

                $param['TranId'] = $result['TransId'];
                $param['url'] = $option['return_url'];
                $shtml = $umspay->buildRequestForm($param, 'post', '去支付');
                echo $shtml;
                break;
            case 'wapwechatpay':
                if (!class_exists('PcWXpay')) {
                    include __DIR__ . '/lib/pcWXpay/pcWXpay.class.php';
                }
                $wxpay = new \PcWXpay();
                return $wxpay->doPay($data, $option);
                break;
            default:
                break;
        }
        return true;
    }

    /**
     * wap支付异步回调校验
     */
    public function wapPayVerify($payType) {
        if (!trim($payType)) {
            return false;
        }
        switch (strtolower(trim($payType))) {
            case 'wapalipay':
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/wapDoPay/Alipay.class.php';
                }
                $verify_result = \Alipay::getInstance()->verifyNotify();
                $result = array();
                if ($verify_result) {//验证成功
                    $doc = new \DOMDocument();
                    $doc->loadXML($_POST['notify_data']);
                    $result['out_trade_no'] = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
                    //支付宝交易号
                    $result['trade_no'] = $doc->getElementsByTagName("trade_no")->item(0)->nodeValue;
                    //交易状态
                    $result['trade_status'] = $doc->getElementsByTagName("trade_status")->item(0)->nodeValue;
                    $result['total_fee'] = $doc->getElementsByTagName("total_fee")->item(0)->nodeValue;
                    //2015.4.8 退款参数的获取
                    if (isset($_POST['refund_status'])) {
                        $result = array_merge($result, array(
                            'refund_status' => $doc->getElementsByTagName("refund_status")->item(0)->nodeValue,
                            'gmt_refund' => $doc->getElementsByTagName("gmt_refund")->item(0)->nodeValue,
                            'pay_id' => $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue,
                            'refund_money' => sprintf("%.2f", $doc->getElementsByTagName("total_fee")->item(0)->nodeValue),
                            'refund_mark' => "申请退款成功!时间:" . $doc->getElementsByTagName("gmt_refund")->item(0)->nodeValue,
                            'refund_ptime' => time(),
                            'refund_etime' => time(),
                        ));
                    }
                    return $result;
                }
                break;
            case 'waptenpay':
                if (!class_exists('Tenpay')) {
                    include __DIR__ . '/lib/wapDoPay/Tenpay.class.php';
                }
                $result = \Tenpay::getInstance()->verifyNotify();
                if ($result) {
                    $data = array(
                        'sp_billno' => isset($result["sp_billno"]) ? $result["sp_billno"] : '',
                        'transaction_id' => isset($result["transaction_id"]) ? $result["transaction_id"] : '',
                        'total_fee' => isset($result["total_fee"]) ? $result["total_fee"] :
                                '',
                    );
                    return $data;
                }
                break;
            default:
                break;
        }
        return false;
    }

    /**
     * 获取app银联支付参数
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     * @return type
     */
    public function getAppUnionPayData($payId, $title, $product, $payFee, $plat) {
        $notify_url = self:: CALL_BACK_HANDLE_URL . 'unionpay';
        if (!class_exists('UnionPay')) {
            include __DIR__ . '/lib/unionpay/UnionPay.class.php';
        }
        $unionPay = new \UnionPay();
        $res = $unionPay->getAppUnionPayData($payId, $title, $product, $payFee, $plat, $notify_url);
        return $res;
    }

    /**
     * 获取wap银联支付参数
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     * @return type
     */
    public function getWapUnionPayData($payId, $title, $product, $payFee, $plat, $sysn_url) {
        define('WAP_UNION_PAY_MODEL', true);
        $notify_url = self::CALL_BACK_HANDLE_URL . 'wapunionpay';
        if (!class_exists('UnionPay')) {
            include __DIR__ . '/lib/unionpay/UnionPay.class.php';
        }
        $unionPay = new \UnionPay();
        $res = $unionPay->getWapUnionPayData($payId, $title, $product, $payFee, $plat, $notify_url, $sysn_url);
    }

    /**
     * 银联支付回调校验
     * @return type
     */
    public function getAppUnionPayVerify($payAction = 'unionpay') {
        if (strpos(strtolower(trim($payAction)), 'wapunionpay') !== false) {
            define('WAP_UNION_PAY_MODEL', true);
        }
        if (!class_exists('UnionPay')) {
            include __DIR__ . '/lib/unionpay/UnionPay.class.php';
        }
        $unionPay = new \ UnionPay();
        return $unionPay->getAppUnionPayVerify();
    }

    /**
     * 连连支付 支付参数获取
     * @param type $payId
     * @param type $title
     * @param type $product
     * @param type $payFee
     * @param type $plat
     * @return type
     */
    public function getAppLianlianPayData($payId, $title, $product, $payFee, $user_info) {
        $notify_url = self::CALL_BACK_HANDLE_URL . 'lianlianpay';
        if (!class_exists('LianlianPay')) {

            include __DIR__ . '/lib/lianlianpay/LianlianPay.class.php';
        }
        $lianlianPay = new \LianlianPay();
        $res = $lianlianPay->getLianlianPayData($payId, $title, $product, $payFee, $user_info, $notify_url);
        return $res;
    }

    /**
     * 连连支付回调校验
     * @return type
     */
    public function getAppLianlianPayVerify() {
        if (!class_exists('LianlianPay')) {
            include __DIR__ . '/lib/lianlianpay/LianlianPay.class.php';
        }
        $lianlianPay = new \LianlianPay();

        return $lianlianPay->getAppLianlianPayVerify();
    }

    /**
     * 连连支付回调校验
     * @return type
     */
    public function getAppLianlianPayRefundVerify() {
        if (!class_exists('LianlianPay')) {
            include __DIR__ . '/lib/lianlianpay/LianlianPay.class.php';
        }
        $lianlianPay = new \LianlianPay();
        return $lianlianPay->getAppLianlianPayRefundVerify();
    }

    /**
     * 京东支付退款回调校验
     * @return type
     */
    public function getWePayRefoundVerify() {
        if (!class_exists('WepayRefund')) {
            include __DIR__ . '/lib/wepay/WepayRefund.php';
        }
        $wepayrefundsign = new \wepay\WepayRefundSign();
        return $wepayrefundsign->refoundVerify();
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
        if (!isset($data['pay_id']) || !trim($data['pay_id'])) {
            return array('error' => 'pay_id 不能为空！');
        }
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
        $type = strtolower(trim($data['type']));

        switch ($type) {
            // 微信退款 wechatpay|wxpay|wapwechatpay|pcwxpaycode

            case 'wapwechatpay':
            case 'pcwxpaycode' :
                if ($type == 'wechatpay' || $type == 'wxpay') {
                    $data = array_merge($data, array('app_id' => self:: WECHATAPP_ID,
                        'mch_id' => self::WECHATPAY_PARTNER,
                        'md5_key' => self::WECHATPAY_PARTNER_KEY,));
                }
                if (!class_exists('PcWXpay')) {
                    include __DIR__ . '/lib/pcWXpay/pcWXpay.class.php';
                }
                $pc_wx_pay = new \PcWXpay();
                $res = $pc_wx_pay->doWxPayRefund($data);
                if (isset($res['result_code']) && $res['result_code'] == 'SUCCESS') {
                    $r_data = array(
                        'pay_id' => ternary($res['out_trade_no'], ''),
                        'trade_no' => ternary($res['transaction_id'], ''),
                        'refund_money' => sprintf("%.2f", ternary($res['refund_fee'] / 100, '')),
                        'refund_mark' => "申请退款成功，退款流水号：{$res['refund_id']}",
                        'refund_ptime' => time(),
                        'refund_etime' => time(),
                    );
                    return $r_data;
                }
                return array('error' => ternary($res['result_code'], '') . '-' . ternary($res['err_code_des'], ''));
                break;
            case 'pctenpay':
            case 'tenpay':
                $data['plat'] = 'pc';
            case 'wechatpay':
            case 'wxpay':
                if (!isset($data['plat']) || !trim($data['plat'])) {
                    $data['plat'] = 'wx';
                }
            case 'tenwap':
            case 'waptenpay' :
                if (!isset($data['plat']) || !trim($data['plat'])) {
                    $data['plat'] = 'wap';
                }
                if (!class_exists('TenPayRefund')) {
                    include_once __DIR__ . '/lib/tenpayRefund/tenPayRefund.class.php';
                }
                $ten_pay_refund = new \TenPayRefund();
                $res = $ten_pay_refund->doTenPayRefund($data);
                if ($type == 'tenpay' && isset($res['error'])) {
                    $res['error'] = "{$res['error']},不支持自动退款，请前去财付通wap商家后台手动退款操作！";
                }
                return $res;
                break;
            case 'unionpay' :
            case 'wapunionpay':
                // 微信app
                if ($type == 'wapunionpay') {
                    define('WAP_UNION_PAY_MODEL', true);
                }
                if (!class_exists('UnionPay')) {
                    include __DIR__ . '/lib/unionpay/UnionPay.class.php';
                }
                $data['notify_url'] = self:: CALL_BACK_HANDLE_URL . $type . '_refund';
                $unionPay = new \UnionPay();
                $res = $unionPay->doUnionRefund($data);
                if (isset($res['respCode']) && $res['respCode'] == '00') {
                    list($_, $order_id) = explode('U', $res['orderId']);
                    $r_data = array(
                        'order_id' => $order_id,
                        'trade_no' => ternary($res['origQryId'], ''),
                        'refund_money' => sprintf("%.2f", ternary($res['txnAmt'] / 100, '')),
                        'refund_mark' => "申请退款成功，退款流水号：{$res['queryId']}",
                        'refund_ptime' => time(),
                    );
                    return $r_data;
                }
                return array('error' => ternary($res['respMsg'], ''));
                break;
            case 'lianlianpay':

                if (!class_exists('LianlianPay')) {
                    include __DIR__ . '/lib/lianlianpay/LianlianPay.class.php';
                }
                $lianlianPay = new \LianlianPay();
                $data['notify_url'] = self::CALL_BACK_HANDLE_URL . $type . '_refund';

                $res = $lianlianPay->doLianlianPayRefund($data);
                if (isset($res['ret_code']) && $res['ret_code'] == '0000') {
                    list($trade_no, $order_id) = @explode('U', $res['no_refund'], 2);
                    $r_data = array(
                        'order_id' => $order_id,
                        'trade_no' => $trade_no,
                        'refund_money' => sprintf("%.2f", ternary($res['money_refund'], '')),
                        'refund_mark' => "申请退款成功，退款流水号：{$res['oid_refundno']}",
                        'refund_ptime' => time(),
                    );
                    return $r_data;
                }
                return array('error' => ternary($res['ret_msg'], ''));
                break;
            case 'alipay':
            case 'aliwap':
            case 'aliapp':
            case 'pcalipay':
            case 'wapalipay':
                $data = array_merge($data, array(
                    'seller_email' => self::ALIPAY_SELLER,
                    'notify_url' => self::CALL_BACK_HANDLE_URL . 'alipay_refund'
                ));
                if (!class_exists('Alipay')) {
                    include __DIR__ . '/lib/pcDoPay/Alipay.class.php';
                }
                \Alipay::getInstance()->doAlipayRefund($data);
                break;
            case 'wepay':
                // ---------------------------------------------------------------------------------------
                if (!class_exists('WepayRefund')) {
                    include __DIR__ . '/lib/wepay/WepayRefund.php';
                }
                $r_parms = array(
                    'version'     => '1.0',
                    'merchantNum' => self::WEPAY_MERCHANT_NUM,
                    // ---------------------------
                    'tradeNum'      => str_replace('go', 're', $data['pay_id']),
                    'oTradeNum'     => $data['pay_id'],
                    'tradeAmount'   => $data['refund_money'],
                    'tradeCurrency' => 'CNY',
                    'tradeDate'     => date('Ymd',NOW_TIME),
                    'tradeTime'     => date('His',NOW_TIME),
                    'tradeNotice'   => self::CALL_BACK_HANDLE_URL . $type . '_refund',
                    'tradeNote'     => ''
                );
                $wepayrefund = new \wepay\WepayRefund();
                $res = $wepayrefund->execute($r_parms);
                if (!isset($res['errorMsg']) || $res['errorMsg'] == null) {
                    $tdata = $res['resultData']['data'];
                    list($_, $order_id, $_, $_,) = explode('-', $tdata['tradeNum']);
                    $r_data = array(
                        'order_id' => $order_id,
                        'trade_no' => '0',
                        'refund_money' => sprintf("%.2f", ternary($tdata['tradeAmount'] / 100, '')),
                        'refund_mark' => "申请退款成功，退款流水号：{$tdata['tradeNum']}",
                        'refund_ptime' => time(),
                    );
                    return $r_data;
                }
                return array('error' => ternary($res['errorMsg'], ''));
                break;
            default:
                return array('error' => $type . ' 该形式不支持接口退款！请到相应的后台系统手动退款！');
                break;
        }
    }

    /**
     * 阿里批量退款
     */
    public function alipayBetchRefund($data = array()) {
        if (!isset($data['batch_max_order_id']) || !$data['batch_max_order_id']) {
            return array('error' => 'batch_max_order_id 不能为空！');
        }
        if (!isset($data['batch_num']) || !$data['batch_num']) {
            return array('error' => 'batch_num 不能为空！');
        }
        if (!isset($data['batch_data']) || !$data['batch_data']) {
            return array('error' => 'batch_data 不能为空！');
        }
        $data = array_merge($data, array(
            'seller_email' => self::ALIPAY_SELLER,
            'notify_url' => self::CALL_BACK_HANDLE_URL . 'alipay_betch_refund'
        ));
        if (!class_exists('Alipay')) {
            include __DIR__ . '/lib/pcDoPay/Alipay.class.php';
        }
        \Alipay::getInstance()->doAlipayBetchRefund($data);
    }
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

}
