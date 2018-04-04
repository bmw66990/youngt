<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-20
 * Time: 15:50
 */

namespace MerchantApi\Controller;

class MemberController extends CommonController {
    
    const CUSTOMER_MOBILE = '4000998433';

    /**
     * 获取商家详情
     */
    public function getPartnerDetail() {
        // 获取商户想情
        $res = D('Partner')->info($this->uid);

        // 整理数据
        $data = array(
            'id' => ternary($res['id'], ''),
            'username' => ternary($res['username'], ''),
            'password' => ternary($res['password'], ''),
            'title' => ternary($res['title'], ''),
            'group_id' => ternary($res['group_id'], ''),
            'homepage' => ternary($res['homepage'], ''),
            'city_id' => ternary($res['city_id'], ''),
            'zone_id' => ternary($res['zone_id'], ''),
            'bank_name' => ternary($res['bank_name'], ''),
            'bank_no' => ternary($res['bank_no'], ''),
            'bank_user' => ternary($res['bank_user'], ''),
            'contact' => ternary($res['contact'], ''),
            'image' => ternary($res['image'], ''),
            'phone' => ternary($res['phone'], ''),
            'address' => ternary($res['address'], ''),
            'other' => ternary($res['other'], ''),
            'mobile' => ternary($res['mobile'], ''),
            'open' => ternary($res['open'], ''),
            'enable' => ternary($res['enable'], ''),
            'head' => ternary($res['head'], ''),
            'user_id' => ternary($res['user_id'], ''),
            'create_time' => ternary($res['create_time'], ''),
            'longlat' => ternary($res['longlat'], ''),
            'lat' => ternary($res['lat'], ''),
            'long' => ternary($res['long'], ''),
            'display' => ternary($res['display'], ''),
            'comment_good' => ternary($res['comment_good'], ''),
            'comment_none' => ternary($res['comment_none'], ''),
            'comment_bad' => ternary($res['comment_bad'], ''),
            'product_vip' => ternary($res['product_vip'], ''),
            'vip' => ternary($res['vip'], ''),
            'partner_balance' => ternary($res['partner_balance'], ''),
            'partner_money' => ternary($res['partner_money'], ''),
            'store' => ternary($res['store'], ''),
            'images' => ternary($res['images'], ''),
            'characteristics' => ternary($res['characteristics'], ''),
            'activity' => ternary($res['activity'], ''),
            'js_time' => ternary($res['js_time'], ''),
            'js_order_id' => ternary($res['js_order_id'], ''),
            'remarks' => ternary($res['remarks'], ''),
            'ptnum' => ternary($res['ptnum'], ''),
            'pnum' => ternary($res['pnum'], ''),
            'show_template' => ternary($res['show_template'], ''),
            'template' => ternary($res['template'], ''),
            'station_id' => ternary($res['station_id'], ''),
            'fid' => ternary($res['fid'], ''),
            'team' => ternary($res['team'], ''),
            'banks' => ternary($res['banks'], ''),
            'bankx' => ternary($res['bankx'], ''),
            'sbank' => ternary($res['sbank'], ''),
            'is_branch' => ternary($res['is_branch'], ''),
            'db_id' => ternary($res['db_id'], ''),
            'customer_mobile'=>self::CUSTOMER_MOBILE,
        );
        
        // 未体现金额获取
        $partner_income = M('partner_income');
        $money = $partner_income->where(array('partner_id' => $this->uid, 'pay_id' => 0,'is_express' => 'N'))->sum('money');
        if($money>0){
            $data['partner_money'] = $money;
        }
        //进入消费券号数量
        $where=array('partner_id'=>$this->uid,'consume_time'=>array('gt',strtotime(date('Y-m-d'))));
        $count=M('coupon')->where($where)->count('id');
        if($count){
            $data['count'] = $count;
        }else{
            $data['count'] = '';
        }
        $today_start = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $today_end = mktime(0,0,0,date('m'),date('d')+1,date('Y'));
        $wherecp = array(
            'partner_id' => $this->uid, 
            'consume_time' => array('between',array($today_start,$today_end)),               
        );        
        $coupon = M('coupon');
        $data['consum_total']=$coupon->where($wherecp)->count('id');
        $this->outPut($data, 0, '', '');
    }

