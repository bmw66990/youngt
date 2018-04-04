<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/18
 * Time: 14:00
 */

namespace Wap\Controller;

/**
 * 公共控制器
 * Class PublicController
 * @package Wap\Controller
 */
class PublicController extends CommonController {

    /**
     * 用户登录保存时间
     */
    const SAVE_LOGIN_TIME = 2592000;

    /**
     * 是否验证用户登录
     * @var bool
     */
    protected $checkUser = false;

    /**
     * 是否验证用户登录
     * @var bool
     */
    protected $checkCity = false;

    /**
     * 选择城市
     */
    public function city() {
        $hotCity = $this->_hotCity();
        $this->assign('hotCity', array_chunk($hotCity, 4));
        $cityList = $this->_getCategoryList('city');
        $letter = array();
        foreach($cityList as $row) {
            if(!in_array(strtoupper($row['letter']), $letter)) {
                $letter[] = strtoupper($row['letter']);
            }
        }
        sort($letter, SORT_STRING);

        $this->assign('letter', $letter);
        $this->assign('cname', cookie(C('SAVE_CITY_KEY')));
        $this->display();
    }

    /**
     * 城市节点
     */
    public function cityNode() {
        $this->_checkblank('node');
        $node     = I('get.node', '', 'strval');
        $cityList = $this->_getCategoryList('city');
        $cityNode = array();
        foreach($cityList as $row) {
            if(strtoupper($row['letter']) == strtoupper($node)) {
                $cityNode[] = $row;
            }
        }
        $this->assign('name', $node);
        $this->assign('citys', $cityNode);
        $this->display();
    }

    /**
     * 改变城市
     */
    public function changeCity() {
        $id = I('get.id', 0, 'intval');
        if(!$id) {
            $name = I('get.name', 'yangling', 'trim');
            $id = M('category')->where("ename='{$name}'")->getField('id');

            if(!$id) {
                $id = M('category')->where("ename='yangling'")->getField('id');
            }
        }

        cookie(C('SAVE_CITY_KEY'), $id, 30 * 86400, '/');
        redirect(U('Index/index'));
    }

    /**
     * 城市定位
     */
    public function locationCity() {
        // 接收参数
        $this->_checkblank(array('lng', 'lat'));
        $lng = I('get.lng', 0, 'doubleval');
        $lat = I('get.lat', 0, 'doubleval');
        $distance = I('get.distance ', 5, 'doubleval');

        // 定位城市
        $team = D('Team');
        $lngLatSquarePoint = $team->returnSquarePoint($lng, $lat, $distance);
        $_where = array(
            'partner.city_id' => array('GT', 0),
            'partner.`long`'  => array('EGT', $lngLatSquarePoint['left-top']['lng']),
            'partner.`lat`'   => array('EGT', $lngLatSquarePoint['left-bottom']['lat']),
        );
        $where = "partner.`long`<={$lngLatSquarePoint['right-bottom']['lng']} AND partner.`lat`<={$lngLatSquarePoint['left-top']['lat']}";
        $distanceField = $team->getMysqlDistanceField($lat, $lng);
        $field = array(
            'city_id' => 'city_id',
            $distanceField => 'distance',
        );

       $query_table = M('partner')->where($_where)->where($where)->field($field)->order('distance asc')->limit(20)->select(false);
        $res = M('partner')->table("($query_table) as t")->field(array('t.city_id'=>'city_id','count(t.city_id)'=>'city_id_count'))->group('t.city_id')->order('city_id_count desc')->find();
        $city_id   = 0;
        $city_name = '';
        if (isset($res['city_id']) && trim($res['city_id'])) {
            $city_res = $this->_getCategoryList('city');
            $city_id = $res['city_id'];
            $city_name = ternary($city_res[$city_id]['name'], '');
            $city_url = U('Public/changeCity', array('id' => $city_id));
        }
        $data = array(
            'status'    => 1,
            'city_id'   => $city_id,
            'city_name' => $city_name,
            'url'       => $city_url
        );
        $this->ajaxReturn($data);
    }

    /**
     * 用户登录
     */
    public function login(){
        if($this->_getUserId()){
            redirect(U('User/index'));
        }
        $jumpUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : cookie('jumpurl');
        $jumpUrl = !$jumpUrl ? U('User/index') : $jumpUrl;
        $this->assign('jumpUrl',$jumpUrl);
        $this->display();
    }

