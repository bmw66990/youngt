<?php
/**
 * Created by PhpStorm.
 * User: zhoombin@126.com
 * Date: 2016/4/21
 * Time: 16:32
 */

namespace Common\Model;

use Common\Model\CommonModel;

class FanliOrderModel extends CommonModel {    

    /*
     * 第三方支付回调
     */
    public function paySuccess($pay_id, $total_fee, $payAction, $trade_no) {
        $order = M('fanli_order');
        $order_id = trim($pay_id);
        if (strpos($order_id, '-') !== false) {
            list($_, $order_id, $_, $_) = @explode('-', $order_id, 4);
        }
        $where['order_no'] = $order_id;
        $orderRes = $order->where($where)->order('origin DESC')->select();
        $orderCount = count($orderRes);
        if ($orderCount < 1) {
            return array('error' => 'fail');
        }
        if ($orderCount == 1) {
            $orderRes = array_pop($orderRes);
            if ($orderRes && isset($orderRes['pay_status']) && $orderRes['pay_status'] == 'unpay') {
                // 支付成功后更新数据库信息
                $upRes = $this->updateOrderUser($pay_id, $trade_no);
                if (!$upRes) {
                    return array('error' => 'fail');
                }
            }
            return array('message' => 'success');
        }
    }

    /**
     * 支付回调成功后修改相关信息
     * @param type $orderRes
     * @param type $trade_no
     */
    public function updateOrderUser($orderRes, $trade_no = '') {
        $order = M('fanli_order');
        $data = array(
            'vid'=>$trade_no,
            'pay_time'=>time(),
            'pay_status'=>'pay',
        );        
        $res = $order->where(array('order_no'=>$orderRes))->save($data);

        if(!$res){
            return false;
        }else{
            $fenchenge = $res['rebate_money'];
            $mid = $res['mid'];
            $uid = $res['uid'];
            $order_no = $res['order_no'];
            $partner = M('fanli_partner')->where('id='.$mid)->find();
            if($res['pay_status'] = 'pay'){
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

            return true;
        }
    }

    
}