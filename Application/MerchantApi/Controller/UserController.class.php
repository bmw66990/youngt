<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-20
 * Time: 18:00
 */
namespace MerchantApi\Controller;

/**
 * 商家登陆/检测更新接口
 * Class UserController
 * @package MerchantApi\Controller
 */
class UserController extends CommonController {
    
    protected $checkUser = false;

    /**
     * 商家登陆
     */
    public function login() {
        $this->_checkblank(array('name', 'pwd'));

        $name    = I('post.name');
        $pwd     = I('post.pwd');

        $partner = D('Partner')->login($name, $pwd);
        $this->_writeDBErrorLog($partner, D('Partner'), 'merchantapi');
        if(isset($partner['error'])) {
            $this->outPut('', -1, '', $partner['error']);
        }
        // 生成token
        $uid   = $partner['id'];
        $token = $this->_createToken($uid);

        // 整理返回数据
        $data = array(
            'token' => $token,
        );
        $this->outPut($data, 0, null);
    }

    /**
     * 检查更新
     */
    public function checkUpdate(){
        $config = '';
        $plat   = I('get.plat', '', 'strval');
        if ($plat == 'ios') {
            $config = C('AppUpdateIos');
        } else if ($plat == 'android') {
            $config = C('AppUpdateAndroid');
        } else {
            $this->outPut(null, 1001);
        }
        $verApp    = getFloatVersion(I('get.ver'));
        $verOnline = getFloatVersion($config['ver']);
        if ($verApp < $verOnline) {
            $data = $config;
            $this->outPut($data, 0);
        }
        $this->outPut(null, 5);
    }

    /**
     * 手机验证码
     */
    public function mobileVerify() {

        // 参数接收
        $this->_checkblank(array('accessName', 'action'));
        $accessName = I('post.accessName', '');
        $action = I('post.action', '');        
       
        // 非法参数判断
        $partner = D('Partner');

        $action = strtolower($action);
        if (!$partner->isActionType($action)) {
            $this->outPut(null, 1025);
        }
        if (!checkMobile($accessName)) {
            $this->outPut(null, 1023);
        }

        // 根据用户行为校验
        $userRes = $partner->isRegister(array('mobile' => trim($accessName)));
        switch (trim($action)) {
            case 'reg':
            case 'bindmobile':
            case 'changemobile':
            case 'pcreg':
                if ($userRes) {
                    if ($action == 'reg') {
                        $this->outPut(null, 1033);
                    }
                    $this->outPut(null, 1026);
                }
                break;
            case 'npwd':
            case 'pcnpwd':
                if (!$userRes) {
                    $this->outPut(null, 1027);
                }
                break;
        }

        $sms_day_count = C('SMS_DAY_COUNT');
        $sms_time_out = C('SMS_TIME_OUT');
        $sms_minute_time_out = C('SMS_MINUTE_TIME_OUT');
        $sms_hours_time_out = C('SMS_HOURS_TIME_OUT');
        $sms_hours_count = C('SMS_HOURS_COUNT');
        // 验证是否正常获取
        $sms = M('sms');
        $where = array('mobile' => trim($accessName), 'date' => date('Y-m-d'), 'action' => $action);
        $vCodeRes = $sms->where($where)->find();
        if (isset($vCodeRes['num']) && intval($vCodeRes['num']) >= $sms_day_count) {
            $this->outPut(null, 1028);
        }

        if (isset($vCodeRes['create_time']) && intval(time() - $vCodeRes['create_time']) < $sms_minute_time_out) {
            $this->outPut(null, 1034);
        }

        $client_ip = get_client_ip();
        $client_ip = str_replace('.', '_', $client_ip);
        $sms_key = "sms_{$accessName}_{$action}_{$client_ip}";
        $sms_time_out_data = S($sms_key);
        if (isset($sms_time_out_data['expire_time']) && intval(time() - $sms_time_out_data['expire_time']) < $sms_hours_time_out) {
            if (isset($sms_time_out_data['num']) && $sms_time_out_data['num'] >= $sms_hours_count) {
                $this->outPut(null, 1035);
            }
        }

        if (!isset($sms_time_out_data['num']) || !isset($sms_time_out_data['expire_time']) || intval(time() - $sms_time_out_data['expire_time']) >= $sms_hours_time_out) {
            $sms_time_out_data = array(
                'num' => 1,
                'expire_time' => time(),
            );
        } else {
            $sms_time_out_data['num'] = $sms_time_out_data['num'] + 1;
        }
        S($sms_key, $sms_time_out_data, $sms_hours_time_out);

        // 重新发送验证码
        $code = '';
        if ($vCodeRes) {
            $createTime = $vCodeRes['create_time'];
            $code = $vCodeRes['code'];
            $updateData = array('num' => $vCodeRes['num'] + 1);
            if (time() > $createTime + $sms_time_out) {
                $code = $updateData['code'] = $partner->getCode();
                $updateData['create_time'] = time();
            }
            $sms->where(array('id' => $vCodeRes['id']))->save($updateData);
        } else {
            // 新加手机验证码
            $code = $partner->getCode();
            $data = array(
                'mobile' => trim($accessName),
                'code' => $code,
                'create_time' => time(),
                'action' => $action,
                'date' => date('Y-m-d'),
                'num' => 1,
            );
            $sms->add($data);
        }

        // 判断是否手机端发送验证码
        $clent_var = isset($_SERVER['HTTP_CLIENTVERSION']) && trim($_SERVER['HTTP_CLIENTVERSION'])?$_SERVER['HTTP_CLIENTVERSION']:0;
        $isClientSend = C('IS_CLIENT_SEND');
        if($clent_var && strcmp($clent_var, '4.0.8') > 0){
            $isClientSend = C('IS_CLIENT_SEND_NEW');
        }
        
        if ($isClientSend) {
            $this->outPut(array('is_client_send' => $isClientSend), 0, null);
        }

        // 服务器发送验证码
        $msg = $partner->getSendSmsMsg($action);
        $msg = str_replace('MSMCode', $code, $msg);
        $res = $this->_sms(trim($accessName), $msg);
        if (isset($res['status']) && intval($res['status']) == 0) {
            $this->outPut(array('is_client_send' => $isClientSend), 0, null);
        }
        $this->_writeDBErrorLog($res, $partner, 'api');
        $this->outPut(null, 1029, null, $res['data']);
    }

