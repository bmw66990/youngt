<?php

namespace Api\Controller;

class IndexController extends CommonController {

    /**
     * @var bool 是否验证uid
     */
    protected $checkUser = false;
    protected $signCheck = false;
    protected $tokenCheck = false;

    function __construct() {
        C('signCheck', false);
        C('tokenCheck', false);
        parent:: __construct();
    }

    public function index() {

        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 10
0px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>青团网客户
端接口</b>！', 'utf-8');
        exit;
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>', 'utf-8');
    }

    public function testPushMessage() {
        $type = I('get.type', '1', 'strval');

        $data = array(
            'title' => '青团网测试推送',
            'content' => '青团网测试内容',
            'custom' => array('type' => "team_detail", 'data' => array('id' => 92803)),
            'plat' => 'android',
        );
        $str = '';
        switch ($type) {
            case '1':
                $str = '标签推：';
                $city_id = I('get.city_id', '1', 'strval');
                $city_ids = explode('@', $city_id);
                $data['tags'] = $city_ids;
                $res = $this->_pushMessageToTags($data);
                break;
            case '2':
                $str = '用户id推：';
                $uid = I('get.uid', '300623', 'strval');
                $uids = explode('@', $uid);
                $data['account'] = $uids;
                $res = $this->_pushMessageToAccess($data);
                break;
            case '3':
            default:
                $str = '全设备推：';
                $res = $this->_pushMessageToAll($data);
                break;
        }
        //var_dump($str);
        //var_dump($res);
    }

    public function xxt() {
        $method = "POST";
        $url_path = "http://www.youngt.com";
        $data['address'] = "雁塔区";
        $data['contact'] = "15209219901";
        $data['content'] = "加盟";
        ksort($data);
        $strs = strtoupper($method) . '&' . rawurlencode($url_path) . '&';
        $str = "";
        foreach ($data as $key => $val) {
            $str .= "$key=$val&";
        }
        $strc = substr($str, 0, strlen($str) - 1);
        $strt = $strs . rawurlencode($strc);
        $sig = sha1($strt);
        $sign = \Think\Crypt\Driver\Xxtea::encrypt($sig, 'youngtxxx');
        $data['sig'] = rawurlencode(base64_encode($sign));
        $url = 'http://117.34.73.235:88/Api/Category/getCityList';
        $res = self::_smsPost($url, $data);
        dump($res);
        exit;
    }

    protected function _smsPost($url, $data) {
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        return $tmpInfo;
    }

    public function feed() {
        $data['title'] = '13259976039';
        $data['category'] = 'zhaoshang';
        $data['contact'] = '13259976039';
        $data['address'] = '1748';
        $data['content'] = '1748';
        $res = $this->_smsPost("http://www.a.com/Api/Public/Station", $data);
        dump($res);
        exit;
    }

    public function sms() {
        $data['mobile'] = '13259976039';
        $data['content'] = '1748';
        $res = $this->_sms($data['mobile'], $data['content']);
        dump($res);
        exit;
    }

    public function money() {
        $sendSms = new \Common\Org\sendSms();
        $res = $sendSms->balance('Cytsms');

        dump($res);
        exit;
    }

    public function voice() {
        $sendSms = new \Common\Org\sendVoices();
        $res = $sendSms->sendVoice('13259976039', 12345);
    }

    public function free() {
        $data['mobile'] = '13259976039';
        $data['content'] = '青团短信123456345435jie';
        $sendSms = new \Common\Org\sendSms();
        $res = $sendSms->checkVerify($data['mobile'], $data['content']);
        dump($res);
    }

    public function testapk() {
        $data['clientver'] = '4.2.1';
        $data['imei'] = '860671020952203';
        $data['mac'] = 'ac:f7:f3:22:e9:af';
        $data['model'] = '2013022';
        $data['platfrom'] = 'android';
        $data['source'] = 'tencent';
        ksort($data);
        $param = '';
        foreach ($data as $key => $value) {
            $param .=$key . '=' . $value . '&';
        }

        $param = substr($param, 0, -1);
        //echo $param;
        $post = 'POST&' . Rawurlencode('http://www.youngt.com') . '&' . Rawurlencode($param);
        // echo  $post;
        $sign = sha1($post);
        echo $sign . '||';
        $miwen = $this->xxtea_encrypt($sign, 'youngtxxx');
        //$miwen = \Think\Crypt\Driver\Xxtea::encrypt($sign,'youngtxxx');
        //echo \Think\Crypt\Driver\Xxtea::decrypt($miwen,'youngtxxx').'sss';

        echo base64_encode($miwen);
        $newsing = Rawurlencode(base64_encode($miwen));
        //echo $newsing;
    }

    function sssxxtea_encrypt($str, $key) {
        if ($str == "") {
            return "";
        }
        echo $str;
        $v = $this->str2long($str, true);
        $k = $this->str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++) {
                $k[$i] = 0;
            }
        }
        dump($v);

        $n = count($v) - 1;

        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = 0;
        echo $q;
        while (0 < $q--) {
            $sum = $this->int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p++) {
                $y = $v[$p + 1];
                $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ $this->int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $z = $v[$p] = $this->int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ $this->int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$n] = $this->int32($v[$n] + $mx);
        }
        dump($v);
        return $this->long2str($v, false);
    }

    function str2long($s, $w) {
        $v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
        $v = array_values($v);
        if ($w) {
            $v[count($v)] = strlen($s);
        }

        return $v;
    }

    function int32($n) {
        while ($n >= 2147483648)
            $n -= 4294967296;
        while ($n <= -2147483649)
            $n += 4294967296;
        return (int) $n;
    }

    function long2str($v, $w) {
        $len = count($v);
        $n = ($len - 1) << 2;
        if ($w) {
            $m = $v[$len - 1];
            if (($m < $n - 3) || ($m > $n))
                return false;
            $n = $m;
        }
        $s = array();
        for ($i = 0; $i < $len; $i++) {
            $s[$i] = pack("V", $v[$i]);
        }
        if ($w) {
            return substr(join('', $s), 0, $n);
        } else {
            return join('', $s);
        }
    }

}
