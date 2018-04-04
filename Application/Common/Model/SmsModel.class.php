<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/29
 * Time: 12:03
 */

namespace Common\Model;

/**
 * 短信验证码模型
 * Class SmsModel
 * @package Common\Model
 */
class SmsModel extends CommonModel {

    /**
     * @param $mobile 手机号码
     * @param $action 短信类型
     * @param $code   验证码
     */
    public function sendSms($mobile, $action, &$code) {
        $where = array('mobile' => trim($mobile), 'date' => date('Y-m-d'), 'action' => $action);
        $vCodeRes = $this->where($where)->find();
        if (isset($vCodeRes['num']) && intval($vCodeRes['num']) >= C('SMS_DAY_COUNT')) {
            return array('status' => -1, 'error' => '获取验证码次数过多');
        }

        $sms_minute_time_out = C('SMS_MINUTE_TIME_OUT');
        $sms_hours_time_out = C('SMS_HOURS_TIME_OUT');
        $sms_hours_count = C('SMS_HOURS_COUNT');

        if (isset($vCodeRes['create_time']) && intval(time() - $vCodeRes['create_time']) < $sms_minute_time_out) {
            return array('status' => -1, 'error' => '请一分钟之后再获取验证码');
        }

        $client_ip = get_client_ip();
        $client_ip = str_replace('.', '_', $client_ip);
        $sms_key = "sms_{$mobile}_{$action}_{$client_ip}";
        $sms_time_out_data = S($sms_key);
       
        if (isset($sms_time_out_data['expire_time']) && intval(time() - $sms_time_out_data['expire_time']) < $sms_hours_time_out) {
            if (isset($sms_time_out_data['num']) && $sms_time_out_data['num'] >= $sms_hours_count) {
                return array('status' => -1, 'error' => '每小时只能获取两次验证码');
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
        if ($vCodeRes) {
            $createTime = $vCodeRes['create_time'];
            $updateData = array('num' => $vCodeRes['num'] + 1);
            if (time() > ($createTime + C('SMS_TIME_OUT'))) {
                $updateData['code'] = $code;
            } else {
                $code = $vCodeRes['code'];
            }
            $updateData['create_time'] = time();
            $res = $this->where(array('id' => $vCodeRes['id']))->save($updateData);
        } else {
            $data = array(
                'mobile' => trim($mobile),
                'code' => $code,
                'create_time' => time(),
                'action' => $action,
                'date' => date('Y-m-d'),
                'num' => 1,
            );
            $res = $this->add($data);
        }
        if ($res) {
            return array('status' => 1);
        } else {
            return array('status' => -1, 'error' => '验证码请请求失败');
        }
    }

    /**
     * @param $phone
     * @param $vcode
     * pc wap 用第三方短信Mon校验
     */
    public function  checkMobileVcode($mobile,$vcode,$action){
        // 非法参数判断
        if (!checkMobile($mobile)) {
            return false;
        }
        $sms = M('sms');
        $type=C('SMSTYPE');
        if($type=='MonSend'){
            $smsSend = new \Common\Org\sendSms();
            $smsRes = $smsSend->checkMon($mobile, $vcode);
            if (isset($smsRes['status']) && intval(trim($smsRes['status'])) == 1) {
                // 修改数据库校验码
                $where=array('mobile' => $mobile, 'action' => $action, 'date' => date('Y-m-d'));
                $sms->where($where)->save(array('code' => trim($vcode), 'create_time' => time()));
                return true;
            }else{
                return false;
            }
        }else{
            $where = array('code' => $vcode, 'mobile' => $mobile, 'action' => $action, 'date' => date('Y-m-d'));
            $res = $sms->where($where)->find();
            if ($res) {
                return true;
            } else {
                return false;
            }
        }


    }
}
