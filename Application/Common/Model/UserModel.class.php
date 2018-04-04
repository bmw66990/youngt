<?php

/**
 * User 用户表模型
 * Created by JetBrains PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 15-3-18
 * Time: 下午15:39
 * To change this template use File | Settings | File Templates.
 */

namespace Common\Model;

use Common\Model\CommonModel;

class UserModel extends CommonModel {

    // 新注册用户所送分值
    const REG_SCORE = 20;

    // 获取验证码 行为类型
    private $actionType = array(
        'reg',  // 注册
        'buy',  // 购买
        'npwd', // 找回密码
        'pcreg', // pc 注册
        'pcbuy', // pc 购买
        'pcnpwd', // pc 找回密码
        'pcbindmobile', // pc绑定手机号
        'pcchangemobile', // pc 修改手机号
        'bindmobile', // 绑定手机号码
        'changemobile', // 修改手机号
        'login', // 登录
        'receive_prize', // 一元众筹 获取验证码
        'other' // 其他
    );
    // 登录类型
    private $loginType = array(
        'weixin',
        'qzone',
        'quicklogin',
        'default',
        'qqbindlogin'
    );

    /*
     *  获取多条数据详细信息
     *  @param array $where  : 获取数据信息的条件
     *  @param string $field : 获取数据信息字段名
     *
     *  return array $list   : 返回数据信息
     */

    public function getUsers($where, $limit, $orderby = 'id DESC', $field = '*') {
        $data = $this->alias('u')
                ->field($field)
                ->join('left join category c ON u.city_id = c.id')
                ->where($where)
                ->order($orderby)
                ->limit($limit)
                ->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

    /**
     * 用户注册统计
     * @param $city_id 城市id
     * @param $stime 开始时间
     * @param $etime 结束时间
     * @param string $type
     * @return array
     */
    public function regUserCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['create_time'][] = array('egt', $stime);
        $where['create_time'][] = array('lt', $etime);
        $data = $this->field('LEFT(FROM_UNIXTIME(create_time), ' . $len . ') ct,count(*) num')->where($where)->group('ct')->order('null')->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        $list = array();
        foreach ($data as $row) {
            $list['reguser'][$row['ct']] = $row['num'];
        }
        return $list;
    }

    /**
     * 用户注册统计 (new)
     * @param $city_id
     * @param $stime
     * @param $etime
     * @return array
     */
    public function newRegUserCount($city_id, $stime, $etime, $type = 'week') {
        /*
          if (!empty($city_id)) {
          $where['`order`.city_id'] = $city_id;
          }
          $where['`order`.create_time'][] = array('egt', $stime);
          $where['`order`.create_time'][] = array('lt', $etime);
          $where['u.create_time'] = array('between', array($stime, $etime));
          if($type == 'daytotal') {
          $data = $this->alias('u')->join('`order` on `order`.user_id=u.id')->where($where)->count();
          return $data;
          } else {
          $where['`order`.create_time - u.create_time'][] = array('elt', 24 * 3600);
          $where['`order`.create_time - u.create_time'][] = array('egt', 0);
          $data = $this->alias('u')->join('`order` on `order`.user_id=u.id')->field('LEFT(FROM_UNIXTIME(`order`.create_time), 10) ct,count(`order`.id) num')->where($where)->group('ct')->order('null')->select();
          } */

        if (!empty($city_id)) {
            $where['o.city_id'] = $city_id;
        }
        $where['u.create_time'] = array('BETWEEN', array($stime, $etime));
        $sub = $this->alias('u')
                ->field('u.id,u.`create_time`,MIN(o.create_time) mct')
                ->join('`order` o on o.user_id=u.id')
                ->where($where)
                ->group('u.id')
                ->order('NULL')
                ->buildSql();
        if ($type == 'daytotal') {
//            $data = $this->field('(t.mct-t.create_time) mt')
//                ->table($sub . 't')
//                ->having('mt BETWEEN 0 AND 86400')
//                ->select();
//            return count($data);

            $data = $this->field('sum((t.mct-t.create_time) < 86400) num')->table($sub . 't')->find();
            return ternary($data['num'], 0);
        } else {
            $data = $this->field('LEFT(FROM_UNIXTIME(t.create_time), 10) ct,count(t.id) num,(t.mct-t.create_time) mt')
                    ->table($sub . 't')
                    ->group('ct')
                    ->having('mt BETWEEN 0 AND 86400')
                    ->order('NULL')
                    ->select();
        }

        //echo $data;
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        $list = array();
        foreach ($data as $row) {
            $list['reguser'][$row['ct']] = $row['num'];
        }
        return $list;
    }

