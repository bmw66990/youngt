<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class OrderController extends CommonController {

    public function index() {

        $mail_order_pay_state = I('get.mail_order_pay_state', '', 'trim');
        $pay_id = I('get.pay_id', '', 'trim');

        $where = array(
            'team.partner_id' => $this->_getPartnerByid($this->partner_id),
            'team.team_type' => 'goods',
            '_string'=>"(order.state='pay' or (order.state='unpay' and order.rstate<>'normal'))"
        );
        if($pay_id!=''){
            $where_partner_income = array(
                'partner_id'=>$this->partner_id,
                'pay_id'=>intval($pay_id),
                'is_express'=>'Y'
            );
            $pay_id_res= M('partner_income')->where($where_partner_income)->field('coupon_id')->select();
            $pay_ids = array();
            if($pay_id_res){
                foreach($pay_id_res as &$v){
                    $pay_ids[$v['coupon_id']] = intval($v['coupon_id']);
                }
                unset($v);
            }
            if(!$pay_ids){
                array_push($pay_ids,0);
            }
           $where['order.id'] = array('in',$pay_ids);
        }
        $count = M('order')->where($where)
                ->join('inner join team on team.id=order.team_id')
                ->count();
        $Page = $this->pages($count, $this->reqnum);
        $field = array(
            'order.id' => 'order_id',
            'order.optional_model' => 'order_optional_model',
            'order.user_id' => 'order_user_id',
            'order.address' => 'order_address',
            'order.address_id' => 'order_address_id',
            'order.delivery_time' => 'order_delivery_time',
            'order.rstate' => 'order_rstate',
            'order.pay_time' => 'order_pay_time',
            'order.quantity' => 'order_quantity',
            'order.origin' => 'order_origin',
            '(order.ucaii_price*order.quantity)+order.fare' => 'order_all_ucaii_money',
            'order.mail_order_pay_state' => 'order_mail_order_pay_state',
            'team.id' => 'team_id',
            'team.product' => 'team_product',
        );
        $list = M('order')->where($where)->order(array('pay_time'=>'desc'))->field($field)->limit($Page->firstRow . ',' . $Page->listRows)
                ->join('inner join team on team.id=order.team_id')
                ->select();

        // 整理数据
        if ($list) {
            $user_ids = array();
            foreach ($list as &$v) {
                $user_ids[$v['order_user_id']] = $v['order_user_id'];
            }
            unset($v);
            $user_info = array();
            if ($user_ids) {
                $user_res = M('user')->where(array('id' => array('in', $user_ids)))->field('username,realname,mobile,id')->select();
                if ($user_res) {
                    foreach ($user_res as &$v) {
                        $user_info[$v['id']] = $v;
                    }
                    unset($v);
                }
            }
            $mail_team_delivery_time = C('MAIL_TEAM_DELIVERY_TIME');
            foreach ($list as &$v) {
                // 用户名称
                $v['user_username'] = ternary($user_info[$v['order_user_id']]['username'], '');
                $v['user_realname'] = ternary($user_info[$v['order_user_id']]['realname'], '');
                $v['user_mobile'] = ternary($user_info[$v['order_user_id']]['mobile'], '');

                // 购买详情
                $v['pay_detail'] = "总共购买{$v['order_quantity']}份";
                $order_optional_model = json_decode(ternary($v['order_optional_model'], ''), true);
                if ($order_optional_model) {
                    $oom_str = '';
                    foreach ($order_optional_model as $oom) {
                        $oom_str .= "{$oom['name']} X {$oom['num']}份<br/>";
                    }
                    $v['pay_detail'] = "{$v['pay_detail']}<br/>$oom_str";
                }

                // 送货时间
                $v['order_delivery_time'] = ternary($mail_team_delivery_time[$v['order_delivery_time']], '用户未选择！');

                // 支付时间
                $v['order_pay_time'] = date('Y-m-d H:i:s', $v['order_pay_time']);

                // 地址处理
                $address_res = @json_decode($v['order_address'], true);
                if ($address_res) {
                    $v['order_address'] = "联系人：{$address_res['name']}<br>联系电话：{$address_res['mobile']}<br>邮编：{$address_res['zipcode']}<br>区域：{$address_res['province']}{$address_res['area']}{$address_res['city']}<br>详细地址：{$address_res['street']}";
                }
            }
        }
        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 点击发货
     */
    public function orderDeliverGoodsView() {
        $order_id = I('get.order_id');
        $action = I('get.action','delivergoods','trim');
        $field = array(
            'order.id' => 'order_id',
            'order.optional_model' => 'order_optional_model',
            'order.user_id' => 'order_user_id',
            'order.address' => 'order_address',
            'order.address_id' => 'order_address_id',
            'order.delivery_time' => 'order_delivery_time',
            'order.pay_time' => 'order_pay_time',
            'order.quantity' => 'order_quantity',
            'order.mail_order_pay_state' => 'order_mail_order_pay_state',
            'order.team_id' => 'order_team_id',
            'order.remark' => 'order_remark',
            'order.express_id' => 'order_express_id',
            'order.express_no' => 'order_express_no',
        );
        $where = array(
            'id' => $order_id,
        );
        if($action=='delivergoods'){
            $where['state'] = 'pay';
        }
        $order_info = M('order')->where($where)->field($field)->find();
        
        $mail_team_delivery_time = C('MAIL_TEAM_DELIVERY_TIME');
        $user_info = M('user')->where(array('id' => $order_info['order_user_id']))->field('id,username,realname,mobile')->find();
        // 用户名称
        $order_info['user_username'] = ternary($user_info['username'], '');
        $order_info['user_realname'] = ternary($user_info['realname'], '');
        $order_info['user_mobile'] = ternary($user_info['mobile'], '');

        // 商品名称
        $order_info['team_product'] = M('team')->where(array('id' => $order_info['order_team_id']))->getField('product');

        // 购买详情
        $order_info['pay_detail'] = "总共购买{$order_info['order_quantity']}份";
        $order_optional_model = json_decode(ternary($order_info['order_optional_model'], ''), true);
        if ($order_optional_model) {
            $oom_str = '';
            foreach ($order_optional_model as $oom) {
                $oom_str .= "{$oom['name']} X {$oom['num']}份 <br/>";
            }
            $order_info['pay_detail'] = "{$order_info['pay_detail']}<br/>$oom_str";
        }

        // 送货时间
        $order_info['order_delivery_time'] = ternary($mail_team_delivery_time[$order_info['order_delivery_time']], '用户未选择！');

        // 支付时间
        $order_info['order_pay_time'] = date('Y-m-d H:i:s', $order_info['order_pay_time']);

        // 地址处理
        $address_res = @json_decode($order_info['order_address'], true);
        if ($address_res) {
            $order_info['order_address'] = "联系人：{$address_res['name']}<br>联系电话：{$address_res['mobile']}<br>邮编：{$address_res['zipcode']}<br>区域：{$address_res['province']}{$address_res['area']}{$address_res['city']}<br>详细地址：{$address_res['street']}";
        }
        // 获取快递类型
        $express_res = $this->_getCategoryList('express');
        
        if(isset($order_info['order_express_id']) && trim($order_info['order_express_id'])){
            $order_info['order_express_name'] = ternary($express_res[$order_info['order_express_id']]['name'], '');
        }

        $data = array(
            'action' => $action,
            'order' => $order_info,
            'express_res' => $express_res,
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 处理发货
     */
    public function doOrderDeliverGoods() {
        $order_id = I('post.order_id', '', 'trim');
        $express_id = I('post.express_id', '', 'trim');
        $express_no = I('post.express_no', '', 'trim');

        if (!$order_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '订单id不能为空！'));
        }
        if (!$express_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择快递类型！'));
        }
        if (!$express_no) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请输入快递单号！'));
        }

        $where = array('id' => $order_id);
        $order_res = M('order')->where($where)->field('user_id,state,mail_order_pay_state,team_id,express_id,express_no,mobile')->find();
        if (!$order_res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '订单不存在！'));
        }
        if (!isset($order_res['state']) || trim($order_res['state']) != 'pay') {
            $this->ajaxReturn(array('code' => -1, 'error' => '该订单未支付,不能发货！'));
        }
        $team_res = M('team')->where(array('id' => $order_res['team_id']))->field('team_type,product')->find();
        if (isset($team_res['team_type']) && $team_res['team_type'] != 'goods') {
            $this->ajaxReturn(array('code' => -1, 'error' => '该订单不是邮购类,不能发货！'));
        }

        $data = array(
            'express_id' => $express_id,
            'express_no' => $express_no,
            'partner_deliver_time' => time(),
            'mail_order_pay_state' => 1,
        );
        $res = M('order')->where($where)->save($data);
        if ($res === false) {
            $this->ajaxReturn(array('code' => -1, 'error' => '发货失败！'));
        }
        
        if(isset($order_res['mobile']) && trim($order_res['mobile']) && checkMobile($order_res['mobile'])){
            $sendSms = new \Common\Org\sendSms();
            $content = "您购买的[{$team_res['product']}]已发货，可在客户端我的订单中查看物流信息,";
            $sendSms->sendMsg($order_res['mobile'], $content);
        }
        
        
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 查看物流
     */
    public function orderLogisticsView() {
        $order_id = I('get.order_id');
        $field = array(
            'order.id' => 'order_id',
            'order.mail_order_pay_state' => 'mail_order_pay_state',
            'order.express_id' => 'express_id',
            'order.express_no' => 'express_no',
        );
        $where = array(
            'id' => $order_id,
            'state' => 'pay',
        );
        $order_res = M('order')->where($where)->field($field)->find();
        $express_res = $this->_getCategoryList('express');
        $type = ternary($express_res[$order_res['express_id']]['ename'], '');
        $express_query = new \Common\Org\ExpressQuery();
        $data = array();
        $res = $express_query->express_query($type, $order_res['express_no']);
        if (isset($res['status']) && $res['status'] == 200 && isset($res['data'])) {
            $data = $res['data'];
        }
        
         $state = '未发货';
        if(intval($order_res['mail_order_pay_state'])==1){
            $state='待收货';
        }
        if(intval($order_res['mail_order_pay_state'])==2){
            $state='已收货';
        }


        $r_data = array(
            'state' => $state,
            'express_name' => ternary($express_res[$order_res['express_id']]['name'], ''),
            'express_no' => $order_res['express_no'],
            'list' => $data,
        );

        $this->assign($r_data);
        $this->display();
    }

}
