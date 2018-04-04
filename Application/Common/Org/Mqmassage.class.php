<?php

/**
 * Created by PhpStorm.
 * User: daipingshan
 * Date: 2015/4/22
 * Time: 11:19
 */

namespace Common\Org;

/**
 * 发送短息接口
 * Class sendSms
 * @package Common\Org
 */
class Mqmassage {

    /**
     * @var string 亿美序列号
     */
    private $YmUsername = '6SDK-EMY-6688-KGXTS';

    /**
     * @var string  亿美短信接口密码
     */
    private $YmPwd = '741044';

    /**
     * @var string  亿美短信接口url
     */
    private $YmUrl = 'http://sdk4report.eucp.b2m.cn:8080/sdkproxy/sendsms.action';

    /**
     * @var string  亿美获取余额接口url
     */
    private $YmMoneyUrl = 'http://sdk4report.eucp.b2m.cn:8080/sdkproxy/querybalance.action';

    /**
     * @var string  诚意通短信接口用户名
     */
    private $CytUsername = 'yllw';

    /**
     * @var string  诚意通短信接口密码
     */
    private $CytPwd = 'superego@123';

    /**
     * @var string  诚意通短信接口url
     */
    private $CytUrl = 'http://yl.mobsms.net/send/gsend.aspx';

    /**
     * @var string  诚意通获取余额接口url
     */
    private $CytMoneyUrl = 'http://yl.mobsms.net/send/getfee.aspx';

    /**
     * @var string  诚意通短信接口用户名
     */
    private $JcUsername = 'qtw';

    /**
     * @var string  君成短信接口密码
     */
    private $JcPwd = '17c6e912734720ac744ab69112d4e79a';

    /**
     * @var string  君成短信接口url
     */
    private $JcUrl = 'http://www.jc-chn.cn/smsSend.do';

    /**
     * @var string  君成获取余额接口url
     */
    private $JcMoneyUrl = 'http://www.jc-chn.cn/balanceQuery.do';

    /**
     * @var string  免费短信appkey
     */
    private $appkey = '4a4c9b1aa26b';
    private $appkey2 = '10562f4441a94';
    private $appkey3 = '1056ebd379780';
    private $appkey4 = '1069af62df4d3';
    /**
     * @var string 免费短信验证接口url
     */
   // private $freeUrl = 'https://api.sms.mob.com/sms/verify';
    private $freeUrl = 'https://web.sms.mob.com/sms/verify';
    private $freeUrl2 = 'https://webapi.sms.mob.com/sms/verify';
    private $freeUrl3 = 'https://webapi.sms.mob.com/sms/sendmsg';//发送验证码
    private $freeUrl4 = 'https://webapi.sms.mob.com/sms/checkcode';//校验验证码
    /**`
     * @var string  发送短信电话号
     */
    private $phone = '';

    /**
     * @var string  发送内容
     */
    private $content = '';

    /**
     * @var string  发送时间
     */
    private $time = '';

