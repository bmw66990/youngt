<?php

/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-13
 * Time: 上午11:42
 */

namespace Common\Org;

/**
 * Class OSS
 *
 * @package Common\Org
 */
class WeiXin {

    const URL = 'https://api.weixin.qq.com/sns/';
    const LOGIN_APPID = 'wxf3dfbccf1d870fd7';
    const PC_LOGIN_APPID = 'wxf94ce5ddc4fbb1e1';
    const PC_LOGIN_APPSECRET = '2690d88b63bae2354701577dfa902fcb';
    const LOGIN_APPSECRET = 'fe822d47109965e68b70acf0411f3fd9';
    const SEND_APPID = 'wx71ef1edff818d209';
    const SEND_APPSECRET = '6ca61e882c27a18dfd063882d80f96bf';
    const GET_QR_URL = 'https://api.weixin.qq.com/cgi-bin';
    const SHOW_QR_URL = 'https://mp.weixin.qq.com/cgi-bin/showqrcode';
    const SEND_COUPON = 'https://api.weixin.qq.com/cgi-bin/message/template/send';
    const CALLBACK_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    private $ch = null;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->connect();
    }

    public function connect() {
        $this->ch = curl_init();
        // 设置基本属性
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, "Accept-Charset: utf-8");
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function send($uri = '', $data = array()) {
        if (!trim($uri)) {
            return array('error' => '操作不能为空！');
        }
        $url = self::URL . $uri;
        if ($data) {
            $data_str = '';
            foreach ($data as $k => $v) {
                trim($data_str) ? $data_str .= "&$k=$v" : $data_str .= "$k=$v";
            }
            $url .= "?$data_str";
        }
        curl_setopt($this->ch, CURLOPT_URL, $url);
        // 发送
        $result = curl_exec($this->ch);
        if (@curl_errno($this->ch)) {
            $result = array('error' => '错误提示：' . curl_error($this->ch));
        }
        return $result;
    }

    /**
     * 获取微信token
     * @param type $code
     * @return type
     */
    public function getToken($code,$from='app') {

        // 参数判断
        if (!trim($code)) {
            return array('error' => '微信授权码不能为空！');
        }

        $uri  = 'oauth2/access_token';
        $data = array(
            'appid'      => self::LOGIN_APPID,
            'secret'     => self::LOGIN_APPSECRET,
            'code'       => trim($code),
            'grant_type' => 'authorization_code',
        );
        if($from == 'pc'){
            $data['appid']  = self::PC_LOGIN_APPID;
            $data['secret'] = self::PC_LOGIN_APPSECRET;
        }
        $res = $this->send($uri, $data);
        if (is_string($res)) {
            $res = json_decode($res, true);
        }
        if ($res === NULL || isset($res['errcode'])) {
            return array('error' => '微信token获取失败！');
        }

        return $res;
    }

    /**
     * 获取微信用户基本信息
     * @param type $code
     * @return type
     */
    public function getWeiXinUserInfo($code = '') {
        list($token, $openid) = @ explode('|', $code);
        $uri  = 'userinfo';
        $data = array(
            'access_token' => $token, // $acccessToken['access_token'],
            'openid'       => $openid, // $acccessToken['openid'],
        );
        $res = $this->send($uri, $data);
        if (is_string($res)) {
            $res = json_decode($res, true);
        }
        if ($res === NULL || isset($res['errcode'])) {
            return array('error' => '微信用户信息获取失败！');
        }
        return $res;
    }

    /**
     * 获取二维码
     * @param type $token
     * @param type $order
     * @return string
     */
    public function getQRImageUrl($order_id,$token) {
        $quert = array(
            'expire_seconds' => 1800,
            'action_name'    => 'QR_SCENE',
            'action_info'    => array(
                'scene' => array(
                    'scene_id' => $order_id
                )
            ),
        );
        $url   = self::GET_QR_URL . "/qrcode/create?access_token=" . $token;
        $http  = new \Common\Org\Http();
        $res   = json_decode($http->post($url, json_encode($quert)), true); 
        if (isset($res['ticket'])) {
            return self::SHOW_QR_URL . '?ticket=' . $res['ticket'];
        } else {
            return '';
        }
    }

    public function getAccessToken(){
        // 获取token
        $http  = new \Common\Org\Http();
        $url   = self::GET_QR_URL . '/token'; //?appid=wx71ef1edff818d209&secret=6ca61e882c27a18dfd063882d80f96bf';
        $data  = array(
            'appid'      => self::SEND_APPID,
            'secret'     => self::SEND_APPSECRET,
            'grant_type' => 'client_credential'
        );
        $token = json_decode($http->get($url, $data), true);
        if (isset($token['access_token'])) {
            return $token['access_token'];
        }else{
            return false;
        }
    }

    /**
     * 验证成功发送券号
     * @param $data
     */
    public function sendVerifyCoupon($data) {
        $now       = date('Y年m月d日 H:i:s', time());
        $prices    = ceil($data['price']);
        $content   = '消费成功！评价可赚取' . $prices . '积分。';
        $sendData  = array(
            'touser'      => $data['wxuser'],
            'template_id' => 'fG9b3hvl7Jidi8D1wW9B5V9o-u8noWxvVs3SBG-4j3w',
            'url'         => "http://m.youngt.com",
            'topcolor'    => '#7B68EE',
            'data'        => array(
                'first'    => array(
                    'value' => urlencode('你好，你的团购券已使用'),
                    'color' => '#743A3A',
                ),
                'keyword1' => array(
                    'value' => urlencode($data['coupon']),
                    'color' => '#743A3A',
                ),
                'keyword2' => array(
                    'value' => urlencode($prices . '元'),
                    'color' => '#FF0000',
                ),
                'keyword3' => array(
                    'value' => urlencode($now),
                    'color' => '#000000',
                ),
                'keyword4' => array(
                    'value' => urlencode($data['title']),
                    'color' => '#FF0000',
                ),
                'remark'   => array(
                    'value' => urlencode($content),
                    'color' => '#008000'
                ),
            )
        );
        $json_data = urldecode(json_encode($sendData));
        $url       = self::SEND_COUPON . "?access_token={$data['token_id']}";
        $http      = new \Common\Org\Http();
        $res       = json_decode($http->post($url, $json_data));
        if($res->errcode == 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 购买成功发送券号
     * @param $data
     */
    Public function sendBuyCoupon($data) {
        $end_time = date('Y年m月d日', $data['end_time']);
        $content  = "请将此券号提供给商家进行验证,券号过期时间：" . $end_time;
        $sendData     = array(
            'touser'      => $data['wxuser'],
            'template_id' => '-8IVL3IxgPksiG4XyOfBTXrXe0xDKunAbgG_f54CsWg',
            'url'         => "http://mobile.youngt.com/WeiXin/index/oid/{$data['order_id']}",
            'topcolor'    => '#7B68EE',
            'data'        => array(
                'first'          => array(
                    'value' => urlencode('恭喜你在青团网购买成功'),
                    'color' => '#743A3A',
                ),

                'hotelName'      => array(
                    'value' => urlencode($data['title']),
                    'color' => '#FF0000',
                ),
                'voucher number' => array(
                    'value' => urlencode($data['coupon']),
                    'color' => '#C4C400',
                ),
                'remark'         => array(
                    'value' => urlencode($content),
                    'color' => '#008000'
                ),
            )
        );
        $json_data     = urldecode(json_encode($sendData));
        $url      = self::SEND_COUPON."?access_token={$data['token_id']}";
        $http  = new \Common\Org\Http();
        $res      = json_decode($http->post($url, $json_data));
        if($res->errcode == 0){
            return true;
        }else{
            return false;
        }
    }

    //联合登陆回调
    public function getWeChatInfo($code){
        $token = $this->getToken($code,'pc');
        if(isset($token['error']) && $token['error']){
            return $token;
        }
        $str = $token['access_token'].'|'.$token['openid'];
        return $this->getWeiXinUserInfo($str);
    }

}
