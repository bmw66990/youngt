<?php
namespace Fanli\Controller;

use Common\Controller\CommonBusinessController;

require_once __DIR__ . "/wapWXpay/pcWXpay.class.php";//输入金额进行支付需要添加

class WechatController extends CommonController{

    const CALL_WAPBACK_HANDLE_URL = 'http://fanli.ree9.com/Paycallback/notify/pay_type/wapwechatpay';        

    public function index() {
        $this->display();
    }

    /*
    *   获取商家id
    */
    public function getqrcode1(){        
        $this->display();
    }    

    /*
    *   获取商家id
    */
    public function getqrcode(){
        $mid = I('get.mid','0','trim');
        $this->_getWxShareData($mid);
    }

     // 微信授权回调   
    public function my_index() {
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
                $subscribe_time = $res->subscribe_time;
                
                $mdfid = rand(100,999).rand(100,999);

                //判断微信用户有没有存在微信用户表中
                $wxwhere = array(
                    'openid'=>$openid,
                    'nickname'=>$nickname,
                );
                $wxuser = M('fanli_wxuser')->where($wxwhere)->find();
                //不存在添加用户到微信用户表中                
                if(!$wxuser){
                    //  如果该用户没有在买单返中进行过联合登录则他的上一级为商家的id，并且添加到商家微信用户表中
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
                        'subscribe_time'=>$subscribe_time,
                    );
                    $wechatuser = M('fanli_wxuser')->add($wxdata);
                    $msg='授权成功！';                
                    redirect('http://fanli.ree9.com/Wechat/getfun?mid='.$mid.'&uid='.$wxuser['id']);                  
                }else{                    
                    $msg='授权成功！';                    
                    redirect('http://fanli.ree9.com/Wechat/getfun?mid='.$mid.'&uid='.$wxuser['id']);
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

    

    /*  扫码输入金额进行支付 start */
    /*
    *   微信扫码输入金额支付
    */
    public function wxpay()
    {
        $order_id = I('get.order_id', '0', 'intval'); //订单id
        $map = array(            
            'id' => $order_id
        );
        $order = M('fanli_order')->where($map)->find();

        if (!$order) {
            echo '订单不存在';//$this->outPut(null, -1, null, '订单不存在');
        }
        //根据选择支付类型返回信息
        $data = array();
        $pay_id = $order['order_no'] . '_' . $order['prices'] * 100;
        $goods = $order['goods_name'];        
        $payFee = sprintf("%.2f", $order['prices']); 
        $order_no = $order['order_no'];
        $mid = $order['mid'];
        $uid = $order['uid'];
        $fenchenge = $order['rebate_money'];
        $partner = M('fanli_partner')->where('id='.$mid)->find();
        $dataa = array(
            'out_trade_no' => $pay_id,
            'product_name' => $goods,
            'money' => $payFee,
            'desc' =>  $goods,
        );       
        $openid = M('fanli_wxuser')->where('id='.$order['uid'])->getField('openid');
 
        //服务器异步通知页面路径
        if (!isset($option['notify_url'])) {
            $option['notify_url'] = self::CALL_WAPBACK_HANDLE_URL ;
        }
        //页面跳转同步通知页面路径
        if (!isset($option['return_url'])) {
            $option['return_url'] = self::CALL_WAPBACK_HANDLE_URL ;//'http://wap.youngt.net/User/order.html';
        }
        if (!isset($option['merchant_url'])) {
            $option['merchant_url'] = self::CALL_WAPBACK_HANDLE_URL ;
        }

        $wxpay = new \pcWXpay();        
        $return = $wxpay->doPaynew($dataa, $option);         

        //添加返利金额到返利金额表
        //给各级返利的金额
        $my_rm = round(($fenchenge * 0.5),2);
        $user_rm = round(($fenchenge * 0.2),2);        
        $dai_rm = round(($fenchenge * 0.1),2);
        //返利的利润构成  给自己返利50% + 给公司返利10% + 给代理返利10%
        // 先给自己返利 50%
        $mydata = array(
            'fanli_order'=>$order_no,
            'mid'=>$mid,
            'uid'=>$uid,
            'fan_money'=>$my_rm,               
            'create_time'=>time(),
            'status'=>'N',            
            'user_attr'=>'user',            
        );
        $myfm = M('fanli_money')->add($mydata);
        
        //  给公司返利10%
        $pingdata = array(
            'fanli_order'=>$order_no,
            'mid'=>$mid,
            'uid'=>'0',
            'fan_money'=>$dai_rm,               
            'create_time'=>time(),
            'status'=>'N', 
            'user_attr'=>'company',          
        );
        $pingfm = M('fanli_money')->add($pingdata);
        //  给代理返利10%        
        $daidata = array(
            'fanli_order'=>$order_no,
            'mid'=>$mid,
            'uid'=>$partner['fanli_mid'],
            'fan_money'=>$dai_rm,               
            'create_time'=>time(),
            'status'=>'N',
            'user_attr'=>'agent',          
        );
        $daifm = M('fanli_money')->add($daidata);
        
        //判断用户有没有上级
        $weixin = M('fanli_wxuser')->where('id='.$uid)->find();
        //上级id == 商户id
        if($weixin['fid'] == $mid){
            //给商户返利20%
            $shangdata = array(
                'fanli_order'=>$order_no,
                'mid'=>$mid,
                'uid'=>$mid,
                'fan_money'=>$user_rm,               
                'create_time'=>time(),
                'status'=>'N',
                'user_attr'=>'merchant',               
            );
            $shangfm = M('fanli_money')->add($shangdata);
            //给公司返利10%
            $gongdata = array(
                'fanli_order'=>$order_no,
                'mid'=>$mid,
                'uid'=>'0',
                'fan_money'=>$dai_rm,               
                'create_time'=>time(),
                'status'=>'N',
                'user_attr'=>'company',               
            );
            $gongfm = M('fanli_money')->add($gongdata);
        }else{            
            //给上一级用户返利20%
            $userdata = array(
                'fanli_order'=>$order_no,
                'mid'=>$mid,
                'uid'=>$weixin['fid'],
                'fan_money'=>$user_rm,               
                'create_time'=>time(),
                'status'=>'N',
                'user_attr'=>'user',             
            );
            $userfm = M('fanli_money')->add($userdata);
            //给上上一级返利10%
            $shangdata = array(
                'fanli_order'=>$order_no,
                'mid'=>$mid,
                'uid'=>$weixin['gfid'],
                'fan_money'=>$dai_rm,               
                'create_time'=>time(),
                'status'=>'N',
                'user_attr'=>'user',                
            );
            $shangfm = M('fanli_money')->add($shangdata);              
        }

        if ($return) {            
            $this->assign('data', $return);            
            $this->assign('openid', $openid);
            $this->display();
        }
    }

    /**
    *  微信输入金额提交支付
    */
    public function wechatPay() {        
        $mid = I('get.mid', '', 'intval'); //商户id        
        $order_no = I('get.order_no', '', 'trim'); //订单编号
        $origin = I('get.origin'); //订单总额
        $uid = I('get.uid', '', 'intval'); //当前用户id
                
        //获取商户名称
        $partner = M('fanli_partner')->where('id='.$mid)->find();
        $discount = M('fanli_dis')->where('partner_id='.$mid)->find();
        $fenchenge = $origin - ($origin * $discount['ratio']);

        //根据选择支付类型返回信息
        $data = array();
        $pay_id = $order_no . '_'.$origin;
        $goods = $partner['title'];                
        $payFee = sprintf("%.2f", $origin); 

        //添加用户订单到返利订单
        $fodata = array(
            'uid'=>$uid,
            'mid'=>$mid,
            'order_no'=>$order_no,
            'goods_name'=>$goods,
            'prices'=>$origin,            
            'pay_type'=>'wapwechatpay',            
            'rebate_money'=>$fenchenge,            
            'create_time'=>time(),
        );    

        $fanli_order = M('fanli_order')->add($fodata);        
        
        $dataa = array(
            'order_id' => $fanli_order,
            'out_trade_no' => $pay_id,
            'product_name' => $goods,
            'money' => $payFee,
            'desc' => "商品：" . $goods,
        );

        //服务器异步通知页面路径
        if (!isset($option['notify_url'])) {
            $option['notify_url'] = self::CALL_WAPBACK_HANDLE_URL ;
        }
        //页面跳转同步通知页面路径
        if (!isset($option['return_url'])) {
            $option['return_url'] = self::CALL_WAPBACK_HANDLE_URL ;//'http://wap.youngt.net/User/order.html';
        }
        if (!isset($option['merchant_url'])) {
            $option['merchant_url'] = self::CALL_WAPBACK_HANDLE_URL ;
        }       
        $wxpay = new \pcWXpay();
        $return =  $wxpay->doPaynew($dataa, $option);        
        


        if ($return) {
            $this->assign('data', $return);
            $this->assign('oid', $order_id);
            $this->display('wxpay');
        }
        //$this->outPut($data, 0);
    }
    /*  扫码输入金额进行支付 end */
    
}
