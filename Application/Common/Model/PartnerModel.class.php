<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-18
 * Time: 11:56
 */

namespace Common\Model;

/**
 * 商户模型
 * Class PartnerModel
 * @package Common\Model
 */
class PartnerModel extends CommonModel {

    const PAYMENT_DEAL_TIME = 3;

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

    /**
     * 自动验证
     * @var array
     */
    protected $_validate = array(
        array('username', 'require', '商户账号必须填写'),
        array('username', '', '商户账号已经存在', 0, 'unique', 1),
        array('password', 'require', '密码必须填写', 1, '', 1),
        array('password', 'checkPwd', '密码必须是6~20位数字和字母组成', 1, 'function',1),
        array('title', 'require', '商家名称必须填写'),
        array('title', '', '商家名称已经存在', 0, 'unique', 1),
        array('zone_id', 'require', '商圈必须选择', 1),
        array('group_id', 'require', '行业必须选择', 1),
        array('longlat', 'require', '请标注商家坐标信息'),
        array('phone', 'require', '预约电话必须填写'),
        array('address', 'require', '商户地址必须填写'),
        array('homepage', 'require', '网站地址必须填写'),
        array('contact', 'require', '联系人必须填写'),
        array('bank_name', 'require', '开户银行必须填写', 1, '', 1),
        array('bank_user', 'require', '开户名必须填写', 1, '', 1),
        array('bank_no', 'require', '银行账户必须填写', 1, '', 1),
        array('is_branch', array('Y', 'N'), '错误值', 2, 'in'),
    );

    /**
     * 自动完成
     */
    protected $_auto = array(
        array('is_branch', 'N'),
        array('create_time', 'time', 1, 'function'),
        array('password', 'encryptPwd', 1, 'function'),
        array('bank_name', '', 2, 'ignore'),
        array('bank_user', '', 2, 'ignore'),
        array('bank_no', '', 2, 'ignore'),
    );