    /**
     * @var string  输入内容
     */
    private $output = '';
    private $errInfo = array(
        '-1'    => '系统异常',
        '-2'    => '必填选项为空',
        '-3'    => '短信内容0个字节',
        '-4'    => '0个有效号码',
        '-5'    => '余额不够',
        '-6'    => '含有一级敏感词',
        '-7'    => '含有二级敏感词，人工审核',
        '-8'    => '提交频率太快，退避重发',
        '-9'    => '数据格式错误',
        '-10'   => '用户被禁用',
        '-11'   => '短信内容过长',
        '-12'   => '失败',
        '-13'   => '用户名或者密码不正确',
        '-101'  => '命令不被支持',
        '-102'  => 'RegistryTransInfo删除信息失败',
        '-103'  => 'RegistryInfo更新信息失败',
        '-104'  => '请求超过限制',
        '-110'  => '号码注册激活失败',
        '-111'  => '企业注册失败',
        '-113'  => '充值失败',
        '-117'  => '发送短信失败',
        '-118'  => '接收MO失败',
        '-119'  => '接收Report失败',
        '-120'  => '修改密码失败',
        '-122'  => '号码注销激活失败',
        '-123'  => '查询单价失败',
        '-124'  => '查询余额失败',
        '-125'  => '设置MO转发失败',
        '-126'  => '路由信息失败',
        '-127'  => '计费失败0余额',
        '-128'  => '计费失败余额不足',
        '-190'  => '数据操作失败',
        '-1102' => '序列号密码错误',
        '-1103' => '序列号Key错误',
        '-1104' => '路由失败，请联系系统管理员',
        '-1105' => '注册号状态异常, 未用 1',
        '-1107' => '注册号状态异常, 停用 3',
        '-1108' => '注册号状态异常, 停止 5',
        '-1110' => '序列号错误,序列号不存在内存中,或尝试攻击的用户',
        '-1131' => '充值卡无效',
        '-1132' => '充值密码无效',
        '-1133' => '充值卡绑定异常',
        '-1134' => '充值状态无效',
        '-1135' => '充值金额无效',
        '-1901' => '数据库插入操作失败',
        '-1902' => '数据库更新操作失败',
        '-1903' => '数据库删除操作失败',
        '512'   => '服务器拒绝访问，或者拒绝操作',
        '513'   => '求Appkey不存在或被禁用',
        '514'   => '权限不足',
        '515'   => '服务器内部错误',
        '517'   => '缺少必要的请求参数',
        '518'   => '请求中用户的手机号格式不正确（包括手机的区号）',
        '519'   => '请求发送验证码次数超出限制',
        '520'   => '无效验证码',
        '526'   => '余额不足',
        
        '405'   => 'AppKey为空',
        '406'   => 'AppKey无效',
        '456'   => '国家代码或手机号码为空',
        '457'   => '手机号码格式错误',
        '466'   => '请求校验的验证码为空',
        '467'   => '请求校验验证码频繁（5分钟内同一个appkey的同一个号码最多只能校验三次）',
        '468'   => '验证码错误',
        '474'   => '没有打开服务端验证开关',
        
        '6002'  => '用户账号不正确',
        '6008'  => '无效的手机号码',
        '6009'  => '手机号码是黑名单',
        '6010'  => '用户密码不正确',
        '6011'  => '短信内容超过了最大长度限制',
        '6012'  => '该企业用户设置了ip限制',
        '6013'  => '该企业用户余额不足',
        '6014'  => '发送短信内容不能为空',
        '6015'  => '发送内容中含非法字符',
        '6019'  => '账户已停机，请联系客服',
        '6021'  => '扩展号码未备案',
        '6023'  => '发送手机号码超过太长，已超过300个号码',
        '6024'  => '定制时间不正确',
        '6025'  => '扩展号码太长（总长度超过20位）',
        '6080'  => '提交异常，请联系服务商解决',
        '6085'  => '短信内容为空',
    );