    /**
     * 判断用户是否存在
     * @param $uid 用户id
     *
     * @return bool
     */
    public function isExist($uid) {
        $mapping = array(
            'id' => $uid
        );

        if ($this->getTotal($mapping) == 0) {
            return false;
        }
        return true;
    }

    /**
     * 判断是否签到
     * @param $uid
     */
    public function isDaySign($uid) {
        $map = array(
            'user_id' => $uid,
            'create_time' => strtotime(date('Y-m-d')),
        );
        if (M('Daysign')->where($map)->count() == 0) {
            return false;
        }
        return true;
    }

    /**
     * 签到
     * @param $uid
     * @return bool
     */
    public function daySign($uid) {
        $score = C('DAYSIGN_SCORE');
        $money = C('DAYSIGN_MONEY');
        $uid = intval($uid);
        $date = strtotime(date('Y-m-d'));
        
        $model = M();
        $model->startTrans();
        //Daysign
        $signData = array(
            'user_id' => $uid,
            'credit' => $score,
            'money' => $money,
            'create_time' => $date,
        );
        if (M('Daysign')->add($signData) === false) {
            $this->errorInfo['info'] = M('Daysign')->getDbError();
            $this->errorInfo['sql'] = M('Daysign')->_sql();
            $model->rollback();
            return false;
        }

        //User
        $user = $this->info($uid, 'id,score,money');
        $userData = array(
            'score' => $user['score'] + $score,
            'money' => $user['money'] + $money,
        );
        if ($this->where('id=' . $uid)->save($userData) === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            $model->rollback();
            return false;
        }

        //Flow
        $flowData = array(
            'user_id' => $uid,
            'money' => $money,
            'direction' => 'income',
            'action' => 'daysign',
            'create_time' => $date,
        );
        if (M('Flow')->add($flowData) === false) {
            $this->errorInfo['info'] = M('Flow')->getDbError();
            $this->errorInfo['sql'] = M('Flow')->_sql();
            $model->rollback();
            return false;
        }

        //Credit
        $creditData = array(
            'create_time' => time(),
            'user_id' => $uid,
            'score' => $score,
            'action' => 'daysign',
            'sumscore' => $user['score'] + 1
        );
        if (M('Credit')->add($creditData) === false) {
            $this->errorInfo['info'] = M('Credit')->getDbError();
            $this->errorInfo['sql'] = M('Credit')->_sql();
            $model->rollback();
            return false;
        }

        $model->commit();
        return true;
    }

    /**
     * 申请提现
     * @param $uid
     * @param $data
     * @return bool
     */
    public function applyCash($uid, $data) {
        // 用户信息
        $user = D('User');
        $model = M();
        $model->startTrans();
        $userRes = $user->field('id,money')->find($uid);
        $money = sprintf("%.2f", $userRes['money'] - $data['money']);
        if ($money < 0) {
            return false;
        }
        if ($user->where(array('id' => $uid))->save(array('money' => $money)) === false) {
            $this->errorInfo['info'] = $user->getDbError();
            $this->errorInfo['sql'] = $user->_sql();
            $model->rollback();
            return false;
        }

        $flowData = array(
            'user_id' => $uid,
            'money' => $data['money'],
            'direction' => 'expense',
            'action' => 'withdraw',
            'detail' => "id号为{$uid}的用户提现{$data['money']}元，用户余额{$userRes['money']}元",
            'create_time' => time(),
            'team_id' => '',
            'partner_id' => '',
            'marks' => ''
        );
        if (M('Flow')->add($flowData) === false) {
            $this->errorInfo['info'] = M('Flow')->getDbError();
            $this->errorInfo['sql'] = M('Flow')->_sql();
            $model->rollback();
            return false;
        }

        $userData = array(
            'bank' => $data['bank'],
            'account' => $data['account'],
            'name' => $data['uname'],
            'user_id' => $uid,
            'money' => $data['money'],
            'state' => 'N',
            'time' => time(),
        );
        if (M('UserPay')->add($userData) === false) {
            $this->errorInfo['info'] = M('UserPay')->getDbError();
            $this->errorInfo['sql'] = M('UserPay')->_sql();
            $model->rollback();
            return false;
        }

        $model->commit();
        return true;
    }

