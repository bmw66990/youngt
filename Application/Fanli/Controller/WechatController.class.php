<?php
namespace Fanli\Controller;

use Common\Controller\CommonBusinessController;

require_once __DIR__ . "/wapWXpay/pcWXpay.class.php";//输入金额进行支付需要添加

class WechatController extends CommonController{

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

    /*
    *   支付完成的页面
    */
    public function paied(){        
        $this->display();
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

    public function getfun(){        
        $mid = I('get.mid', '0', 'intval'); //商户id
        $dis = M('fanli_dis')->where('partner_id='.$mid)->getField('ratio');
        $pdis = (1-$dis)*0.5*100;
        $this->assign('pdis',$pdis);
        $this->display();
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
        $wxuser = M('fanli_wxuser')->where(array('id'=>$order['uid']))->find();
        if (!$order || $order['prices'] == '' || $order['prices'] < 0) {  
            $this->assign('order_no', $order['order_no']);
            $this->assign('mobile', $wxuser['mobile']);       
            $this->display('fail');
            die;
        }
        //根据选择支付类型返回信息        
        
        $goods = $order['goods_name'];        
        $payFee = sprintf("%.2f", $order['prices']); 
        $order_no = $order['order_no'];

        $dataa = array(
            'out_trade_no' => $order_no,
            'product_name' => $goods,
            'money' => $payFee,
            'desc' =>  $goods,
        );       
        $openid = M('fanli_wxuser')->where('id='.$order['uid'])->getField('openid');
 
        $wxpay = new \pcWXpay();        
        $return = $wxpay->doPaynew($dataa);  

        if ($return) {            
            $this->assign('data', $return);            
            $this->assign('order_id', $order_no);            
            $this->assign('openid', $openid);            
            $this->display();
        }
    }

    /**
    *  微信输入金额提交支付
    */
    public function wechatPay() {                
        $mid = I('get.mid', '', 'intval'); //商户id                
        $origin = I('get.origin'); //订单总额
        $uid = I('get.uid', '', 'intval'); //当前用户id        
        if($origin == ''){
            
        }
        //获取商户名称
        $partner = M('fanli_partner')->where('id='.$mid)->find();
        $discount = M('fanli_dis')->where('partner_id='.$mid)->find();
        $benjin = $origin * $discount['ratio'];
        $fenchenge = $origin - ($origin * $discount['ratio']);

        //id+金额+随机数
        $rand = rand(100,999);
        $trade_no = $origin*100;
        $order_no = 'youngtfanli' . $uid . '_' . $trade_no.'_'.$rand;


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
            'pay_status'=>'unpay',            
            'capital'=>$benjin,            
            'create_time'=>time(),
        );    

        $fanli_order = M('fanli_order')->add($fodata);
        $wxuser = M('fanli_wxuser')->where(array('id'=>$uid))->find();
        if($origin == '' || $origin < 0){
            $this->assign('order_no', $order_no);
            $this->assign('mobile', $wxuser['mobile']);
            $this->display('fail');
            die;
        }

        $dataa = array(
            'order_id' => $fanli_order,
            'out_trade_no' => $order_no,
            'product_name' => $goods,
            'money' => $payFee,
            'desc' => "商品：" . $goods,
        );

        $wxpay = new \pcWXpay();
        $return =  $wxpay->doPaynew($dataa);  
        if ($return) {
            $this->assign('data', $return);
            $this->assign('oid', $order_id);
            $this->display('wxpay');
        }
        //$this->outPut($data, 0);
    }
    /*  扫码输入金额进行支付 end */

    public function notify()
    {
        require_once __DIR__ . "/qtw/WxPayPubHelper.php";//输入金额进行支付需要添加
        //使用通用通知接口
        $notify = new \Notify_pub();
        
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //file_put_contents('/tmp/wx.log',var_export($xml, true).'||',FILE_APPEND);  
        $notify->saveData($xml);
        
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if($notify->checkSign() == FALSE){
            $notify->setReturnParameter("return_code","FAIL");//返回状态码
            $notify->setReturnParameter("return_msg","签名失败");//返回信息
        }else{
            $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
        }
        $returnXml = $notify->returnXml();
                
//        file_put_contents('/tmp/wx.log',var_export($notify->checkSign(), true).'||',FILE_APPEND);  
        if($notify->checkSign() == true)
        {
            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                //log_result($log_name,"【通信出错】:\n".$xml."\n");
            }
            elseif($notify->data["result_code"] == "FAIL"){
                //此处应该更新一下订单状态，商户自行增删操作
                //log_result($log_name,"【业务出错】:\n".$xml."\n");
            }
            else{
                //此处应该更新一下订单状态，商户自行增删操作
                //log_result($log_name,"【支付成功】:\n".$xml."\n");
            }
        
            //商户自行增加处理流程,
            //例如：更新订单状态            
            $data = array(
                'vid'=>$notify->data['transaction_id'],
                'pay_time'=>time(),
                'pay_status'=>'pay',
            );                
            $resdata = M('fanli_order')->where(array('order_no'=>$notify->data['out_trade_no']))->save($data);
            $res = M('fanli_order')->where(array('order_no'=>$notify->data['out_trade_no']))->find();

            //开启事物
            $model = M();
            $model->startTrans();
            if(!$res){
                $model->rollback();                
                return false;
            }else{
                $fenchenge = $res['rebate_money'];
                $mid = $res['mid'];
                $uid = $res['uid'];
                $order_no = $res['order_no'];
                $partner = M('fanli_partner')->where('id='.$mid)->find();    
                if($res['pay_status'] == 'pay') {            
                    //  给商家返还本金
                    $shangdata = array(
                        'fanli_order'=>$order_no,
                        'mid'=>$mid,
                        'uid'=>$uid,
                        'fan_money'=>$res['capital'],               
                        'create_time'=>time(),
                        'status'=>'N',            
                        'user_attr'=>'merchant',            
                    );
                    $shangfm = M('fanli_money')->add($shangdata);

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
                }
            }
            $model->commit();

            //例如：数据库操作
            //例如：推送支付完成信息
        }
    }

    /*
    *   支付成功
    */    
    /*public function success(){
        $order_id = I('get.order_id','','trim');
        $fl_order = M('fanli_order');
        $fl_wxuser = M('fanli_wxuser');
        $fl_par = M('fanli_partner');
        $where = array(
            'id'=>$order_id,
        );
        $data = array(
            'pay_status'=>'pay',
        );
        $fodata = $fl_order->where($where)->save($data);

        $weuser = $fl_wxuser->where('id='.$fodata['uid'])->find('mobile');
        $merchant = $fl_par->where('id='.$fodata['mid'])->find('title');       
        $return_data=array(
            //订单编号
            'order'=>$fodata['order_no'],
            //手机号
            'mobile'=>$weuser['mobile'],
            //商户名称
            'merchant'=>$merchant['title'],
            //不参与返现金额
            'no_prices'=>'0',
            //支付金额
            'prices'=>$fodata['prices'],
            //返现金额
            'rebate'=>$fodata['rebate_money'],
            //下单时间
            'time'=>$fodata['create_time'],
        );    
        $this->assign('return_data',$return_data);
        $this->display();
    }*/

}