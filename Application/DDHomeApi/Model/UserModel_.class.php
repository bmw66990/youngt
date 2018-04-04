<?php

/**
 * User 用户表模型
 * Created by JetBrains PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 15-3-18
 * Time: 下午15:39
 * To change this template use File | Settings | File Templates.
 */

namespace Api\Model;

use \Think\Model;

class UserModel extends Model {
    
    // 获取验证码 行为类型
    private $actionType = array('reg', 'buy', 'npwd', 'pcreg', 'pcbuy', 'pcnpwd');
    
    // 发送短信内容
    private $msgType = array(
        'npwd' => 'MSMCode（找回登录密码验证码），工作人员不会向您索要，请勿泄露他人使用',
        'reg_success' => '恭喜您成为青团用户，您的用户名是：MOBILEPHONE，密码是: PASSWORD,请勿转告他人，建议浏览完立即删除！',
        'other' => '您的手机验证码为:MSMCode  请勿泄露他人使用'
    );
    
    // 登录类型
    private $loginType = array('weixin', 'qq', 'default');
    
    // 验证码取值范围
    const V_CODE_MIN = 10000;
    const V_CODE_MAX = 99999;
    
    // 新注册用户所送分值
    const REG_SCORE = 20;
    
    // 验证码有效期 秒
    const SMS_TIME_OUT = 600;
    
    // 每天获取验证码次数
    const SMS_DAY_COUNT = 5;

    /**
     * 客户端登陆
     * @param string $accessName 用户名
     * @param string $password 密码
     * @return type
     */
    public function login($accessName = '', $password = '',$loginType,$code='') {

        // 非法参数判断
        if (!trim($loginType)) {
            return array('error' => '登陆方式不能为空！');
        }
        $loginType = strtolower($loginType);
        if (!in_array($loginType, $this->loginType)) {
            return array('error' => '登陆方式错误！');
        }

        // 登陆判断
        $res = array('error' => '');
        switch ($loginType) {
            case 'weixin':
                $res = $this->__weixinLogin($code);
                break;
            case 'qq':

                break;
            case 'default':
            default:
                $res = $this->__defaultLogin($accessName, $password);
                break;
        }

        return $res;
    }

    /**
     * 默认登陆类型
     * @param type $accessName
     * @param type $password
     */
    private function __defaultLogin($accessName = '', $password = '') {
        // 非法参数判断
        if (!trim($accessName)) {
            return array('error' => '用户名不能为空！');
        }
        if (!trim($password)) {
            return array('error' => '密码不能为空！');
        }

        $where = array(
            'mobile|username|email' => trim($accessName),
            'password' => encryptPwd(trim($password)),
        );
        $user = M('user');
        $res = $user->where($where)->find();
        if (!$res) {
            return array('error' => '登陆校验失败！');
        }

        // 更新最后登录时间
        $uid = $res['id'];
        $user->where("id=$uid")->save(array('login_time' => time()));

        return array('message' => '校验成功！', 'data' => $res);
    }

    /**
     * 微信登陆
     * @param type $code
     * @return type
     */
    private function __weixinLogin($code = '') {

        // 非法参数判断
        if (!trim($code)) {
            return array('error' => '微信授权码不能为空！');
        }

        // 获取
        $weixin = new \Common\Org\WeiXin();
        $weixinRes = $weixin->getWeiXinUserInfo($code);
        if (isset($weixinRes['error'])) {
            return $weixinRes;
        }
        $unid = $weixinRes['unionid'];
        $user = M('user');
        $userRes = $user->where(array('unid' => $unid))->find();
        if ($userRes) {
            $uid = $userRes['id'];
            $user->where("id=$uid")->save(array('login_time' => time()));
            return array('message' => '校验成功！', 'data' => $weixinRes);
        }

        // 向用户表中添加数据
        $name = $weixinRes['nickname'];
        if (!trim($name)) {
            $name = 'weixin';
        }
        $name = $this->__getUserName($name);
        $password = '123456';
        $data = array(
            'username' => $name,
            'password' => encryptPwd($password),
            'create_time' => time(),
            'score' => self::REG_SCORE,
            'login_time' => time(),
            'wxtoken' => $weixinRes['openid'],
            'unid' => $unid,
        );
        $uid = $user->add($data);

        // 添加积分流水
        $scoredata = array(
            'create_time' => time(),
            'user_id' => $uid,
            'score' => self::REG_SCORE,
            'action' => 'register',
            'sumscore' => self::REG_SCORE
        );
        M('credit')->add($scoredata);

        // 向微信用户表中加入数据
        $userWeixin = M('weixin_user');
        $res = $userWeixin->where(array('openid' => $weixinRes['openid']))->find();
        if (!$res) {
            $data = array(
                'username' => $uid,
                'openid' => $weixinRes['openfid'],
                'nickname' => $weixinRes['nickname'],
                'sex' => $weixinRes['sex'],
                'language' => $weixinRes['language'],
                'city' => $weixinRes['city'],
                'province' => $weixinRes['province'],
                'country' => $weixinRes['country'],
                'headimgurl' => $weixinRes['headimgurl'],
            );
            $userWeixin->add($data);
        }

        return array('message' => '登录成功！', 'data' => array('id' => $uid));
    }