    /**
     * 构造函数
     */
    public function __construct() {
        $this->handle = curl_init();
        $header       = array(
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            'Accept: application/json',
        );
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_HEADER, 0);
        curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->handle, CURLOPT_TIMEOUT, 30);
    }

    /**
     * @param $url  设置提交url
     */
    private function setUrl($url) {
        curl_setopt($this->handle, CURLOPT_URL, $url);
    }

    /**
     * 设置curl参数
     */
    private function setOptions($flag, $value) {
        curl_setopt($this->handle, $flag, $value);
    }


    /**
     * @param $method  执行的方法
     * @param $data    发送的数据
     * @return string
     */
    private function op_curl($method, $data = "", $type) {
        $url = '';

        $this->setUrl($url);
        if ($method == 'sendMsg' && $type != 'Cytsms') {
            $this->setData($data, $type);
        }
        $this->exec_curl();
        $this->colse();
        return $this->returnResult($method, $type);
    }

    /**
     * 打开句柄
     */
    private function exec_curl() {
        $this->output = curl_exec($this->handle);
    }

    /**
     * 关闭句柄
     */
    private function colse() {
        curl_close($this->handle);
    }

    /**
     * 错误信息展示
     * @param $key
     * @return mixed
     */
    private function errorInfo($key) {
        return $this->errInfo[$key];
    }

    /**
     * 日志输出
     */
    private function logOut($flag) {
        $destination = C('LOG_PATH') . 'sms/' . date('y_m_d') . '.log';
        if (!is_dir(C('LOG_PATH') . 'sms')) {
            @mkdir(C('LOG_PATH') . 'sms');
        }
        \Think\Log::write("$flag:$this->time-$this->phone-$this->content-$this->output", \Think\Log::INFO, '', $destination);
    }

    /**
     * 返回错误信息
     * @param $flag   操作标记
     * @return string  返回结果
     */
    private function returnResult($flag = "sendMsg", $type) {
        if ($flag == 'sendMsg') {
            $this->parsingRes($type);
            if (array_key_exists($this->output, $this->errInfo)) {
                $this->logOut($flag);
                return array('status' => -1, 'data' => $this->errorInfo($this->output));
            } else {
                return array('status' => 1, 'data' => '发送成功');
            }
        } elseif ($flag == 'balance') {
            return array('status' => 1, 'data' => $this->output);
        } elseif ($flag == 'verify') {
            $this->output = @json_decode($this->output, true);
            if (!isset($this->output['status']) || array_key_exists($this->output['status'], $this->errInfo)) {
                $this->logOut($flag);
                return array('status' => -1, 'data' => $this->errorInfo($this->output['status']));
            } else {
                return array('status' => 1, 'data' => '验证成功');
            }
        }elseif ($flag == 'verify2') {
            $this->output = @json_decode($this->output, true);
           // file_put_contents('/tmp/sms_ver.log',var_export($this->output,true),FILE_APPEND);//

            if (!isset($this->output['status']) || array_key_exists($this->output['status'], $this->errInfo)) {
                $this->logOut($flag);
                return array('status' => -1, 'data' => $this->errorInfo($this->output['status']));
            } else {
                return array('status' => 1, 'data' => '验证成功');
            }
        }elseif ($flag == 'MonSend') {
            $this->output = @json_decode($this->output, true);
            if (!isset($this->output['status']) || array_key_exists($this->output['status'], $this->errInfo)) {
                $this->logOut($flag);
                return array('status' => -1, 'data' => $this->errorInfo($this->output['status']));
            } else {
                return array('status' => 1, 'data' => '发送成功');
            }
        }elseif ($flag == 'checkmon') {
            $this->output = @json_decode($this->output, true);
            file_put_contents('/tmp/sms_paycallback.log',var_export($this->output,true),FILE_APPEND);//

            if (!isset($this->output['status']) || array_key_exists($this->output['status'], $this->errInfo)) {
                $this->logOut($flag);
                return array('status' => -1, 'data' => $this->errorInfo($this->output['status']));
            } else {
                return array('status' => 1, 'data' => '验证成功');
            }
        }
    }
    /**
     * 解析返回结果
     */
    private function parsingRes($type) {
        $arr = array();
        $res = $this->output;
        switch ($type) {
            case 'Ymsms':
                $this->output = $res;
                break;
            case 'Jcsms':
                if ($res == 0)
                    $res = -12;
                if ($res == -1)
                    $res = -13;
                $this->output = $res;
                break;
            case 'Cytsms':
                $arr = explode('&', $res);
                $tmp = array_pop($arr);
                list($error_num, $err_info) = explode('=', $tmp);
                $this->output = $err_info;
                break;
        }
    }

    /**
     * @param $phone
     * @param $code
     * @return string
     * Mon验证校验
     */
    public function checkMon($phone, $code) {
        $appkey=$this->appkey2;
        $data = array(
            'appkey' => $appkey,
            'phone'  => $phone,
            'zone'   => '86',
            'code'   => $code,
        );
        return $this->op_curl('checkmon', $data, '');
    }
}