    /**
     * 找回密码
     */
    public function findPwd() {
        // 参数接收
        $this->_checkblank(array('name', 'password','repassword', 'vCode'));
        $accessName = I('post.name', '','trim');
        $password = I('post.password', '','trim');
        $repassword = I('post.repassword', '','trim');
        $vCode = I('post.vCode', '','trim');

        // 非法参数判断
        if (!checkMobile($accessName)) {
            $this->outPut(null, 1023);
        }

        if($password != $repassword){
            $this->outPut(null, '两次输入的密码不一样，请重新输入！');
        }

        // 校验手机验证码是否正确
        $partner = D('Partner');
        $res = $partner->checkMobileVcode($vCode, $accessName, 'npwd');
       /* if (!$res) {
            $this->_writeDBErrorLog($res, $partner, 'api');
            $this->outPut(null, 1030);
        }*/
        
        $uid = $partner->where(array('mobile' => strval($accessName)))->field('id')->find();
        if(!$uid || !isset($uid['id'])){
            $this->outPut(null, 1031);
        }

        // 修改密码
        $data['password'] = encryptPwd(trim($password));
        $res = $partner->where(array('id' => $uid['id']))->save($data);
        if ($res === false) {
            $this->_writeDBErrorLog($res, $partner, 'api');
            $this->outPut(null, 1031);
        }

        $this->outPut(null, 0, null);
    }

    /**
     * 校验手机验证码是否正确
     */
    public function checkMobileVcode($vCode, $accessName, $action) {
        // 参数接收
        $this->_checkblank(array('accessName', 'action', 'vCode'));
        $accessName = I('post.accessName', '');
        $vCode = I('post.vCode', '');
        $action = I('post.action', '');
        $jy = I('post.jy', '');
        // 非法参数判断
        $partner = D('Partner');

        $action = strtolower($action);
        if (!$partner->isActionType($action)) {
            $this->outPut(null, 1025);
        }
        if (!checkMobile($accessName)) {
            $this->outPut(null, 1023);
        }
        //2016.3.12 号加新版短信校验
        if($jy){
            $jy=strtolower($jy);
            $res = $partner->checkMobileVcode($vCode, $accessName, $action,$jy);
        }else{
            $res = $partner->checkMobileVcode($vCode, $accessName, $action);
        }
        //end
        if (!$res) {
            $this->_writeDBErrorLog($res, $partner, 'api');
            $this->outPut(null, 1030);
        }

        $this->outPut(null, 0, null);
    }

    /**
     * 修改密码
     */
    public function modifyPwd($oldpassword, $password, $repassword) {
        $this->check();
        $this->_checkblank(array('oldpassword', 'password','repassword'));
        $partner = D('Partner');
        $oldpassword = I('post.oldpassword', '','trim');
        $password = I('post.password', '','trim');
        $repassword = I('post.repassword', '','trim');

        if (!$partner->checkPwd($this->uid, $oldpassword)) {            
            $this->outPut('', -1, '', '密码错误，请确认！');
        }

        if($password != $repassword){
            $this->outPut('', -1, '', '两次输入的密码不一样，请重新输入！');
        }

        $data['password'] = encryptPwd(trim($password));
        $res = $partner->where(array('id' => $this->uid))->save($data);        
        if ($res === false) {
            $this->_writeDBErrorLog($res, $partner, 'api');
            $this->outPut(null, 1031);
        }
        $this->outPut('修改成功',0);        
    }

}