    /**
     * 检查用户名是否存在
     * @param $username
     * @param $uid
     * @return bool
     */
    public function checkAccount($username, $uid = '') {
        $map['username|mobile'] = $username;
        if (!empty($uid))
            $map['id'] = array('NEQ', $uid);
        if ($this->where($map)->count() == 0) {
            return false;
        }
        return true;
    }

    /**
     * 检查用户密码是否正确
     * @param $uid
     * @param $pwd
     * @return bool
     */
    public function checkPwd($uid, $pwd) {
        $user = $this->info($uid, 'id,password');
        if (strcmp(encryptPwd($pwd), $user['password']) === 0) {
            return true;
        }
        return false;
    }

    /**
     * 默认登陆类型
     * @param type $accessName
     * @param type $password
     */
    public function defaultLogin($accessName = '', $password = '') {

        $where = array(
            'mobile|username|email' => trim($accessName),
        );
        $userRes = $this->where($where)->field('id,password')->find();
        if (!$userRes) {
            return array('error' => '账户不存在，请重新输入');
        }
        if (!isset($userRes['password']) || strcmp($userRes['password'], encryptPwd(trim($password))) !== 0) {
            return array('error' => '账号或密码错误，请重新输入');
        }

        // 更新最后登录时间
        $uid = $userRes['id'];
        $res = $this->where(array('id' => $uid))->save(array('login_time' => time()));
        if (!$res) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return array('error' => '登录失败！');
        }