    /**
     * 获取商家团单
     */
    public function getPartnerTeam() {
        $lastId = I('get.lastId', '');
        $time = time();
        $searchWhere = "begin_time<$time AND end_time>$time";
        if (trim($lastId) != '') {
            $searchWhere = "$searchWhere AND sort_order<$lastId";
        }

        // 获取商家团单
        $res = $this->_Search("partner_id:'{$this->uid}'", $searchWhere, array('sort_order' => '-', 'id' => '-'));
        if (!$res) {
            // 数据库查询
            $sort = array(
                'sort_order' => 'desc',
                'id' => 'desc',
            );
            $where = array(
                'partner_id' => $this->uid,
                'begin_time' => array('LT', $time),
                'end_time' => array('GT', $time)
            );
            if (trim($lastId) != '') {
                $where['sort_order'] = array('LT', $lastId);
            }
            $res = D('Team')->samePartnerOtherTeam($where, $sort);
        }

        // 整理数据
        $data = array();
        if ($res) {
            foreach ($res as $v) {
                $data[] = array(
                    'id' => ternary($v['id'], ''),
                    'product' => ternary($v['product'], ''),
                    'team_price' => ternary($v['team_price'], ''),
                    'begin_time' => ternary($v['begin_time'], ''),
                    'sort_order' => ternary($v['sort_order'], ''),
                );
            }
        }

        $this->outPut($data, 0, '', '');
    }

    /**
     * 结款纪录
     */
    public function paymentRecord() {
        $lastId = I('get.lastid', '');
        // 查询数据
        $data = $this->__getPartnerPaymentRecord($this->uid, $lastId);
        //增加银行相关信息
        $bank=M('partner')->where(array('id'=>$this->uid))->field('sbank,bank_no')->find();
        if($bank){
            if(is_numeric($bank['bank_no'])){
                $bank_no=$bank['sbank'].'(尾号'.substr($bank['bank_no'], -4, 4).')';
            }
            $data['bank']=$bank_no;
        }else{
            $data['bank']='';
        }

        $this->outPut($data, 0, '', '');
    }

    /**
     * 结款明细
     */
    public function paymentList() {

        $this->_checkblank('payId');
        $payId = I('get.payId', 0, 'intval');

        // 查询借款详情
        $where = array('partner_income.partner_id' => $this->uid, 'partner_income.pay_id' => $payId);
        if ($payId == 0) {
            $where['partner_income.create_time'] = array('ELT', time());
        }
        $partnerIncome = M('partner_income');
        $filed = array(
            'partner_income.id' => 'id',
            'partner_income.`team_id`' => 'team_id',
            'COUNT(partner_income.id)' => 'num',
            'SUM(partner_income.money)' => 'sumMoney',
            'team.product' => 'product',
            'team.ucaii_price' => 'ucaii_price',//供货价
            'team.team_price' => 'team_price',//团购价   
        );
        $list = $partnerIncome->where($where)->field($filed)->join('INNER JOIN team ON team.id=partner_income.`team_id`')->group('partner_income.`team_id`')->select();
        foreach ($list as $k => &$v) {
            $lirun = $v['ucaii_price'];
            if($lirun > 0){
                $v['lirun'] = $lirun;
            }else{
                $v['lirun'] = 0;
            }
        }
        // 查询时间
        $create_time = M('partner_pay')->where(array('id' => $payId))->getField('create_time');

        // 整理数据
        $data = array(
            'list' => $list,
            'create_time' => $create_time
        );

        $this->outPut($data, 0, '', '');
    }