    /**
     * 获取商家列表
     * @param $where where条件
     * @param $order 排序
     * @param $limit
     * @return mixed
     */
    public function getPartnerList($where, $order, $limit) {
        $field = 'p.id,p.title,p.contact,p.phone,p.mobile,p.open,p.head,p.db_id,p.city_id,c.name catname,bu.db_name';
        $list  = $this->table('partner p')->field($field)->join('LEFT JOIN category c on p.group_id=c.id')->join('LEFT JOIN bd_user bu on p.db_id=bu.id')->where($where)->order($order)->limit($limit)->select();
        if ($list === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $list;
    }

    /**
     * 获取接待商家总条数
     * @param string $where
     * @return mixed|void
     */
    public function getReceptionCount($where) {
        $condition = 'c.partner_id=p.id';
        $total     = $this->table('coupon c,partner p')->where($condition)->where($where)->order('null')->getField('partner_id', true);
        if ($total === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }

        return count(array_unique($total));
    }

    /**
     * 获取接待列表-----//此方法需要优化，引起没有通用性，不直观，考虑废弃
     * @param $where
     * @param $limit
     * @return mixed
     */
    public function getReceptionList($where, $limit) {
        //$field     = 'c.id,c.partner_id,p.username,count(c.id) num,sum(c.team_price - c.ucaii_price) profit';
        /*$field     = 'c.id,c.partner_id,p.username,count(c.id) num';
        $condition = 'c.partner_id=p.id';
        $order     = 'num DESC';
        $data      = $this->table('coupon c,partner p')
            ->field($field)
            ->where($where)
            ->where($condition)
            ->group('c.partner_id')
            ->order($order)
            ->limit($limit)
            ->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }*/

        $field = 'c.partner_id,p.username,count(c.id) num';
        $sub = $this->table('coupon c')
            ->field($field)
            ->join('LEFT JOIN partner p on c.partner_id=p.id')
            ->where($where)
            ->group('c.partner_id')
            ->order('NULL')
            ->buildSql();

        $data = M()->table($sub . 't')->order('t.num DESC')->limit($limit)->select();
        echo M()->getLastSql();
        //echo $data;exit;
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $data;
    }

    /**
     * 获取商家利润明细
     * @param  $id    商家编号
     * @param  $stime 开始时间
     * @param  $etime 截止时间
     * @return array
     */
    public function getProfit($id,$stime,$etime) {
        $model = M('team');
        $data = $model->alias('t')
                      ->join('INNER JOIN coupon c ON t.id=c.team_id')
                      ->field('sum(t.team_price - t.ucaii_price) as profit, t.product, t.team_price, t.ucaii_price, t.id, sum(1) as num')
                      ->where(array('c.consume_time'=>array('between', array($stime, $etime)),'c.partner_id'=>$id))
                      ->group('t.id')
                      ->select();
        return $data;
    }

    /**
     * 获取商家利润
     * @param $where
     * @param $group
     * @return mixed
     */
    // 参数封装过度，无法从方法声明得知方法需要的确切参数，调用成本太高，考虑重构 2016-06-14(张乔)
    public function getPartnerProfit($where, $group) {
        $data = M('Coupon')->alias('c')->join('team t on c.team_id=t.id')->where($where)->group($group)->order('null')->getField($group . ',sum(t.team_price-t.ucaii_price) profit');
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $data;
    }

    /**
     * 获取商家总金额
     */
    public function getPartnerMoney($where) {
        //获取总金额
        $totalMoney = M('PartnerIncome')->where($where)->group('partner_id')->order('null')->getField('partner_id,sum(money) total', true);
        if ($totalMoney === false) {
            $this->errorInfo['info'] = M('PartnerIncome')->getDbError();
            $this->errorInfo['sql']  = M('PartnerIncome')->_sql();
        }
        return $totalMoney;
    }

    /**
     * 获取商家详情信息
     * @param $id
     * @return mixed
     */
    public function getPartnerDetail($id) {
        if (!$id)
            return false;
        //TODO 确定显示字段信息
        $field         = 'p.id,p.username,p.password,p.address,p.title,p.group_id,p.homepage,p.zone_id,p.bank_name,p.bank_no,p.bank_user,p.contact,p.phone,p.mobile,p.open,p.head,p.db_id,p.city_id,c.name catname,bu.db_name,p.group_id,p.image,p.long,p.lat';
        $where['p.id'] = $id;
        $vo            = $this->table('partner p')->field($field)->join('LEFT JOIN category c on p.group_id=c.id')->join('LEFT JOIN bd_user bu on p.db_id=bu.id')->where($where)->find();
        if ($vo === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $vo;
    }

    /**
     * 检测账号是否存在
     */
    public function checkAccount($user) {
        $vo = $this->where(array('username' => $user))->count();
        if ($vo === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        if ($vo == 1) {
            return true;
        }
        return false;
    }

    /**
     * 确认结算
     * @param $id
     * @return bool
     */
    public function comfirmPay($id) {
        $PartnerIncome = M('partner_income');
        $time          = time();
        $where         = array(
            'partner_id'  => $id,
            'pay_id'      => 0,
            'create_time' => array('elt', $time)
        );
        $money         = $PartnerIncome->where($where)->sum('money');
        if ($money === false) {
            $this->errorInfo['info'] = $PartnerIncome->getDbError();
            $this->errorInfo['sql']  = $PartnerIncome->_sql();
            return false;
        }
        if (empty($money)) {
            return array(
                'error' => '金额为0，无法结算',
                'code'  => -1,
            );
        }
        $partnerXX=M('partner')->where(array('id' => $id))->field('bank_user,bank_name,bank_no')->find();
        $model = M();
        $model->startTrans();
        $nowtime    = time() + 3 * 86400;
        $payData    = array(
            'partner_id'  => $id,
            'partner_bankname'  => $partnerXX['bank_name'],
            'partner_bankno'  => $partnerXX['bank_no'],
            'partner_bankuser'  => $partnerXX['bank_user'],
            'money'       => $money,
            'end_time'    => $nowtime,
            'create_time' => $time
        );
        $PartnerPay = M('PartnerPay');
        $pay_id     = $PartnerPay->add($payData);
        if ($pay_id) {
            $incomeRes = $PartnerIncome->where($where)->save(array('pay_id' => $pay_id));
            if ($incomeRes === false) {
                $this->errorInfo['info'] = $PartnerIncome->getDbError();
                $this->errorInfo['sql']  = $PartnerIncome->_sql();
                $model->rollback();
                return false;
            }
            $upblance = array(
                'partner_money' => 0.00,
                'remarks'       => date('Y-m-d H:i:s') . $money . "元已申请提现，请等待处理",
            );
            $res      = $this->where('id =' . $id)->save($upblance);
            if ($res === false) {
                $this->errorInfo['info'] = $this->getDbError();
                $this->errorInfo['sql']  = $this->_sql();
                $model->rollback();
                return false;
            }
        } else {
            $this->errorInfo['info'] = $PartnerPay->getDbError();
            $this->errorInfo['sql']  = $PartnerPay->_sql();
            $model->rollback();
            return false;
        }
        $model->commit();
        return true;
    }

    /**
     * 获取结算详情
     * @param $pay_id
     * @param $partner_id
     * @return array|bool
     */
    public function getPayDetail($pay_id, $partner_id) {
        $data = array();
        if ($pay_id == 0) {
            $map = array(
                'partner_id'  => $partner_id,
                'pay_id'      => 0,
                'create_time' => array('elt', time())
            );
        } else {
            $map = array(
                'id'         => $pay_id,
                'partner_id' => $partner_id
            );
            $pay = D('PartnerPay')->where($map)->find();
            if ($pay === false) {
                $this->errorInfo['info'] = D('PartnerPay')->getDbError();
                $this->errorInfo['sql']  = D('PartnerPay')->_sql();
                return false;
            }
            if (empty($pay)) {
                return array(
                    'error' => '结算信息不存在',
                    'code'  => -1
                );
            }
            $data['pay'] = $pay;
        }

        $model = M('PartnerIncome');
        $list  = $model->field('team_id,count(id) as num,sum(money) as sumMoney')->where($map)->group('team_id')->select();

        if ($list === false) {
            $this->errorInfo['info'] = $model->getDbError();
            $this->errorInfo['sql']  = $model->_sql();
            return false;
        }
        $data['list'] = $data;
        return $data;
    }

    /**
     * 获取团单结算详情
     * @param $pay_id
     * @param $team_id
     * @param $partner_id
     * @return bool
     */
    public function getCouponDetail($pay_id, $team_id, $partner_id) {
        $where = array(
            'partner_id' => $partner_id,
            'pay_id'     => $pay_id,
            'team_id'    => $team_id
        );

        $model = M('partner_income');
        $list  = $model->field('coupon_id,create_time,partner_id')->where($where)->order('create_time desc')->select();
        if ($list) {
            $couponId = '';
            foreach ($list as &$val) {
                $val['create_time'] = date('m-d', $val['create_time']);
                $couponId .= $val['coupon_id'] . ',';
            }
            $map['id'] = array('in', substr($couponId, 0, -1));
            $user      = M('Coupon c')->join('user u on c.user_id=u.id')->where($map)->select()->getField('c.id,u.username', true);
            foreach ($list as &$val) {
                $val['username'] = ternary($user[$val['coupon_id']], '');
            }
        } else {
            $this->errorInfo['info'] = $model->getDbError();
            $this->errorInfo['sql']  = $model->_sql();
            return false;
        }

        return $list;
    }

    /**
     * 判断商家是否存在
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
     * 商家登陆
     * @param $name
     * @param $pwd
     * @return array|bool|mixed
     */
    public function login($name, $pwd) {
        $map = array(
            'username|mobile' => trim($name),
            'password'        => encryptPwd($pwd)
        );

        $data = $this->where($map)->find();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        }
        if ($data) {
            return $data;
        } else {
            return array(
                'error' => '账号或密码错误',
                'code'  => 2001
            );
        }
    }

    /**
     * 获取分店列表
     * @param type $partner_id
     * @return type
     */
    public function getPartnerBranch($partner_id = '') {
        $res = $this->where(array('id' => $partner_id))->getField('is_branch');
        if (strtolower(trim($res)) == 'n') {
            return array();
        }

        $where = array(
            'fid'       => $partner_id,
            'is_branch' => 'N',
        );
        $filed = array(
            'id',
            'phone',
            'mobile',
            'address',
            'username'
        );

        $data = $this->where($where)->field($filed)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            $data                    = array();
        }
        return $data;
    }

    /**
     * 申请提现
     * @param type $partner_id
     * @return boolean
     */
    public function paymentApply($partner_id = '',$is_express='N') {

        if (!trim($partner_id)) {
            return false;
        }

        $partnerIncome = M('partner_income');
        $time          = time();
        $where         = array(
            'partner_id'  => $partner_id,
            'pay_id'      => 0,
            'create_time' => array('ELT', $time),
            'is_express' => $is_express
        );
        $money         = $partnerIncome->where($where)->sum('money');
        if ($money <= 10 &&$partner_id!='28355') {//不合作的商家申请金额
            return false;
        }

        // 开启事务
        $model = M();
        $model->startTrans();

        // 添加申请结算表数据
        $nowTime    = time();
        $endTime    = strtotime('+' . self::PAYMENT_DEAL_TIME . ' day ');
        $partnerXX=M('partner')->where(array('id' => $partner_id))->field('bank_user,bank_name,bank_no')->find();
        $data       = array(
            'partner_id'  => $partner_id,
            'partner_bankname'  => $partnerXX['bank_name'],
            'partner_bankno'  => $partnerXX['bank_no'],
            'partner_bankuser'  => $partnerXX['bank_user'],
            'money'       => $money,
            'end_time'    => $endTime,
            'create_time' => $nowTime,
             'is_express' => $is_express
        );
        $partnerPay = M('partner_pay');
        $payId      = $partnerPay->data($data)->add();
        if (!$payId) {
            $model->rollback();
            $this->errorInfo['info'] = $partnerPay->getDbError();
            $this->errorInfo['sql']  = $partnerPay->_sql();
            return false;
        }

        // 修改商家结算表
        $res = $partnerIncome->where($where)->save(array('pay_id' => $payId));
        if (!$res) {
            $model->rollback();
            $this->errorInfo['info'] = $partnerIncome->getDbError();
            $this->errorInfo['sql']  = $partnerIncome->_sql();
            return false;
        }

        // 修改商家信息表
        $nowTime = date('Y-m-d H:i:s', $nowTime);
        $updata  = array(
            'partner_money' => 0.00,
            'remarks'       => "{$nowTime}{$money}元已申请提现，等待处理",
        );
        $partner = M('partner');
        $res     = $partner->where(array('id' => $partner_id))->save($updata);
        if (!$res) {
            $model->rollback();
            $this->errorInfo['info'] = $partner->getDbError();
            $this->errorInfo['sql']  = $partner->_sql();
            return false;
        }
        $model->commit();
        return true;
    }

    /**
     * 根据商户id获取业务员信息
     * @param type $partner_id
     */
    public function getDBUser($partner_id = '') {

        if (!trim($partner_id)) {
            return fasle;
        }
        // 查询条件
        $where = array(
            'id' => $partner_id,
        );
        $dbId  = $this->where($where)->field('db_id')->find();
        if (!$dbId || !isset($dbId['db_id']) || !trim($dbId['db_id'])) {
            return false;
        }
        $db = M('bd_user')->where(array('id' => $dbId['db_id']))->field('db_username,db_phone')->find();
        if (!$db) {
            return false;
        }

        return $db;
    }

    /**
     * 获取业务员签约商家
     * @param $where
     */
    public function getBdUserPartner($where) {
        $data = $this->alias('t')->where($where)->group('db_id')->getField('db_id,count(id)', true);
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }
    
    /**
     * 商户添加 编辑 字段非法判断
     */
    public function checkPartnerValidate($data=array()){
        if(!isset($data['username']) || !trim($data['username'])){
            return array('error'=>'商户账号必须填写');
        }
        if(!isset($data['title']) || !trim($data['title'])){
            return array('error'=>'商家名称必须填写');
        }
        $where = array('username'=>trim($data['username']));
        if(isset($data['id']) && trim($data['id'])){
            $where['id']=array('neq',$data['id']);
            if(isset($data['password']) && trim($data['password']) && !checkPwd($data['password'])){
                return array('error'=>'密码必须是6~20位数字和字母组成');
            }
        }else{
            if(!isset($data['password']) || !trim($data['password'])){
                return array('error'=>'密码必须填写');
            }
            if(!checkPwd($data['password'])){
                return array('error'=>'密码必须是6~20位数字和字母组成');
            }
            
        }
        $partner_count = $this->where($where)->count();
        if($partner_count && intval($partner_count)>0){
            return array('error'=>'商户账号已经存在');
        }
        unset($where['username']);
        $where['title']=trim($data['title']);
        $partner_count = $this->where($where)->count();
        if($partner_count && intval($partner_count)>0){
            return array('error'=>'商家名称已经存在');
        }
        
        if(!isset($data['image']) || !trim($data['image'])){
            return array('error'=>'商户图片必须上传');
        }
        
//        if(!isset($data['zone_id']) || !trim($data['zone_id'])){
//            return array('error'=>'商圈必须选择');
//        }
        if(!isset($data['group_id']) || !trim($data['group_id'])){
            return array('error'=>'行业必须选择');
        }
        if(!isset($data['longlat']) || !trim($data['longlat'])){
            return array('error'=>'请标注商家坐标信息');
        }
        if(!isset($data['phone']) || !trim($data['phone'])){
            return array('error'=>'预约电话必须填写');
        }
        if(!isset($data['address']) || !trim($data['address'])){
            return array('error'=>'商户地址必须填写');
        }
//        if(!isset($data['homepage']) || !trim($data['homepage'])){
//            return array('error'=>'网站地址必须填写');
//        }
        if(!isset($data['contact']) || !trim($data['contact'])){
            return array('error'=>'联系人必须填写');
        }
        if(!isset($data['mobile']) || !trim($data['mobile'])){
            return array('error'=>'验证电话必须填写');
        }
        if(!isset($data['bank_name']) || !trim($data['bank_name'])){
            return array('error'=>'开户银行必须填写');
        }
        if(!isset($data['bank_user']) || !trim($data['bank_user'])){
            return array('error'=>'开户名必须填写');
        }
        if(!isset($data['bank_no']) || !trim($data['bank_no'])){
            return array('error'=>'联系人必须填写');
        }
       
        return array('message'=>'验证成功！');
        
        
    }
    //app商家修改密码
    /**
     * 检查用户密码是否正确
     * @param $uid
     * @param $pwd
     * @return bool
     */
    public function checkPwd($uid, $pwd) {
        $partner  =  $this->info($uid, 'id,password');
        if (strcmp(encryptPwd($pwd), $partner['password']) === 0) {
            return true;
        }
        return false;
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
     * 获取验证码
     * @return type
     */
    public function getCode() {
        return rand(C('SMS_MIN'), C('SMS_MAX'));
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
     * 判断是否合法的获取短信验证码类型
     */
    public function isActionType($action) {
        if (!trim($action)) {
            return false;
        }
        return in_array($action, $this->actionType);
    }
}