    /**
     * 手机号码注册
     * @param type $accessName
     * @param type $password
     * @param type $vCode
     * @return type
     */
    public function register($accessName = '', $password = '', $vCode = '') {

        // 非法参数判断
        if (!trim($accessName)) {
            return array('error' => '用户名不能为空！');
        }
        if (!trim($password)) {
            return array('error' => '密码不能为空！');
        }
        if (!trim($vCode)) {
            return array('error' => '验证码不能为空！');
        }

        if (!checkMobile($accessName)) {
            return array('error' => '非法手机号码！');
        }

        // 根据手机号 获取验证 并校验该验证码是否正确
        $where = array('mobile' => trim($accessName), 'date' => date('Y-m-d'), 'action' => 'reg');
        $res = M('sms')->where($where)->find();
        if (!$res) {
            return array('error' => '验证码校验失败！请重新获取校验码！');
        }
        $reg_time = $res['create_time'];

        // 验证码10分钟失效
        if (time() > $reg_time + self::SMS_TIME_OUT) {
            return array('error' => '验证码已经失效！请重新获取校验码！');
        }
        if (trim($vCode) != trim($res['code'])) {
            return array('error' => '验证码错误！请重新获取校验码！');
        }

        // 添加注册信息
        $data = array(
            'password' => encryptPwd(trim($password)),
            'score' => self::REG_SCORE,
            'mobile' => trim($accessName),
            'create_time' => time(),
            'username' => trim($accessName),
        );
        $user = M('user');
        $uid = $user->add($data);
        if (!$uid) {
            return array('error' => '注册失败！');
        }

        // 添加积分流水
        $scoredata = array(
            'create_time' => time(),
            'user_id' => $uid,
            'score' => self::REG_SCORE,
            'action' => 'register',
            'sumscore' => self::REG_SCORE
        );
        M('credit')->add($scoredata);

        return array('message' => '注册成功！');
    }

    /**
     * 找回密码
     * @param type $accessName
     * @param type $password
     * @param type $vCode
     * @return type
     */
    public function findPwd($accessName = '', $password = '', $vCode) {

        // 非法参数判断
        if (!trim($accessName)) {
            return array('error' => '用户名不能为空！');
        }
        if (!trim($password)) {
            return array('error' => '密码不能为空！');
        }
        if (!trim($vCode)) {
            return array('error' => '验证码不能为空！');
        }
        if (!checkMobile($accessName)) {
            return array('error' => '非法手机号码！');
        }

        // 根据手机号 获取验证 并校验该验证码是否正确
        $where = array('mobile' => trim($accessName), 'date' => date('Y-m-d'), 'action' => 'npwd');
        $res = M('sms')->where($where)->find();
        if (!$res) {
            return array('error' => '验证码校验失败！请重新获取校验码！');
        }
        $reg_time = $res['create_time'];

        // 验证码10分钟失效
        if (time() > $reg_time + self::SMS_TIME_OUT) {
            return array('error' => '验证码已经失效！请重新获取校验码！');
        }
        if (trim($vCode) != trim($res['code'])) {
            return array('error' => '验证码错误！请重新获取校验码！');
        }

        // 修改密码
        $user = M('user');
        $data['password'] = encryptPwd(trim($password));
        $res = $user->where('mobile=' . trim($accessName))->save($data);
        if (!$res) {
            return array('error' => '密码修改失败！');
        }

        return array('message' => '密码修改成功！');
    }