     /**
     * 青团卷详情
     */
    public function couponDetail() {        
        $this->_checkblank('payId');
        $this->_checkblank('teamId');
        $payId = I('get.payId', 0, 'intval');
        $teamId = I('get.teamId', 0, 'intval');
        $lastId = I('get.lastId', 0, 'intval');
        $var = I('get.var', 0, 'intval');
        if (!trim($lastId)) {
            $lastId = time();
        }
        // 获取青团卷id
        
        $pwhere = array(
            'partner_income.partner_id' => $this->uid,
            'partner_income.team_id' => $teamId,
            'partner_income.pay_id' => $payId,
        );
        $partnerIncome = M('partner_income');        
        $filed = array(
            'partner_income.`team_id`' => 'team_id',            
            'SUM(partner_income.money)' => 'sumMoney',
            'coupon_id' => 'coupon_id',
            'team.product' => 'product',            
        );
        $couponRes = $partnerIncome->where($pwhere)->field($filed)->join('INNER JOIN team ON team.id=partner_income.`team_id`')->group('partner_income.`team_id`')->select();
        
        $couponIds = array();
        foreach ($couponRes as $v) {
            if (isset($v['coupon_id']) && trim($v['coupon_id'])) {
                $couponIds[] = $v['coupon_id'];
                $redname = $v['product'];
                $summoney = $v['summoney'];
            }
        }
        
        $couponCount = $partnerIncome->where($pwhere)->field('coupon_id')->select();
        
        $count =array();
        foreach ($couponCount as $m) {
            if (isset($m['coupon_id']) && trim($m['coupon_id'])){
                $count[] = $m['coupon_id'];
            }
        }

        // 查询青团信息
        $coupon = M('coupon');
        $cwhere = array(
            'coupon.id' => array('IN', $count),
            'coupon.create_time' => array('LT', $lastId),
        );
        $field = array(
            'coupon.id' => 'coupon_id',
            'coupon.team_id' => 'team_id',
            'coupon.consume_time' => 'create_time',//返回应该是消费时间
            'coupon.partner_id' => 'partner_id',            
            'coupon.team_price' => 'team_price',
            'coupon.ucaii_price' => 'ucaii_price',
            'coupon.team_type' => 'team_type',
            'user.username' => 'username'
        );        
        
        if($var){
            $newdata = $coupon->where($cwhere)->limit(20)->field($field)->join('INNER JOIN user ON user.id=coupon.`user_id`')->order('coupon.create_time DESC')->select(); 
            $data = array();
            foreach ($newdata as $k => &$v) {
                $data[$k]['coupon_id'] = $v['coupon_id'];
                $data[$k]['username'] = $v['username'];
                $data[$k]['create_time'] = $v['create_time'];
                $data[$k]['team_price'] = $v['team_price'];
                $data[$k]['ucaii_price'] = $v['ucaii_price'];
                $twhere = array(
                    'id'=>$v['team_id'],
                );
                $teamdata = M('team')->where($twhere)->field('id,lottery_price,activities_id,team_price')->find();
                if($teamdata['lottery_price']<$teamdata['team_price']){
                    $discount = $teamdata['team_price'] - $teamdata['lotter_price'];
                }else{
                    $discount = '0';
                }
                
                $awhere =array(
                    'id'=>$teamdata['activities_id'],
                );
                $admadata = M('admanage')->where($awhere)->field('id,textarr')->find();
                $data[$k]['type'] = $admadata['textarr'];
                $data[$k]['discount'] = $discount;
            }            
            $list = array(
                'product'=> $redname,
                'sumMoney'=> $summoney,
                'data'=> $data,
            );
            
            $this->outPut($list, 0, '', '');
        }else{
            $data = $coupon->where($cwhere)->limit(20)->field($field)->join('INNER JOIN user ON user.id=coupon.`user_id`')->order('coupon.create_time DESC')->select();       
            $this->outPut($data, 0, '', '');
        }       
    }