        return array('id' => $uid);
    }

    /**
     * 微信登陆
     * @param type $code
     * @return type
     */
    public function weixinLogin($code = '', $city_id = 0) {

        // 获取微信用户信息
        $weixin = new \Common\Org\WeiXin();
        $weixinRes = $weixin->getWeiXinUserInfo($code);
        if (isset($weixinRes['error'])) {
            return false;
        }
        $unid = $weixinRes['unionid'];
        $user = M('user');
        $userRes = $user->where(array('unid' => $unid))->field('id')->find();
        if ($userRes) {
            $uid = $userRes['id'];
            $res = $user->where(array('id' => $uid))->save(array('login_time' => time()));
            if (!$res) {
                $this->errorInfo['info'] = $this->getDbError();
                $this->errorInfo['sql'] = $this->_sql();
                return false;
            }
            return $userRes;
        }

        // 开启事务
        $model = M();
        $model->startTrans();

        // 向用户表中添加数据
        $name = ternary($weixinRes['nickname'], '');
        $name = preg_replace('/[^a-zA-Z0-9_\p{Han}]/u', "", $name);
        if (!trim($name)) {
            $name = 'weixin';
        }
        $name = $this->getUserName($name);
        $password = '123456';
        $data = array(
            'username' => $name,
            'password' => encryptPwd($password),
            'create_time' => time(),
            'score' =>  C('POINTS.REG_USER') ? C('POINTS.REG_USER') : self::REG_SCORE,
            'login_time' => time(),
            'avatar' => ternary($weixinRes['headimgurl'], ''),
            'wxtoken' => ternary($weixinRes['openid'], ''),
            'unid' => $unid,
            'city_id' => $city_id ? intval($city_id) : 0,
        );
        $res = $uid = $this->add($data);
        if (!$res) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            $model->rollback();
            return false;
        }

        // 添加积分流水
        $res = $this->addCredit($uid, self::REG_SCORE, 'register');
        if (!$res) {
            $model->rollback();
            return false;
        }
        $model->commit();

        return array('id' => $uid);
    }

    /**
     * 第三方 qq 登录
     * @param type $code
     * @param type $city_id
     */
    public function qqLogin($code, $info, $city_id = 0) {

        if (!trim($code)) {
            return false;
        }
        if (!$info) {
            return false;
        }
        $qqData = $info;
        if (is_string($qqData)) {
            $qqData = @json_decode(base64_decode($info), true);
        }
        if (!$qqData || !isset($qqData['nickname'])) {
            return false;
        }
        $qqunid = $code;
        if (strpos($qqunid, 'qzone') === false) {
            $qqunid = "qzone:$code";
        }
        $user = M('user');
        $userRes = $user->where(array('sns' => $qqunid))->field('id')->find();
        if ($userRes) {
            $uid = $userRes['id'];
            $res = $user->where(array('id' => $uid))->save(array('login_time' => time()));
            if (!$res) {
                $this->errorInfo['info'] = $this->getDbError();
                $this->errorInfo['sql'] = $this->_sql();
                return false;
            }
            return $userRes;
        }

        // 开启事务
        $model = M();
        $model->startTrans();

        // 向用户表中添加数据
        $qqname = ternary($qqData['nickname'], 'qq');
        if (!trim($qqname)) {
            $qqname = 'qq';
        }
        $qqname = $this->getUserName($qqname);
        $password = '123456';
        $data = array(
            'username' => $qqname,
            'password' => encryptPwd($password),
            'create_time' => time(),
            'avatar' => ternary($qqData['usericon'], ''),
            'score' =>  C('POINTS.REG_USER') ? C('POINTS.REG_USER') : self::REG_SCORE,
            'login_time' => time(),
            'sns' => $qqunid,
            'city_id' => $city_id ? intval($city_id) : 0,
        );
        $res = $uid = $this->add($data);
        if (!$res) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            $model->rollback();
            return false;
        }

        // 添加积分流水
        $res = $this->addCredit($uid, self::REG_SCORE, 'register');
        if (!$res) {
            $model->rollback();
            return false;
        }
        $model->commit();

        return array('id' => $uid);
    }

    /**
     * qq web联合登录
     */
    public function qqWebLogin($call_back_url = '') {
        if (!trim($call_back_url)) {
            $call_back_url = 'http://' . $_SERVER['HTTP_HOST'] . '/PayCallBack/qqWebLoginCallbackHandle';
        }
        $qlogin = new \Common\Org\QqLogin();
        $qlogin->login($call_back_url);
    }

    /**
     * 添加积分流水
     */
    public function addCredit($uid, $score, $action) {
        if($action == 'register') {
            // 注册送积分设置
            $score = C('POINTS.REG_USER') ? C('POINTS.REG_USER') : $score;
        }

        // 添加积分流水
        $scoredata = array(
            'create_time' => time(),
            'user_id' => $uid,
            'score' => $score,
            'action' => $action,
            'sumscore' => $score
        );
        return M('credit')->add($scoredata);
    }

    /**
     * 手机验证码快捷支付
     * @param type $mobile
     * @param type $vCode
     * @return type
     */
    public function quickLogin($mobile, $vCode, $city_id = 0,$jy='old') {

        // 校验手机号码是否正确
        if (!checkMobile($mobile)) {
            return false;
        }

        // 验证手机验证码
        $res = $this->checkMobileVcode($vCode, $mobile, 'login',$jy);
        if (!$res) {
            return false;
        }

        // 校验是否注册
        $userRes = $this->isRegister(array('mobile|username|email' => $mobile));
        if (!$userRes) {
            $userRes = $this->mobileRegister($mobile, '123456', $vCode, 'login', $city_id, false,$jy);
        }
        if (!isset($userRes['id'])) {
            return false;
        }

        $uid = $userRes['id'];
        return array('id' => $uid);
    }

    /**
     * 判断手机号码是否注册
     * @param type $where
     * @return boolean
     */
    public function isRegister($where) {
        $res = $this->where($where)->field(array('id'))->find();
        if (!$res) {
            return false;
        }
        return $res;
    }

    /**
     * 判断是否合法的获取短信验证码类型
     */
    public function isActionType($action) {
        if (!trim($action)) {
            return false;
        }
        return in_array($action, $this->actionType);
    }

    /**
     * 判断是否合法的登陆类型
     */
    public function isLoginType($action) {
        if (!trim($action)) {
            return false;
        }
        return in_array($action, $this->loginType);
    }

    /**
     * 手机号码注册
     * @param type $mobile
     * @param type $password
     * @param type $vCode
     * @param type $action
     * @param type $isSendSms
     */
    public function mobileRegister($mobile, $password, $vCode, $action, $cityId = '', $isSendSms = false,$jy='old') {

        // 校验手机验证码是否正确
        $res = $this->checkMobileVcode($vCode, $mobile, $action,$jy);
        file_put_contents('/tmp/sms_paycallback.log',$mobile.'验证返回'.var_export($res, true).'||\n',FILE_APPEND);
        if (!$res) {
            return false;
        }

        // 校验该手机号是否已经注册
        $where = array(
            'mobile|username|email' => trim($mobile),
        );

        $res = $this->isRegister($where);
        file_put_contents('/tmp/sms_paycallback.log',$mobile.'检验是否有新用户'.var_export($res, true).'||\n',FILE_APPEND);
        if ($res) {
            return false;
        }

        // 开启事务
        $model = M();
        $model->startTrans();

        // 添加注册信息
        $data = array(
            'password' => encryptPwd(trim($password)),
            'score' =>  C('POINTS.REG_USER') ? C('POINTS.REG_USER') : self::REG_SCORE,
            'mobile' => trim($mobile),
            'create_time' => time(),
            'username' => trim($mobile),
            'city_id' => trim($cityId),
            'snsfrom'=>'手机',
        );
        $uid = $this->add($data);
        if (!$uid) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            $model->rollback();
            return false;
        }

        // 添加积分流水
        $res = $this->addCredit($uid, self::REG_SCORE, 'register');
        if (!$res) {
            $model->rollback();
            return false;
        }
        $model->commit();

        // 注册成功后是否发送短信
        if ($isSendSms) {
            $msg = $this->getSendSmsMsg('reg_success');
            $msg = str_replace('USERNAME', $mobile, $msg);
            $msg = str_replace('PASSWORD', $password, $msg);
            $sendSms = new \Common\Org\sendSms();
            $res = $sendSms->sendMsg(trim($mobile), $msg);
        }
        file_put_contents('/tmp/sms_paycallback.log',$mobile.'注册成功'.$uid.'||\n',FILE_APPEND);
        return array('id' => $uid);
    }

    /**
     * 校验手机验证码是否正确
     * @param type $vCode
     * @param type $mobile
     * @param type $action
     */
    public function checkMobileVcode($vCode, $mobile, $action,$jy='old') {

        // 非法参数判断
        if (!checkMobile($mobile)) {
            return false;
        }

        $sms = M('sms');
        $where = array('mobile' => trim($mobile), 'date' => date('Y-m-d'), 'action' => $action);
        $res = $sms->where($where)->find();

        if (!$res) {
            return false;
        }

        // 校验方式1
        $isClientSend = C('IS_CLIENT_SEND');
        $clent_var = isset($_SERVER['HTTP_CLIENTVERSION']) && trim($_SERVER['HTTP_CLIENTVERSION'])?$_SERVER['HTTP_CLIENTVERSION']:0;
        if($clent_var && strcmp($clent_var, '4.0.8') > 0){
            $isClientSend = C('IS_CLIENT_SEND_NEW');
        }
        if ($isClientSend) {
            // 去第三方校验
            $smsSend = new \Common\Org\sendSms();

            if($jy=='old'){
                $smsRes = $smsSend->checkVerify($mobile, $vCode);
                //2016.4.19加
                if (isset($smsRes['status']) && intval(trim($smsRes['status'])) == 1) {

                }else{
                    $smsRes = $smsSend->checkVerify2($mobile, $vCode,$jy);
                }
                //结束
            }else{
                $smsRes = $smsSend->checkVerify2($mobile, $vCode,$jy);
            }
            if (isset($smsRes['status']) && intval(trim($smsRes['status'])) == 1) {

                // 修改数据库校验码
                $sms->where(array('id' => $res['id']))->save(array('code' => trim($vCode), 'create_time' => time()));
                return true;
            }
        }

        // 验证码10分钟失效
        $reg_time = $res['create_time'];
        if (time() > $reg_time + 600) {
            return false;
        }

        // 获取校验码
        if (trim($vCode) != trim($res['code']) && strlen($vCode)!=4) {//增加$vCode长度判断为了兼容苹果一个bug2016.4.19
            return false;
        }

        return true;
    }

    /**
     * 获取发送短信的格式
     * @param type $action
     */
    public function getSendSmsMsg($action = 'other') {
        $msgType = C('MSG_TYPE');
        $msg = $msgType['other'];
        if (isset($msgType[$action])) {
            $msg = $msgType[$action];
        }
        return $msg;
    }

    /**
     * 获取验证码
     * @return type
     */
    public function getCode() {
        return rand(C('SMS_MIN'), C('SMS_MAX'));
    }

    /**
     * 获取微信 登录时，username
     * @param type $name username前缀
     * @return string
     */
    public function getUserName($name) {

        $res = $this->where(array('username' => $name))->count();
        if (!trim($name) && (!$res || $res <= 0)) {
            return $name;
        }

        while (true) {
            $username = $name . '_' . ($this->getCode());
            $res = $this->where(array('username' => $username))->count();
            if (!$res || $res <= 0) {
                return $username;
            }
        }
        return time();
    }

    /**
     * 绑定第三方账号
     * @param $user_id
     * @param $field
     * @param $val
     * @param $is_cover
     * @return bool
     */
    public function bindAccount($user_id, $field, $val, $is_cover = 0) {
        if ($field == 'unid') {
            $weixin = new \Common\Org\WeiXin();
            $weixinRes = $weixin->getWeiXinUserInfo($val);
            if (isset($weixinRes['error'])) {
                return false;
            }
            $val = $weixinRes['unionid'];
        } else if ($field == 'sns') {
            if (strpos($val, 'qzone') === false) {
                $val = 'qzone:' . $val;
            }
        }
        $map = array(
            'id' => array('NEQ', $user_id),
            $field => $val
        );
        $user = $this->where($map)->find();
        if (!empty($user) && isset($user['id'])) {
            return array(
                'error' => '此账号已绑定青团账号',
                'code' => -1
            );
        }

        $srcUser = $this->info($user_id);
        $score = 0;
        if (($srcUser['score'] + 10) < 0) {
            $score = 0;
        } else {
            $score = $srcUser['socre'] + 10;
        }
        $model = M();
        $model->startTrans();
        $userData = array(
            $field => $val,
            'score' => $score
        );
        $res = $this->where('id=' . $user_id)->save($userData);
        if ($res) {
            // 写入积分流水
            $scoreData = array(
                'create_time' => time(),
                'user_id' => $user_id,
                'score' => 10,
                'action' => 'binding',
                'sumscore' => $score
            );
            $creditRes = M('credit')->add($scoreData);
            if (!$creditRes) {
                $this->errorInfo['info'] = M('credit')->getDbError();
                $this->errorInfo['sql'] = M('credit')->_sql();
                $model->rollback();
                return false;
            }

            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * 解除用户绑定第三方账号
     * @param $user_id
     * @param $field
     * @return bool
     */
    public function unBindAccount($user_id, $field) {
        $score = $this->info($user_id, 'score');
        $score = $score['score'] > 10 ? $score['score'] - 10 : 0;
        $data = array(
            $field => '',
            'score' => $score
        );
        $res = $this->where('id=' . $user_id)->save($data);
        $scoreData = array(
            'create_time' => time(),
            'user_id' => $user_id,
            'score' => -10,
            'action' => 'binding',
            'sumscore' => $score
        );
        M('credit')->add($scoreData);
        return $res;
    }

}