    /**
     * 手机验证码获取
     * @param type $accessNumber
     * @param type $action
     */
    public function mobileVerify($accessName = '', $action = '') {
        // 非法参数判断
        if (!trim($accessName)) {
            return array('error' => '用户名不能为空！');
        }
        if (!trim($action)) {
            return array('error' => '用户行为不能为空！');
        }
        $action = strtolower($action);
        if (!in_array($action, $this->actionType)) {
            return array('非法用户行为！');
        }
        if (!checkMobile($accessName)) {
            return array('error' => '非法手机号码！');
        }

        // 根据用户行为校验
        $user = M('user');
        $userCount = $user->where(array('mobile' => trim($accessName)))->count();
        switch (trim($action)) {
            case 'reg':
            case 'pcreg':
                if ($userCount && $userCount > 0) {
                    return array('error' => '该手机号已经绑定！不能重复绑定！');
                }
                break;
            case 'npwd':
            case 'pcnpwd':
                if (!$userCount && $userCount <= 0) {
                    return array('error' => '该用户不存在，不能修改密码！');
                }
                break;
        }

        // 获取发送短信内容  
        $sendSms = new \Common\Org\sendSms();
        $msg = '【青团网】' . $this->msgType['other'];
        if (isset($this->msgType[$action])) {
            $msg = '【青团网】' . $this->msgType[$action];
        }

        // 验证是否正常获取
        $sms = M('sms');
        $where = array('mobile' => trim($accessName), 'date' => date('Y-m-d'), 'action' => $action);
        $vCodeRes = $sms->where($where)->find();
        
        if (isset($vCodeRes['num']) && intval($vCodeRes['num']) >= self::SMS_DAY_COUNT) {
            return array('error' => '获取验证码，你获取验证码次数过多！'.$vCodeRes['num']);
        }

        // 重新发送验证码
        $code = '';
        if ($vCodeRes) {
            $createTime = $vCodeRes['create_time'];
            $code = $vCodeRes['code'];
            $updateData = array('num' => $vCodeRes['num'] + 1);
            if (time() > $createTime + self::SMS_TIME_OUT) {
                $code = $updateData['code'] = $this->__getCode();
                $updateData['create_time'] = time();
            }
            $msg = str_replace('MSMCode', $code, $msg);
            $res = $sendSms->sendMsg(trim($accessName), $msg);
            if (isset($res['error']) && $res['error'] == 0) {
                $sms->where(array('id' => $vCodeRes['id']))->save($updateData);
                return array('message' => '发送成功！');
            }
            return array('error' => '发送失败！' . $res['data']);
        }

        $code = $this->__getCode();
        $data = array(
            'mobile' => trim($accessName),
            'code' => $code,
            'create_time' => time(),
            'action' => $action,
            'date' => date('Y-m-d'),
            'num' => 1,
        );
        
        $msg = str_replace('MSMCode', $code, $msg);
        $res = $sendSms->sendMsg(trim($accessName), $msg);
        if (isset($res['error']) && $res['error'] == 0) {
            $sms->add($data);
            return array('message' => '发送成功！');
        }
        return array('error' => '发送失败！' . $res['data']);
    }

    /**
     * 获取验证码
     * @return type
     */
    private function __getCode() {
        return rand(self::V_CODE_MIN, self::V_CODE_MAX);
    }

    /**
     * 获取微信 登录时，username
     * @param type $name username前缀
     * @return string
     */
    private function __getUserName($name) {

        $user = M('user');
        while (true) {
            $username = $name . '_' . (time() + $this->__getCode());
            $res = $user->where(array('username' => $username))->find();
            if (!$res) {
                return $username;
            }
        }
        return time();
    }

}