    /**
     * 登录逻辑处理
     */
    public function doLogin(){
        if(IS_AJAX){
            $jumpUrl = I('post.jumpUrl','','trim');
            $ajaxData = $this->_checkLogin();
            if(isset($ajaxData['error'])){
                $data = $ajaxData;
            }else{
                $this->_saveUserInfo($ajaxData['id']);
                $data = getPromptMessage('登录成功','success','1',$jumpUrl);
            }
        }else{
            $data = getPromptMessage('非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 登录检测
     */
    protected function _checkLogin(){
        $account  = I('post.account','','trim');
        $password = I('post.password','','trim');
        if(!$account)
            return getPromptMessage('请输入用户名');
        if(!$password)
            return getPromptMessage('请输入密码');
        $where = array('username|mobile|email'=>$account);
        $have_user = M('user')->where($where)->find();
        if(!$have_user)
            return getPromptMessage('账号信息不存在');
        if($have_user['password'] != encryptPwd($password))
            return getPromptMessage('密码错误');
        return $have_user;
    }

    /**
     * 保存用户登录信息
     */
    protected function _saveUserInfo($user_id){
        cookie(C('SAVE_USER_KEY'),$user_id,self::SAVE_LOGIN_TIME);
    }

    /**
     * 用户注册
     */
    public function register(){
        if($this->_getUserId()){
            redirect(U('User/index'));
        }
//        $agent = $_SERVER['HTTP_USER_AGENT'];
//        if(!strpos($agent,"icroMessenger")) {
//            echo '请求非法';exit;
//        }
        // 非手机端访问的注册 非法
        if (!isMobile()){
             exit('请求非法！');
        }

        //2016.3.6号加 根据注册是添加的ip以及当天时间判断是否有恶意注册
        $ip=get_client_ip();
        $time=time();
        $map = array(
            'ip' => $ip,
            'create_time' => $time
        );
        $count = M('user')->where($map)->count();
        if($count>10){
            redirect(U('Index/index'));
        }
        //end
        $this->display();
    }

    /**
     * 注册逻辑处理
     */
    public function doRegister(){
//        $agent = $_SERVER['HTTP_USER_AGENT'];
//        if(!strpos($agent,"icroMessenger")) {
//            echo '请求非法';exit;
//        }

         // 非手机端访问的注册 非法
        if (!isMobile()){
             exit('请求非法！');
        }
        $accountData = $this->_checkAccount();
        if($accountData['status'] == -1){
            $data = $accountData;
        }
        if(isset($data) === false){
            $codeData = $this->_checkCode();
            if($codeData === false){
                $data = getPromptMessage('验证码错误');
            }
        }
        $password = I('post.password','','trim');
        $pwd      = I('post.pwd','','trim');
        if(isset($data) === false){
            if(!$password || !$pwd || strlen($password) < 6 || strlen($password) > 18 || strlen($pwd) < 6 || strlen($pwd) > 18){
                $data = getPromptMessage('密码或确认密码不符合要求,密码长度必须是6-18位');
            }
        }
        if(isset($data) === false) {
            if ($password != $pwd) {
                $data = getPromptMessage('两次密码不一致!');
            }
        }

        if(isset($data) === false) {
            $user_id = $this->_registerUser();
            if ($user_id) {
                $this->_saveUserInfo($user_id);
                $data = getPromptMessage('注册成功', 'success', '1', U('User/index'));
            } else {
                $data = getPromptMessage('注册失败!');
            }
        }
        $this->ajaxReturn($data);
    }

    /**
     * 检测账户是否已存在
     */
    protected function _checkAccount(){
        $Model = D('User');
        $mobile = I('post.mobile','','trim');
        if(checkMobile($mobile) === false){
            $data = getPromptMessage('手机号码格式不正确');
        }else{
            $res = $Model->checkAccount($mobile);
            if($res){
                $data = getPromptMessage('手机号码已注册');
            }else{
                $data = getPromptMessage('','success',1);
            }
        }
        return $data;
    }

    /**
     * 检测验证码
     */
    protected function _checkCode($action = 'wapreg'){
        $code = I('post.code',0,'intval');
        $mobile = I('post.mobile','','trim');
        return D('Sms')->checkMobileVcode($mobile,$code,$action);
        /*$where = array('code' => $code, 'mobile' => $mobile, 'action' => $action, 'date' => date('Y-m-d'));
        $count = D('Sms')->getTotal($where);
        if ($count > 0) {
            return true;
        } else {
            return false;
        }*/
    }

    /**
     * 获取短息验证码
     */
    public function smsCode(){
//        $agent = $_SERVER['HTTP_USER_AGENT'];
//        if(!strpos($agent,"icroMessenger")) {
//            $data = getPromptMessage('非法请求');
//        }
//        // 非手机端访问的注册 非法
        if (!isMobile()){
             $data = getPromptMessage('非法请求');
        }
        if (IS_AJAX) {
            $mobile   = I('post.mobile', '', 'trim');
            $reSetPwd = I('post.reSetPwd', 'wapreg', 'trim');
            $data = $this->_sendSms($mobile, '', 'wap', $reSetPwd);
            if ($data['status'] == 0) {
                $data = getPromptMessage('发送成功','success',1);
            }
        } else {
            $data = getPromptMessage('非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 向数据库保存注册信息
     */
    protected function _registerUser(){
        $city = $this->_getCityId();
        $ip=get_client_ip();
        $data['mobile']      = I('post.mobile', '', 'trim');
        $data['password']    = encryptPwd(I('post.password', '', 'trim'));
        $data['username']    = $data['mobile'];
        $data['create_time'] = time();
        $data['active']      = 1;
        $data['city_id']     = ternary($city,0);
        $data['score']       = C('POINTS.REG_USER') ? C('POINTS.REG_USER') : 20;
        $data['snsfrom']     = 'wap';//注册来源
        $data['ip']            =$ip;
        $Model               = M();
        $Model->startTrans();
        $uid       = M('user')->add($data);
        $scoreData = array(
            'create_time' => time(),
            'user_id'     => $uid,
            'score'       => $data['score'],
            'action'      => 'register',
            'sumscore'    => $data['score']
        );
        $sid       = M('credit')->add($scoreData);
        if ($uid && $sid) {
            $Model->commit();
            return $uid;
        } else {
            $Model->rollback();
            return 0;
        }
    }
    /**
     * 找回密码
     */
    public function  retrievePwd(){
        $this->display();
    }

    /**
     * 找回密码异步处理
     */
    public function doRetrievePwd(){
        $Model = D('User');
        $mobile = I('post.mobile','',trim);
        $user_id = $Model->where(array('mobile'=>$mobile))->getField('id');
        if(!$user_id){
            $data = getPromptMessage('该用户不存在');
        }
        if(isset($data) === false){
            $codeData = $this->_checkCode('wapnpwd');
            if($codeData === false){
                $data = getPromptMessage('验证码错误');
            }
        }
        $password = I('post.password','','trim');
        $pwd      = I('post.pwd','','trim');
        if(isset($data) === false){
            if(!$password || !$pwd || strlen($password) < 6 || strlen($password) > 18 || strlen($pwd) < 6 || strlen($pwd) > 18){
                $data = getPromptMessage('新密码或确认密码不符合要求');
            }
        }
        if(isset($data) === false) {
            if ($password != $pwd) {
                $data = getPromptMessage('两次密码不一致!');
            }
        }

        if(isset($data) === false) {
            $up_data = array(
                'id'=>$user_id,
                'password' => encryptPwd($password),
            );
            $res = $Model->save($up_data);
            if($res){
                $data = getPromptMessage('找回密码成功，请登录验证','success',1,U('Public/login'));
            }else{
                $data = getPromptMessage('新密码不能与原始密码相同!');
            }
        }
        $this->ajaxReturn($data);
    }

    /**
     * 注销登录
     */
    public function logout(){
        cookie(C('SAVE_USER_KEY'),null);
        redirect(U('Public/login'));
    }

    /**
     * 客户端下载
     */
    public function client_download(){
        client_download();
        $this->display();
    }

    /**
     * 券号验证
     */
    public function verifyCoupon(){
        $this->display();
    }

    /**
     * 券号验证
     */
    public function checkCoupon(){
        if(IS_AJAX){
            $coupon_id = str_replace(' ', '',I('post.coupon_id'));
            if(!$coupon_id){
                $data = getPromptMessage('请输入券号');
            }else{
                $map=array('id'=>$coupon_id);
                $coupon=M('coupon')->where($map)->find();
                if($coupon){
                    $data = getPromptMessage($coupon,'success',1);
                }else{
                    $data = getPromptMessage('券号不存在');
                }
            }
        }else{
            $data = getPromptMessage('非法请求');
        }
        $this->ajaxReturn($data);
    }
    /**
     * 积分验证
     */
    public function jfcheckCoupon(){
        $this->_checkblank('points_code');
        $points_code = str_replace(' ', '',I('post.points_code','','trim'));
        if (!$points_code) {
            $data = getPromptMessage('积分兑换码不能为空！');
            $this->ajaxReturn($data);
        }

        if(strlen($points_code) != 10){
            $data = getPromptMessage('积分兑换码长度为10！');
            $this->ajaxReturn($data);
        }

        $where = array(
            'code' => $points_code,
        );
        $points_order_info = M('points_order')->where($where)->find();
        if (!$points_order_info) {
            $data = getPromptMessage('兑换券错误！');
            $this->ajaxReturn($data);
        }

        if (!isset($points_order_info['team_id']) || !trim($points_order_info['team_id'])) {
            $data = getPromptMessage('该兑换券所属商品信息异常！');
            $this->ajaxReturn($data);
        }

        if (!isset($points_order_info['partner_id']) || !trim($points_order_info['partner_id'])) {
            $data = getPromptMessage('该兑换券所属商家信息异常！');
            $this->ajaxReturn($data);
        }

        $points_team_info = M('points_team')->where(array('id' => $points_order_info['team_id']))->field('name')->find();
        if (!$points_team_info) {
            $data = getPromptMessage('该兑换券所属商品信息异常！');
            $this->ajaxReturn($data);
        }

        $partner_info = M('partner')->where(array('id' => $points_order_info['partner_id']))->field('title,mobile')->find();
        if (!$partner_info) {
            $data = getPromptMessage('该兑换券所属商家信息异常！');
            $this->ajaxReturn($data);
        }

        $data = array(
            'title' => ternary($points_team_info['name'], ''),
            'score' => ternary($points_order_info['score'], ''),
            'num' => intval(ternary($points_order_info['num'], '')),
            'total_score' => ternary($points_order_info['total_score'], ''),
            'points_code' => ternary($points_order_info['code'], ''),
            'expire_time' => ternary($points_order_info['expire_time'], ''),
            'partner' => ternary($partner_info['title'], ''),
            'mobile' => ternary($partner_info['mobile'], ''),
            'status' => 4, // 状态 1-已消费  2-已过期 3-验证成功 4-未验证
        );

        if (isset($points_order_info['expire_time']) && $points_order_info['expire_time'] < strtotime(date('Y-m-d'))) {
            $data['status'] = 2;
            $data = getPromptMessage('该兑换券已过期！');
            $this->ajaxReturn($data);
        }

        if (isset($points_order_info['consume']) && trim($points_order_info['consume']) == 'Y') {
            $data = getPromptMessage('该兑换券已验证！');
            $this->ajaxReturn($data);
        }

        $up_data = array(
            'consume' => 'Y',
            'consume_time' => time()
        );
        $res = M('points_order')->where(array('id'=>$points_order_info['id']))->save($up_data);
        if(!$res){
            $data = getPromptMessage('兑换券兑换失败！');
            $this->ajaxReturn($data);
        }else{
            $data = getPromptMessage($points_order_info['id'],'success',1);
            $this->ajaxReturn($data);
        }
    }

    /**
     * 券号详情
     */
    public function getCouponDetail(){
        $Coupon          = M('coupon');
        $coupon_id       = str_replace(' ', '', I('param.coupon_id'));
        $coupon          = $Coupon->where(array('id' => $coupon_id))->find();
        if(!$coupon){
            //如果券号不存在跳转回券号验证页面
            $this->redirect(U('Public/verifyCoupon'));
        }
        $list['team']    = M('team')->find($coupon['team_id']);
        $list['partner'] = M('partner')->find($coupon['partner_id']);
        if ($coupon['consume'] == 'Y') {
            $list['coupon'] = $coupon;
            $list['state']  = "Y";
            $this->assign('list', $list);
            $this->display('verifyDetail');
        }elseif ($coupon['expire_time'] < strtotime(date('Y-m-d'))) {
            $list['coupon'] = $coupon;
            $list['state']  = "N";
            $this->assign('list', $list);
            $this->display('verifyDetail');
        }else{
            $map             = array('consume' => 'N', 'order_id' => $coupon['order_id'], 'expire_time' => array('egt', strtotime(date('Y-m-d'))));
            $list['coupons'] = $Coupon->where($map)->select();
            $list['count']   = count($list['coupons']);
            $this->assign('list', $list);
            $this->display();
        }
    }
    /**
     * 券号详情
     */
    public function getjfCouponDetail(){
        $Coupon          = M('coupon');
        $coupon_id       = str_replace(' ', '', I('param.coupon_id'));
        $coupon          = $Coupon->where(array('id' => $coupon_id))->find();
        if(!$coupon){
            //如果券号不存在跳转回券号验证页面
            $this->redirect(U('Public/verifyCoupon'));
        }
        $list['team']    = M('team')->find($coupon['team_id']);
        $list['partner'] = M('partner')->find($coupon['partner_id']);
        if ($coupon['consume'] == 'Y') {
            $list['coupon'] = $coupon;
            $list['state']  = "Y";
            $this->assign('list', $list);
            $this->display('verifyDetail');
        }elseif ($coupon['expire_time'] < strtotime(date('Y-m-d'))) {
            $list['coupon'] = $coupon;
            $list['state']  = "N";
            $this->assign('list', $list);
            $this->display('verifyDetail');
        }else{
            $map             = array('consume' => 'N', 'order_id' => $coupon['order_id'], 'expire_time' => array('egt', strtotime(date('Y-m-d'))));
            $list['coupons'] = $Coupon->where($map)->select();
            $list['count']   = count($list['coupons']);
            $this->assign('list', $list);
            $this->display();
        }
    }
    /**
     * 券号验证
     */
    public function doVerifyCoupon(){
        $id  = str_replace(' ', '', I('post.coupon_id', 0, 'strval'));
        if ($id) {
            $Model      = D('Coupon');
            $coupons = $Model->where(array('id'=>array('in',$id)))->select();
            if(!$coupons){
                //如果券号不存在跳转回券号验证页面
                $this->redirect(U('Public/verifyCoupon'));
            }
            foreach ($coupons as $val){
                if($val['consume'] == 'Y'  || $val['expire_time'] < strtotime(date('Y-m-d'))){
                    $this->redirect('Public/getCouponDetail', array('coupon_id' => $val['id']));
                }
            }
            $coupon = $Model->info($id[0]);
            $data = array(
                'id'       => $id,
                'order_id' => $coupon['order_id']
            );
            $list['team'] = M('team')->find($coupon['team_id']);
            $list['partner'] = M('partner')->find($coupon['partner_id']);
            $result = $Model->consumeCoupon($data, $coupon['partner_id'], 'wap端');
            $list['coupon'] = $Model->info($id[0]);
            if($result){
                $this->assign('coupon_id',$id);
                $this->assign('list',$list);
                $this->display('verifySuccessDetail');
            }else{
                $list['state'] = 'E';
                $this->assign('list',$list);
                $this->display('verifyDetail');
            }
        }else{
            //如果券号不存在跳转回券号验证页面
            $this->redirect(U('Public/verifyCoupon'));
        }
    }
    /**
     * 异步判断用户是否登陆
     */
    public function isLogin() {
        if (IS_AJAX) {
            if ($this->uid) {
                $userInfo = $this->_getUserInfo();
                $city_info = cookie('city');
                $data     = array('status' => 1,'id'=>  ternary($userInfo['id'], ''),'city'=>"{$city_info['name']}({$city_info['id']})", 'username' => ternary($userInfo['username'], $userInfo['mobile']),'mobile'=>$userInfo['mobile']);
            } else {
                $data = array('status' => -1);
            }
            $this->ajaxReturn($data);
        }
    }

    // 微信授权回调
    public function getWxAuth($tid) {
        $msg = '授权失败！';
        if (isset($_GET['code']) && trim($_GET['code'])) {
            $code = trim($_GET['code']);
            $appid  = \Common\Org\WeiXin::SEND_APPID;
            $secret = \Common\Org\WeiXin::SEND_APPSECRET;
            $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
            $http = new \Common\Org\Http();
            $res = json_decode($http->post($token_url));
            if (isset($res->access_token) && $res->access_token) {
                $token  = $res->access_token;
                $openid = $res->openid;
                unset($res);

                $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$token.'&openid='.$openid.'&lang=zh_CN';
                $res = json_decode($http->get($info_url));
                $nickname = $res->nickname;

                $data = array(
                    'openid'     => $openid,
                    'user_id'    => $this->_getUserId(),
                    'weixinname' => $nickname
                );
                $weixin_sy = M('weixin_sy');
                if ($weixin_sy->add($data)) {
                    $msg = '授权成功！';
                }
            }
        }
        redirect(U('Team/detail',array('id'=>$tid,'error' => base64_encode($msg))));
    }
    public function taobaoke(){
        $data['title']='科尚2016夏新款镂空钩花真丝连衣裙女中长款桑蚕丝连衣裙优雅显瘦';
        $data['author']='新信息商店';
        $data['postDate']='9.9元';
        $data['firstAccess']='月销10000';
        $data['url']='下单购买';
        $this->ajaxReturn($data);
    }
    public function taoketop(){
        $data['title']='科尚2016夏新款镂空钩花真丝连衣裙女中长款桑蚕丝连衣裙优雅显瘦';
        $data['author']='新信息商店';
        $data['postDate']='9.9元';
        $data['firstAccess']='月销10000';
        $data['url']='下单购买';
        $data['html']='<a href=http://www.sina.com title=test target=_blank><img src=http://cdn.taokezhushou.com/main.png width=350 height=70></a>';
        $this->ajaxReturn($data);
    }
}