    /**
     * 结款申请
     */
    public function paymentApply() {
        $partner = D('Partner');
        $res = $partner->paymentApply($this->uid);
        if (!$res) {
            $this->outPut('', -1, '', '结款失败！(温馨提示：金额大于10元才可提款！)');
        }

        $this->outPut(null, 0, '', '');
    }

    /**
     * 分店列表
     */
    public function branchList() {
        // 查看当前商户是否有分店
        $partner = D('Partner');
        $data = $partner->getPartnerBranch($this->uid);

        $this->outPut($data, 0, '', '');
    }

    /**
     * 分店详情
     */
    public function branchDetail() {
        $this->_checkblank('partnerId');
        $partnerId = I('get.partnerId', '');
        $lastId = I('get.lastId', '');
        // 查询数据
        $data = $this->__getPartnerPaymentRecord($partnerId, $lastId);

        $this->outPut($data['list'], 0, '', '');
    }

    /**
     * 获取某个商家的结账纪录
     */
    private function __getPartnerPaymentRecord($partnerId, $lastId,$is_express='N') {

        if (!trim($partnerId)) {
            return array('error' => '商家id不能为空！');
        }

        // 查看结款纪录表
        $partner_pay = M('partner_pay');
        $where = array(
            'partner_id' => $partnerId,
            'is_express' => $is_express
        );
        if (trim($lastId) != '' && trim($lastId) !='null') {
            $where['create_time'] = array('LT', $lastId);
        }
        $list = $partner_pay->where($where)->limit($this->reqnum + 1)->order(array('create_time' => 'desc'))->select();
        $before = date('m-d',$list[0]['create_time']);
        $now = date('m-d',time());
        $time = $before.'~'.$now;
        $this->setHasNext(false, $list, $this->reqnum);
        // 查看待结款总金额
        $partner_income = M('partner_income');
        $money = $partner_income->where(array('partner_id' => $partnerId, 'pay_id' => 0,'is_express' => $is_express))->sum('money');

        foreach ($list as &$v) {            
            $parwhere = array('partner_income.partner_id' => $this->uid, 'partner_income.pay_id' => $v['id']);
            $parfiled = array(
                'COUNT(partner_income.id)' => 'num',
            );
            //var_dump($parwhere);

            $mun = $partner_income->where($parwhere)->field($parfiled)->join('INNER JOIN team ON team.id=partner_income.`team_id`')->group('partner_income.`team_id`')->select();            
            foreach ($mun as &$m) {
                $number += $m['num'];                
            }
            $v['num']=(string)$number;
        }

        // 整理数据
        $data = array(
            'money' => trim($money) ? $money : '0.0',
            'list' => $list,
            'time'=> $time,
        );
        return $data;
    }
    /**
     * 修改密码
     */
    public function putUpdatePwd() {
        $this->checkUser=true;
        $this->_checkblank(array('oldpwd', 'newpwd', 'renewpwd'));
        $model = D('Partner');
        $oldPwd = trim(I('post.oldpwd'));
        $newPwd = trim(I('post.newpwd'));
        $reNewPwd = trim(I('post.renewpwd'));
        if (!$model->checkPwd($this->uid, $oldPwd)) {
            //密码错误
            $this->outPut('', 2003);
        }
        if ($newPwd != $reNewPwd) {
            //新密码不相等
            $this->outPut('', 2004);
        }
        $curPwd = $model->info($this->uid, 'password');
        if ($curPwd === encryptPwd($newPwd)) {
            $this->outPut('', -1, '', '新密码不能与旧密码相等');
        }
        if ($rs = $model->where('id=' . $this->uid)->setField('password', encryptPwd($newPwd))) {
            $this->outPut('', 0);
        }
        $this->_writeDBErrorLog($rs, $model, 'api');
        $this->outPut('', 1014);
    }

}
