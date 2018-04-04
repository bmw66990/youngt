<?php
namespace Fanli\Controller;

require_once __DIR__ . "/wapWXpay/pcWXpay.class.php";//输入金额进行支付需要添加

class UserController extends CommonController{    

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
    }

    /*
    *   推广赚钱
    */
    public function shareQrcode(){
        $uid = I('get.uid','0','trim');
        $this->_PutshareQrcode($uid);        
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
            $data = $this->getSendSms($mobile, '', 'wap', $reSetPwd);
            if ($data['status'] == 0) {
                $data = getPromptMessage('发送成功','success',1);
            }
        } else {
            $data = getPromptMessage('非法请求');
        }
        $this->ajaxReturn($data);
    }

    

    public function userIndex(){
        $msg = '授权失败！';          
        if (isset($_GET['code']) && trim($_GET['code'])) {
            $code = trim($_GET['code']);
            $mid = trim($_GET['state']);
                            
            $appid = 'wx5aaa6db815374f64';
            $secret = 'bee335ac4a3f5ee2878e2dcba1835a1a';
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
                $username = $res->username;
                $sex = $res->sex;
                $language = $res->language;
                $city = $res->nickcityname;
                $province = $res->province;
                $country = $res->country;
                $lng = $res->lng;
                $lat = $res->lat;
                $headimgurl = $res->headimgurl;                
                
                $mdfid = rand(100,999).rand(100,999);

                //判断微信用户有没有存在微信用户表中
                $wxwhere = array(
                    'openid'=>$openid,                    
                );
                $wxuser = M('fanli_wxuser')->where($wxwhere)->find();
                //不存在添加用户到微信用户表中                
                if(!$wxuser){
                    //  如果该用户没有在买单返中进行过联合登录则他的上一级为商家的id，并且添加到商家微信用户表中
                    $weixin = M('fanli_wxuser')->where('id='.$mid)->find();

                    //  如果该上一级用户中存在上一级和上上一级，则新用户的gfid为上一级的fid
                    if($weixin['fid']){
                        $wxdata =array(                                
                            'fid'=>$mid,
                            'gfid'=>$weixin['fid'],                   
                            'cardid'=>$mdfid,
                            'username'=>$username,
                            'openid'=>$openid,
                            'nickname'=>$nickname,
                            'sex'=>$sex,
                            'language'=>$language,
                            'city'=>$city,
                            'province'=>$province,
                            'country'=>$country,
                            'lng'=>$lng,
                            'lat'=>$lat,
                            'headimgurl'=>$headimgurl,
                            'subscribe_time'=>time(),                            
                        );
                    }else{
                        $wxdata =array(    
                            'fid'=>$mid,                 
                            'cardid'=>$mdfid,
                            'username'=>$username,
                            'openid'=>$openid,
                            'nickname'=>$nickname,
                            'sex'=>$sex,
                            'language'=>$language,
                            'city'=>$city,
                            'province'=>$province,
                            'country'=>$country,
                            'lng'=>$lng,
                            'lat'=>$lat,
                            'headimgurl'=>$headimgurl,
                            'subscribe_time'=>time(),                            
                        );
                    }                    
                    $wechatuser = M('fanli_wxuser')->add($wxdata);
                    $msg='授权成功！';
                    redirect('http://fanli.ree9.com/User/index?openid='.$openid);                  
                }else{                    
                    $msg='授权成功！';                    
                    redirect('http://fanli.ree9.com/User/index?openid='.$openid);
                }

                /* $data = array(
                    'openid'     => $openid,
                    'user_id'    => $this->_getUserId(),
                    'weixinname' => $nickname
                );
                $weixin_sy = M('weixin_sy');
                if ($weixin_sy->add($data)) {
                    $msg = '授权成功！';
                }*/
            }
        } 
    }

    /*
    *  个人中心主页
    */
    public function index() {   
        $openid = I('get.openid');
        $cookie = $_COOKIE['uid'];

        //第一次如果没有授权过，先授权，然后登录
        //第二次授权过，有cookie['uid']
        if(!$openid){            
            if(!$cookie) {
                $this->_PutshareQrcode(0);
            }else{
                $where =array(
                    'id'=>$cookie,
                );
            }
        }else{
            $where =array(
                'openid'=>$openid,
            );
        }
        //$uid = 12;
        
        $fan = M('fanli_money');
        $wechat = M('fanli_wxuser');
        $forder = M('fanli_order');
        
        //我的微信名称和我的买单宝id
        $my_info = $wechat->where($where)->find();
        if(!$my_info){
            $this->_PutshareQrcode(0);
        }
        $uid = $my_info['id'];
        //返利订单数量及所有
        $where=array(
            'uid'=>$uid,
            'user_attr'=>'user',
        );
        $order_count = $fan->where($where)->count('id');
       
      
        //我可用的提现金额
        $fan_where=array(
            'uid'=>$uid,
            'status'=>'N',
            'user_attr'=>'user',
        );
    
        $pay = $fan->where($fan_where)->sum('fan_money');
        if(!$pay){
            $payment_money = 0;
        }else{
            $payment_money = $pay;
        }
       
        //我推荐的用户的总数量
        $where =array(
            'fid|gfid'=>$uid,
        );
        $usercount = $wechat->where($where)->count('id');
        $data=array(
            'my_info'=>$my_info,            
            'usercount'=>$usercount,            
            'order_count'=>$order_count,            
        );     
        $this->assign('payment_money', $payment_money);        
        $this->assign('uid', $uid);        
        $this->assign('data', $data);
        $this->display();
    }    

    /*
    *   推广赚钱
    */
    public function promoteMakemonery(){
        $uid = I('get.uid', '', 'trim');
        $this->assign('uid', $uid);
        $this->display();
    }

    /*
    *   我的粉丝
    */
    public function myRecommend(){
        $uid = I('get.uid', '', 'trim');

        $wechat = M('fanli_wxuser');
        //查看我推荐的一级和二级用户
        //我的一级用户
        $user_first = $wechat->where('fid='.$uid)->order('subscribe_time desc')->select();
        $firstcount = $wechat->where('fid='.$uid)->count('id');
        //我的二级用户
        $user_second = $wechat->where('gfid='.$uid)->order('subscribe_time desc')->select();
        $secondcount = $wechat->where('gfid='.$uid)->count('id');
        $data = array(
            'firstcount'=>$firstcount,
            'secondcount'=>$secondcount,
        );
        $this->assign('user_first', $user_first);
        $this->assign('user_second', $user_second);
        $this->assign('data', $data);
        $this->display();
    }

    /*
    *   返利订单
    */
    public function order(){
        $uid = I('get.uid', '', 'trim');
        $fan = M('fanli_money');
        $forder = M('fanli_order');
        $wechat = M('fanli_wxuser');
        $where=array(
            'uid'=>$uid,
            'user_attr'=>'user',
        );
        $order_fanli = $fan->where($where)->order('create_time desc')->select();        
        $order=array();
        foreach ($order_fanli as $k => $v) {
            $order[$k]['id'] = $v['id'];
            $order[$k]['fan_money'] = $v['fan_money'];
            $order[$k]['create_time'] = $v['create_time'];
            $order[$k]['fanli_order'] = $v['fanli_order'];
            $fwhere = array(
                'order_no'=>$v['fanli_order'],
            );
            $fouid = $forder->where($fwhere)->getField('uid');
            $order[$k]['cardid']=$wechat->where('id='.$fouid)->getField('cardid');
            /*$weixin = $wechat->where('id='.$v['uid'])->find();
            $order[$k]['cardid'] = $weixin['cardid'];*/
        }
        $this->assign('order', $order);
        $this->display();
    }

    /*
    *   财务记录
    */
    public function financialRecords(){
        $uid = I('get.uid', '', 'trim');
        $fan = M('fanli_money');
        $forder = M('fanli_order');
        $wechat = M('fanli_wxuser');
        $order_fanli = $fan->where('uid='.$uid)->order('create_time desc')->select();        
        $order=array();
        foreach ($order_fanli as $k => $v) {
            $order[$k]['id'] = $v['id'];
            $order[$k]['fan_money'] = $v['fan_money'];
            $order[$k]['create_time'] = $v['create_time'];
            $order[$k]['fanli_order'] = $v['fanli_order'];
            $fwhere = array(
                'order_no'=>$v['fanli_order'],
            );
            $fouid = $forder->where($fwhere)->getField('uid');
            $order[$k]['cardid']=$wechat->where('id='.$fouid)->getField('cardid');
            /*$weixin = $wechat->where('id='.$v['uid'])->find();
            $order[$k]['cardid'] = $weixin['cardid'];*/
        }
        $this->assign('record', $order);
        $this->display();
    }

    /*
    *   用户提现
    */
    public function withdrawalPage(){
        $pay = I('get.pay', '', 'trim');
        if($pay == 0){
            $this->display('busniess_error');
            die;
        }
        $this->display();
    }

    /*
    *   用户提现完成
    */
    public function wancheng(){
        $uid = I('get.uid', '', 'trim');//用户的id用作获取用户的openid
        $jine = I('get.jine', '', 'trim');//获取提现金额
        $pay = I('get.pay', '', 'trim');//获取可提现金额
        
        //获取用户的openid
        $openid = M('fanli_wxuser')->where('id='.$uid)->getField('openid');
        $data=array(
            'jine' =>$jine,
            'pay' =>$pay,
            'openid' =>$openid,
        );
        $wxpay = new \pcWXpay();
        $return =  $wxpay->rebate($data);
        $this->display();
    }

    /*
     *  绑定手机号
    */    
    public function bindPhone() {
        if(IS_POST){
            $mobile = I('post.mobile','', '');
            $cookie = $_COOKIE['uid'];
            $data = array(
                'mobile'=>$mobile
            );
            $res = M('fanli_wxuser')->where('id='.$cookie)->save($data);
            if(!$res){
                $res = 0;
            }
            $this->assign('res', $res);
            $this->display();
            die;
        }        
        $this->assign('res', 2);
        $this->display();
    }

    /*
     *  提现记录
    */    
    public function withdrawalhis() {        
        $cookie = $_COOKIE['uid'];
        $res = M('fanli_sett')->where('uid='.$cookie)->select();        
        $this->assign('data', $res);
        $this->display();
    }

    /*
     *  推荐商家
    */    
    public function bus_recommend() {
        $this->display();
    }

    /*
     *  推荐商家
    */    
    public function do_recommend() {
        $partner_data = I('post.partner', array(), '');
        if($partner_data['title'] == null || $partner_data['address'] == null || $partner_data['mobile'] == null || $partner_data['username'] == null){
            $this->display('busniess_error');
            die;
        }        
        $data = array(
            'username' => $partner_data['username'],
            'title' => $partner_data['title'],
            'mobile' => $partner_data['mobile'],
            'address' => $partner_data['address'],
            'create_time' => time(),
        );
        $res = M('fanli_partner')->add($data);
        if (!$res) {            
            $this->display('busniess_error');
            die;
        }
        $this->display();
    }

    /*
     *  提交商家资料
    */    
    public function check_add_edit() {
        $partner_data = I('post.partner', array(), '');
        if (!isset($partner_data['title']) || !trim($partner_data['title'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商家店名不能为空！'));
        }
        if (!isset($partner_data['address']) || !trim($partner_data['address'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商家住址必须上传！'));
        }
        if (!isset($partner_data['username']) || !trim($partner_data['username'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商家姓名不能为空！'));
        }
        if (!isset($partner_data['mobile']) || !trim($partner_data['mobile'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请填写商家电话！'));
        }       
        $this->ajaxReturn(array('code' => 0));
    }

}