<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;

/**
 * 客服模块模块
 * Class TeamController
 * @package Admin\Controller
 */
class CustomerServiceController extends CommonController {

    private $order_tn_type = array(
        'original_road_back' => '原路退',
        'retreat_balance' => '退至青团余额',
    );
    private $delivery_type = array(
        'coupon' => '青团券',
        'voucher' => ' 商户券',
        // 'express' => ' 邮购',
        'thirdpart' => ' 第三方码'
    );
    private $refund_type = array(
        'norefund' => '审核未通过',
        'credit' => '退款到账户余额',
        'online' => '其他途径已退款',
    );
    private $paystate = array(
        'unpay' => '未付款',
        'pay' => '已付款',
    );
    private $option_state = array(
        'Y' => '已消费',
        'N' => '未消费',
        'E' => '已过期',
    );

    /**
     * 第三方退款方式
     * @var type 
     */
    private $third_party_refund_type = array(
        //支付宝 alipay|aliwap|aliapp|pcalipay|wapalipay
        'alipay' => 'HTML',
        'aliwap' => 'HTML',
        'aliapp' => 'HTML',
        'pcalipay' => 'HTML',
        'wapalipay' => 'HTML',
        //财付通 tenpay|tenwap|tenapp|pctenpay|waptenpay  
        'tenapp' => 'http://mch.tenpay.com/',
        // 财付通pc
        'pctenpay' => true,
        'tenpay' => true,
        // 财付通 wap
        'tenwap' => true,
        'waptenpay' => true,
        // 财付通 微信
        'wechatpay' => true,
        'wxpay' => true,
        // 微信公众号 wapwechatpay|pcwxpaycode
        'wapwechatpay' => true,
        'pcwxpaycode' => true,
        // 全民付 chinabank|umspay|wapumspay
        'chinabank' => 'http://service.chinaums.com/uis/uisWebLogin/login',
        'umspay' => 'http://service.chinaums.com/uis/uisWebLogin/login',
        'wapumspay' => 'http://service.chinaums.com/uis/uisWebLogin/login',
        // 银联 unionpay|wapunionpay
        'unionpay' => true,
        'wapunionpay' => true,
        // 连连
        'lianlianpay' => true,
    );

    public function index() {
        redirect(U('CustomerService/refund'));
    }

    public function refund() {
        $order_service = I('get.order_service', '', 'trim');
        $order_tn = I('get.order_tn', '', 'trim');
        $username = I('get.username', '', 'trim');
        $team_type = I('get.team_type', '', 'trim');

        // 查询条件
        $where = array(
            'order.state' => 'pay',
            'order.allowrefund' => 'Y',
            'order.rstate' => 'askrefund',
            'order.team_id' => array('GT', 0),
        );
        if (trim($order_service)) {
            $order_service_arr = @explode('|', $order_service);
            $where['order.service'] = array('IN', $order_service_arr);
        }
        
        // 订单类型，默认非邮购
        $where['team.team_type'] = array('neq','goods');
        if($team_type=='goods'){
             $where['team.team_type'] = 'goods';
        }
        if (trim($order_tn)) {
            if (trim($order_tn) == 'original_road_back') {
                $where['order.tn'] = '原路退';
            } else if (trim($order_tn) == 'retreat_balance') {
                $where['order.tn'] = array('like', '退至青团%');
            }
        }
        if (trim($username)) {
            $where['_string'] = "user.username LIKE '%{$username}%' OR user.ID LIKE '%{$username}%' OR order.id LIKE '{$username}%' OR order.pay_id LIKE '%{$username}%'";
        }

        $order = M('order');
        $count = $order->where($where)
                ->join('INNER JOIN user ON user.id=`order`.user_id')
                ->join('INNER JOIN team ON team.id=`order`.team_id')
                ->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'order.id' => 'order_id',
            'order.service' => 'order_service',
            'order.quantity' => 'order_quantity',
            'order.origin' => 'order_origin',
            'order.retime' => 'order_retime',
            'order.pay_id' => 'order_pay_id',
            'order.tn' => 'order_tn',
            'team.id' => 'team_id',
            'team.product' => 'team_product',
            'team.city_id' => 'team_city_id',
            'team.delivery' => 'team_delivery',
            'user.username' => 'user_username',
            'user.mobile' => 'user_mobile',
            'user.id' => 'user_id',
            'user.username' => 'user_username',
            'user.email' => 'user_email',
            'team.city_id' => 'team_city_id',
            'order.id' => 'order_id',
            'order.id' => 'order_id',
        );
        $list = $order->where($where)->field($field)->order(array('order.retime' => 'desc'))
                ->join('INNER JOIN user ON user.id=`order`.user_id')
                ->join('INNER JOIN team ON team.id=`order`.team_id')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();

        // 处理数据
        $city_res = $this->_getCategoryList('city');
        if ($list) {//
            foreach ($list as &$v) {
                $v['team_city_name'] = ternary($city_res[$v['team_city_id']]['name'], '');
                $v['team_delivery_name'] = ternary($this->delivery_type[$v['team_delivery']], '');
                $v['order_service_name'] = order_service($v['order_service']);
                $v['expire_time'] = strtotime('+3 day ' . date('Y-m-d H:i:s', ternary($v['order_retime'], time())));
                $v['is_send_sms'] = '0';
                if (isset($v['user_mobile']) && checkMobile($v['user_mobile'])) {
                    $v['is_send_sms'] = '1';
                }
                // 判断是否显示第三方退款
                $v['is_show_third_party_refund'] = '1';
                if (!isset($v['order_service']) || trim($v['order_service']) == 'credit' || trim($v['order_service']) == 'cash' || strpos($v['order_tn'], '退至青团') !== false) {
                    $v['is_show_third_party_refund'] = '0';
                }
                if (!isset($this->third_party_refund_type[$v['order_service']])) {
                    $v['is_show_third_party_refund'] = '0';
                }
                // 购物车付款 也不能自动退款
                if (strpos($v['order_pay_id'], 'cart_') !== false) {
                    $v['is_show_third_party_refund'] = '0';
                }

                // 第三方地址跳转链接
                $v['third_party_refund_url'] = '';
                if (isset($this->third_party_refund_type[$v['order_service']]) && strpos($this->third_party_refund_type[$v['order_service']], 'http') !== false) {
                    $v['third_party_refund_url'] = $this->third_party_refund_type[$v['order_service']];
                }
                if (isset($this->third_party_refund_type[$v['order_service']]) && strpos($this->third_party_refund_type[$v['order_service']], 'HTML') !== false) {
                    $v['third_party_refund_url'] = U('CustomerService/doThirdPartyRefund', array('order_id' => $v['order_id']));
                }
            }
            unset($v);
        }

        $data = array(
            'order_service' => $order_service,
            'username' => $username,
            'order_tn' => $order_tn,
            'team_type' => $team_type,
            'order_tn_type' => $this->order_tn_type,
            'service_type' => $this->service_type,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($data);
        $this->display();
    }

    public function viewRefund() {
        $order_id = I('get.order_id', '', 'strval');
        if (!trim($order_id)) {
            $this->redirect_message(U('CustomerService/refund'), array('error' => base64_encode('订单id不能为空！')));
        }

        $where = array(
            'order.id' => $order_id,
            'order.state' => 'pay',
            'order.allowrefund' => 'Y',
            'order.rstate' => 'askrefund',
        );
        $order = M('order');
        $field = array(
            'order.id' => 'order_id',
            'order.state' => 'order_state',
            'order.quantity' => 'order_quantity',
            'order.credit' => 'order_credit',
            'order.service' => 'order_service',
            'order.money' => 'order_money',
            'order.origin' => 'order_origin',
            'order.pay_time' => 'order_pay_time',
            'order.mobile' => 'order_mobile',
            'order.adminremark' => 'order_adminremark',
            'order.rereason' => 'order_rereason',
            'order.retime' => 'order_retime',
            'order.dorefund' => 'order_dorefund',
            'order.yuming' => 'order_yuming',
            'order.pay_id' => 'order_pay_id',
            'order.tn' => 'order_tn',
            'order.card' => 'order_card',
            'order.card_id' => 'order_card_id',
            'order.express' => 'order_express',
            'order.optional_model' => 'order_optional_model',
            'order.fare' => 'order_fare',
            'user.mobile' => 'user_mobile',
            'user.username' => 'user_username',
            'user.email' => 'user_email',
            'team.id' => 'team_id',
            'team.product' => 'team_product',
            'team.delivery' => 'team_delivery',
        );
        $orderRes = $order->field($field)->where($where)
                        ->join('INNER JOIN user ON user.id=`order`.user_id')
                        ->join('INNER JOIN team ON team.id=`order`.team_id')->find();
        if (!$orderRes) {
            $this->redirect_message(U('CustomerService/refund'), array('error' => base64_encode('订单信息不能为空！')));
        }

        // 团购券
        $orderRes['coupon_list'] = array();
        if (isset($orderRes['team_delivery']) && trim($orderRes['team_delivery']) == 'coupon') {
            $field = array('id', 'consume');
            $coupon_list = M('coupon')->where(array('order_id' => $order_id))->field()->select();
            // 处理数据

            if ($coupon_list) {
                foreach ($coupon_list as &$v) {
                    $v['consume_name'] = ternary($this->option_state[$v['consume']], '');
                }
                unset($v);
                $orderRes['coupon_list'] = $coupon_list;
            }
        }

        // 整理数据
        $orderRes['order_adminremark'] = htmlspecialchars($orderRes['order_adminremark']);
        $orderRes['order_state_name'] = ternary($this->paystate[$orderRes['order_state']], '未知');
        $orderRes['order_yuming_name'] = order_from($orderRes['order_yuming']);
        $orderRes['order_service_name'] = '';
        if (isset($orderRes['order_service']) && trim($orderRes['order_service']) != 'credit') {
            $orderRes['order_service_name'] = order_service($orderRes['order_service'], '第三方支付');
        }
        if (isset($orderRes['order_express']) && $orderRes['order_express'] == 'Y') {
            $order_optional_model = $orderRes['order_optional_model'] = @json_decode($orderRes['order_optional_model'], true);
            $orderRes['pay_detail'] = "总共购买{$orderRes['order_quantity']}份";
            if ($order_optional_model) {
                $oom_str = '';
                foreach ($order_optional_model as $oom) {
                    $oom_str .= "{$oom['name']} : {$oom['num']}份; ";
                }
                $orderRes['pay_detail'] = "{$orderRes['pay_detail']}; $oom_str";
            }
        }

        // 代金券
        if (!isset($orderRes['order_card']) || $orderRes['order_card'] <= 0) {
            if (isset($orderRes['order_card_id']) && trim($orderRes['order_card_id'])) {
                $orderRes['order_card'] = M('card')->where(array('id' => $orderRes['order_card_id']))->getField('credit');
            }
        }

        $data = array(
            'order' => $orderRes,
            'refund_type' => $this->refund_type,
        );

        $this->assign($data);
        $this->display();
    }

    // 修改备注
    public function doRefund() {
        $action = I('get.action', '', 'trim');
        $order_id = I('get.order_id', '', 'trim');
        $action = strtolower(trim($action));
        if (!$action) {
            $this->ajaxReturn(array('code' => -1, 'error' => '行为不能为空！'));
        }
        if (!$order_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '操作的订单id不能为空！'));
        }
        $now_time = time();
        $order = M('order');
        if ($action == 'adminremark') {
            $admin_remark = I('post.remark', '', 'trim');
            if (!trim($admin_remark)) {
                $this->ajaxReturn(array('code' => -1, 'error' => '备注不能为空！'));
            }
            $now_time = date('Y-m-d H:i:s', $now_time);
            $data = array(
                'adminremark' => "{$admin_remark} 【{$this->user['username']}】 {$now_time}",
            );
            $res = $order->where(array('id' => $order_id))->save($data);
            if (!$res) {
                $this->ajaxReturn(array('code' => -1, 'error' => '备注设置失败！'));
            }
            $this->addOperationLogs("操作：退款修改备注，备注内容：" . htmlspecialchars($data['adminremark']));
            $this->ajaxReturn(array('code' => 0));
            exit;
        }

        // 退款处理
        $refund_type = I('post.refund_type', '', 'trim');
        $refund_type = strtolower($refund_type);
        $coupon_ids = I('post.coupon_ids', '', 'trim');
        if (!trim($refund_type)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择退款方式！'));
        }
        if (!isset($this->refund_type[$refund_type])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '非法处理方式！'));
        }
        $orderRes = $order->where(array('id' => $order_id))->find();
        if (!$orderRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '要处理的订单不存在！'));
        }
        $team = M('team');
        $field = 'delivery';
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->field($field)->find();
        if (!$orderRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该订单所购买的团单不存在！'));
        }

        $success = '';
        switch ($refund_type) {
            case 'norefund':
                $res = $order->where(array('id' => $order_id))->save(array('rstate' => 'norefund'));
                if ($res === false) {
                    $this->ajaxReturn(array('code' => -1, 'error' => '该申请未通过设置失败！'));
                }
                $success = '申请未通过设置成功！';
                break;
            case 'credit':
                if (isset($teamRes['delivery']) && $teamRes['delivery'] == 'coupon' && !trim($coupon_ids)) {
                    $this->ajaxReturn(array('code' => -1, 'error' => '请选择券号！'));
                }
                $res = $this->__creditRefund($orderRes, $coupon_ids);
                if (isset($res['error'])) {
                    $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
                }
                $success = '退款到账户余额成功！';
                break;
            case 'online':
                if (isset($teamRes['delivery']) && $teamRes['delivery'] == 'coupon' && !trim($coupon_ids)) {
                    $this->ajaxReturn(array('code' => -1, 'error' => '请选择券号！'));
                }
                $res = $this->__otherRefund($orderRes, $coupon_ids);
                if (isset($res['error'])) {
                    $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
                }
                $success = '其他途径已退款操作成功！';
                break;
            default:
                $this->ajaxReturn(array('code' => -1, 'error' => '非法处理方式！'));
                break;
        }

        $this->addOperationLogs("操作：退款处理，order_id:{$order_id},处理方式：{$this->refund_type[$refund_type]}[$refund_type],券号id：[$coupon_ids],结果：$success");
        $this->ajaxReturn(array('code' => 0, 'success' => $success));
    }
    
    /**
     * 获取 退至余额自动备注
     * @param type $orderRes
     * @param type $refund_mark
     * @param type $data
     * @return string
     */
    private function getCreditRefundOrderadminremark($orderRes, $money = '0.00',$couponIds=array()) {
        if(is_array($couponIds)){
            $couponIds = implode(',', $couponIds);
        }
        $adminremark = "{$orderRes['adminremark']}.\r\n";
        $adminremark .= "#余额退款自动备注#\r\n";
        $adminremark .= " > 向用户余额中退款 {$money}       \r\n";
        if(trim($couponIds)){
            $adminremark .= " > 退款券号：[ {$couponIds} ]      \r\n";
        }
        $adminremark .= " > 操作员信息：{$this->user['username']}[{$this->user['id']}],操作时间: ". date('Y-m-d H:i:s')."\r\n";
        $adminremark .= "#余额退款结束#\r\n";
        return $adminremark;
    }

    /**
     * 余额退款
     */
    private function __creditRefund($orderRes = array(), $couponIds = array()) {
        if (!$orderRes) {
            return array('error' => '该订单不存在！');
        }
        if (is_string($couponIds)) {
            $couponIds = @explode(',', $couponIds);
        }
//        if (!$couponIds) {
//            return array('error' => '请选择团券号！');
//        }
        if (!isset($orderRes['state']) || $orderRes['state'] != 'pay') {
            return array('error' => '该订单未付款，不能退款！');
        }
        if (!isset($orderRes['origin']) || $orderRes['origin'] <= 0) {
            return array('error' => '该订单总价格为0，不能退款！');
        }
        $user = M('user');
        $order = M('order');
        $coupon = M('coupon');
        $coupon_delete = M('coupon_delete');
        $team = M('team');
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();
        
        // 查询用户积分
        $userRes = $user->where(array('id' => $orderRes['user_id']))->field('score,money,id')->find();

        $where = array('id' => array('in', $couponIds), 'consume' => array('neq', 'Y'));
        $count = $coupon->where($where)->count();
        $state = (isset($orderRes['quantity']) && $orderRes['quantity'] == $count) ? 'berefund' : 'normal';
        $money = sprintf("%.2f",ternary($orderRes['origin'], 0)-ternary($orderRes['fare'],0));
        if (isset($teamRes['team_type']) && trim($teamRes['team_type'] != 'goods') && $orderRes['quantity'] != $count) {
            $money = sprintf("%.2f", ternary($orderRes['price'], 0) * $count);
        }
        if (isset($orderRes['card']) && $orderRes['card'] > 0) {
            if ($money > $orderRes['card']) {
                $money = sprintf("%.2f", $money - $orderRes['card']);
            }
        }
        $update_order_data = array(
            'adminremark' => $this->getCreditRefundOrderadminremark($orderRes,$money,$couponIds),
            'refund_ptime' => time(),
            'refund_etime' => time(),
        );
        
        $model = M();
        $model->startTrans();
        // 修改余额
        $res = $user->where(array('id' => $orderRes['user_id']))->setInc('money', $money);
        if (!$res) {
            $model->rollback();
            return array('error' => '余额更新失败！');
        }
        // 修改订单信息
        $res = $order->where(array('id' => $orderRes['id']))->save(array('state' => 'unpay','rstate' => 'berefund'));
        if (!$res) {
            $model->rollback();
            return array('error' => '订单更新失败！');
        }
        $res = $order->where(array('id' => $orderRes['id']))->save($update_order_data);
        if (!$res) {
            $model->rollback();
            return array('error' => '订单更新失败！');
        }
        // 添加流水
        $res = D('Team')->addFlowData($orderRes, $money, 'income', 'refund');
        if (!$res) {
            $model->rollback();
            return array('error' => '流水添加失败！');
        }
        // 删除团券
        $couponRes = $coupon->where($where)->select();
        if ($couponRes) {
            // 过滤空值
            foreach ($couponRes as &$v) {
                $v = array_filter($v);
            }
            unset($v);
            $res = $coupon_delete->addAll($couponRes);
            if (!$res) {
                $model->rollback();
                return array('error' => '券号删除失败！');
            }
            $res = $coupon->where($where)->delete();
            if (!$res) {
                $model->rollback();
                return array('error' => '券号删除失败！');
            }

            //第三方作废电子凭证
            threeValidCoupon($orderRes['partner_id'], $couponRes, 'invalid');
        }

        // 修改团单信息
        team_state($teamRes);
        if (isset($teamRes['state']) && trim($teamRes['state']) != 'failure') {
            $minus = isset($teamRes['conduser']) && trim($teamRes['conduser']) == 'Y' ? 1 : $count;
            $res = $team->where(array('id' => $orderRes['team_id']))->setDec('now_number', $minus);
            if (!$res) {
                $model->rollback();
                return array('error' => '团单信息更新失败！');
            }
        }
        
        // 修改用户积分
        $_order_res = $orderRes;
        $_order_res['origin'] = $money;
        $res = D('Team')->delCredit($_order_res,$userRes);
        if (!$res) {
            $model->rollback();
            return array('error' => '积分扣减失败！');
        }
        
        $model->commit();
        return array('success' => '余额退款成功！');
    }

    /**
     * 其他方式退款退款
     */
    private function __otherRefund($orderRes = array(), $couponIds = array()) {
        if (!$orderRes) {
            return array('error' => '该订单不存在！');
        }
        if (is_string($couponIds)) {
            $couponIds = @explode(',', $couponIds);
        }
//        if (!$couponIds) {
//            return array('error' => '请选择团券号！');
//        }
        if (!isset($orderRes['state']) || $orderRes['state'] != 'pay') {
            return array('error' => '该订单未付款，不能退款！');
        }
        if (!isset($orderRes['origin']) || $orderRes['origin'] <= 0) {
            return array('error' => '该订单总价格为0，不能退款！');
        }
        $order = M('order');
        $coupon = M('coupon');
        $coupon_delete = M('coupon_delete');
        $team = M('team');
        
        $where = array('id' => array('in', $couponIds), 'consume' => array('neq', 'Y'));
        $count = $coupon->where($where)->count();
        $state = (isset($orderRes['quantity']) && $orderRes['quantity'] == $count) ? 'berefund' : 'normal';
        $update_order_data = array(
            'state' => 'unpay',
            'rstate' => 'berefund',
            'refund_ptime' => time(),
            'refund_etime' => time(),
        );
        
        // 查询用户积分
        $userRes = M('user')->where(array('id' => $orderRes['user_id']))->field('score,money,id')->find();

        // 查询需要删除的券号
        $couponRes = $coupon->where($where)->select();
        
        // 查询团单相关信息
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();
        
        $model = M();
        $model->startTrans();

        // 修改订单信息
        $res = $order->where(array('id' => $orderRes['id']))->save($update_order_data);
        if (!$res) {
            $model->rollback();
            return array('error' => '订单更新失败！');
        }

        // 删除团券
        if ($couponRes) {
            // 过滤空值
            foreach ($couponRes as &$v) {
                $v = array_filter($v);
            }
            unset($v);
            $res = $coupon_delete->addAll($couponRes);
            if (!$res) {
                $model->rollback();
                return array('error' => '券号删除失败！');
            }
            $res = $coupon->where($where)->delete();
            if (!$res) {
                $model->rollback();
                return array('error' => '券号删除失败！');
            }

            //第三方作废电子凭证
            threeValidCoupon($orderRes['partner_id'], $couponRes, 'invalid');
        }

        // 修改团单信息
        
        team_state($teamRes);
        if (isset($teamRes['state']) && trim($teamRes['state']) != 'failure') {
            $minus = isset($teamRes['conduser']) && trim($teamRes['conduser']) == 'Y' ? 1 : $count;
            $res = $team->where(array('id' => $orderRes['team_id']))->setDec('now_number', $minus);
            if (!$res) {
                $model->rollback();
                return array('error' => '团单信息更新失败！');
            }
        }
        
        // 修改用户积分
        $_order_res = $orderRes;
        $res = D('Team')->delCredit($_order_res,$userRes);
        if (!$res) {
            $model->rollback();
            return array('error' => '积分扣减失败！');
        }

        $model->commit();
        return array('success' => '操作成功！');
    }

    /**
     * 第三方退款
     */
    public function viewThirdPartyRefund() {
        $order_id = I('get.order_id', '', 'strval');
        if (!trim($order_id)) {
            $this->redirect_message(U('CustomerService/refund'), array('error' => base64_encode('订单id不能为空！')));
        }

        $where = array(
            'order.id' => $order_id,
            'order.state' => 'pay',
            'order.allowrefund' => 'Y',
            'order.rstate' => 'askrefund',
        );
        $order = M('order');
        $field = array(
            'order.id' => 'order_id',
            'order.state' => 'order_state',
            'order.quantity' => 'order_quantity',
            'order.credit' => 'order_credit',
            'order.service' => 'order_service',
            'order.money' => 'order_money',
            'order.price' => 'order_price',
            'order.origin' => 'order_origin',
            'order.pay_time' => 'order_pay_time',
            'order.mobile' => 'order_mobile',
            'order.adminremark' => 'order_adminremark',
            'order.rereason' => 'order_rereason',
            'order.retime' => 'order_retime',
            'order.dorefund' => 'order_dorefund',
            'order.yuming' => 'order_yuming',
            'order.pay_id' => 'order_pay_id',
            'order.tn' => 'order_tn',
            'order.card' => 'order_card',
            'order.card_id' => 'order_card_id',
            'order.express' => 'order_express',
            'order.optional_model' => 'order_optional_model',
            'order.fare' => 'order_fare',
            'user.mobile' => 'user_mobile',
            'user.username' => 'user_username',
            'user.email' => 'user_email',
            'team.id' => 'team_id',
            'team.product' => 'team_product',
            'team.delivery' => 'team_delivery',
        );
        $orderRes = $order->field($field)->where($where)
                        ->join('INNER JOIN user ON user.id=`order`.user_id')
                        ->join('INNER JOIN team ON team.id=`order`.team_id')->find();
        if (!$orderRes) {
            $this->redirect_message(U('CustomerService/refund'), array('error' => base64_encode('订单信息不能为空！')));
        }

        // 团购券
        $orderRes['coupon_list'] = array();
        $coupon_unpay_count = 0;
        if (isset($orderRes['team_delivery']) && trim($orderRes['team_delivery']) == 'coupon') {
            $field = array('id', 'consume');
            $coupon_list = M('coupon')->where(array('order_id' => $order_id))->field($field)->select();
            // 处理数据
            if ($coupon_list) {
                foreach ($coupon_list as &$v) {
                    $v['consume_name'] = ternary($this->option_state[$v['consume']], '');
                    if ($v['consume'] != 'Y') {
                        $coupon_unpay_count++;
                    }
                }
                unset($v);
                $orderRes['coupon_list'] = $coupon_list;
            }
        }

        // 整理数据
        $orderRes['order_adminremark'] = htmlspecialchars($orderRes['order_adminremark']);
        $orderRes['order_state_name'] = ternary($this->paystate[$orderRes['order_state']], '未知');
        $orderRes['order_yuming_name'] = order_from($orderRes['order_yuming']);
        $orderRes['order_service_name'] = '';
        if (isset($orderRes['order_service']) && trim($orderRes['order_service']) != 'credit') {
            $orderRes['order_service_name'] = order_service($orderRes['order_service'], '第三方支付');
        }
        $team = D('Team');
        $orderRes['order_credit_money'] = $orderRes['order_credit'];
        $orderRes['order_third_party_money'] = $orderRes['order_money'];

        if (isset($orderRes['order_express']) && $orderRes['order_express'] == 'Y') {
            $order_optional_model = $orderRes['order_optional_model'] = @json_decode($orderRes['order_optional_model'], true);
            $orderRes['pay_detail'] = "总共购买{$orderRes['order_quantity']}份";
            if ($order_optional_model) {
                $oom_str = '';
                foreach ($order_optional_model as $oom) {
                    $oom_str .= "{$oom['name']} : {$oom['num']}份; ";
                }
                $orderRes['pay_detail'] = "{$orderRes['pay_detail']}; $oom_str";
            }
        }

        // 代金券
        if (!isset($orderRes['order_card']) || $orderRes['order_card'] <= 0) {
            if (isset($orderRes['order_card_id']) && trim($orderRes['order_card_id'])) {
                $orderRes['order_card'] = M('card')->where(array('id' => $orderRes['order_card_id']))->getField('credit');
            }
        }

        $team->setOrderRefundMoney($orderRes, $coupon_unpay_count);
        $data = array(
            'order' => $orderRes,
            'refund_type' => $this->refund_type,
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 执行第三方退款
     */
    public function doThirdPartyRefund() {
        ini_set("max_execution_time", 1800);
        $order_id = I('get.order_id', '', 'strval');
        if (!trim($order_id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '退款的订单id不能为空！'));
        }

        $where = array(
            'order.id' => $order_id,
            'order.state' => 'pay',
            'order.allowrefund' => 'Y',
            'order.rstate' => 'askrefund',
        );
        $order = M('order');
        $field = array(
            'order.id' => 'order_id',
            'order.state' => 'order_state',
            'order.quantity' => 'order_quantity',
            'order.credit' => 'order_credit',
            'order.service' => 'order_service',
            'order.money' => 'order_money',
            'order.price' => 'order_price',
            'order.origin' => 'order_origin',
            'order.pay_time' => 'order_pay_time',
            'order.mobile' => 'order_mobile',
            'order.adminremark' => 'order_adminremark',
            'order.rereason' => 'order_rereason',
            'order.retime' => 'order_retime',
            'order.dorefund' => 'order_dorefund',
            'order.yuming' => 'order_yuming',
            'order.pay_id' => 'order_pay_id',
            'order.trade_no' => 'order_trade_no',
            'order.express' => 'order_express',
            'order.fare' => 'order_fare',
        );
        $orderRes = $order->field($field)->where($where)->find();
        if (!$orderRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '退款的订单不存在！'));
        }
        $team = D('Team');
        $coupon_unpay_count = M('coupon')->where(array('order_id' => $orderRes['order_id'], 'consume' => array('neq', 'Y')))->count();
        $orderRes['order_credit_money'] = $orderRes['order_credit'];
        $orderRes['order_third_party_money'] = $orderRes['order_money'];
        $team->setOrderRefundMoney($orderRes, $coupon_unpay_count);

        $orderRes['order_service_name'] = '';
        if (isset($orderRes['order_service']) && trim($orderRes['order_service']) != 'credit') {
            $orderRes['order_service_name'] = order_service($orderRes['order_service'], '第三方支付');
        }

        $refund_data = array(
            'type' => ternary($orderRes['order_service'], ''),
            'pay_id' => ternary($orderRes['order_pay_id'], ''),
            'trade_no' => ternary($orderRes['order_trade_no'], ''),
            'yuming' => ternary($orderRes['order_yuming'], ''),
            'pay_time' => ternary($orderRes['order_pay_time'], ''),
            'total_money' => sprintf("%.2f", ternary($orderRes['order_money'], '0.00')),
            'refund_money' => sprintf("%.2f", ternary($orderRes['order_third_party_money'], '0.00')),
            'refund_no' => ternary($orderRes['order_trade_no'], '') . 'U' . ternary($orderRes['order_id'], ''),
        );

        // 操作日志
        $this->addOperationLogs("操作：第三方退款处理，order_id:{$order_id},退款平台：{$orderRes['order_service_name']}[{$orderRes['order_service']}]");

        $pay = new \Common\Org\Pay();
        $res = $pay->thirdPartyRefund($refund_data);
        if (isset($res['error'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
        }
        $res['op_admin_user'] = "{$this->user['username']}[{$this->user['id']}],操作时间:" . date('Y-m-d H:i:s');
        $u_res = $team->thirdPartyRefundUpdateData($res);
        if (isset($u_res['message'])) {
            $this->ajaxReturn(array('code' => 0));
        }
        $this->ajaxReturn(array('code' => -1, 'error' => '退款失败！'));
    }
    public function doThirdPartyRefundnew() {
        ini_set("max_execution_time", 1800);
        $order_id = I('get.order_id', '', 'strval');
        if (!trim($order_id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '退款的订单id不能为空！'));
        }

        $where = array(
            'order.id' => $order_id,
            'order.state' => 'pay',
            'order.allowrefund' => 'Y',
            'order.rstate' => 'askrefund',
        );
        $order = M('order');
        $field = array(
            'order.id' => 'order_id',
            'order.state' => 'order_state',
            'order.quantity' => 'order_quantity',
            'order.credit' => 'order_credit',
            'order.service' => 'order_service',
            'order.money' => 'order_money',
            'order.price' => 'order_price',
            'order.origin' => 'order_origin',
            'order.pay_time' => 'order_pay_time',
            'order.mobile' => 'order_mobile',
            'order.adminremark' => 'order_adminremark',
            'order.rereason' => 'order_rereason',
            'order.retime' => 'order_retime',
            'order.dorefund' => 'order_dorefund',
            'order.yuming' => 'order_yuming',
            'order.pay_id' => 'order_pay_id',
            'order.trade_no' => 'order_trade_no',
            'order.express' => 'order_express',
            'order.fare' => 'order_fare',
        );
        $orderRes = $order->field($field)->where($where)->find();
        if (!$orderRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '退款的订单不存在！'));
        }
        $team = D('Team');
        $coupon_unpay_count = M('coupon')->where(array('order_id' => $orderRes['order_id'], 'consume' => array('neq', 'Y')))->count();
        $orderRes['order_credit_money'] = $orderRes['order_credit'];
        $orderRes['order_third_party_money'] = $orderRes['order_money'];
        $team->setOrderRefundMoney($orderRes, $coupon_unpay_count);

        $orderRes['order_service_name'] = '';
        if (isset($orderRes['order_service']) && trim($orderRes['order_service']) != 'credit') {
            $orderRes['order_service_name'] = order_service($orderRes['order_service'], '第三方支付');
        }

        $refund_data = array(
            'type' => ternary($orderRes['order_service'], ''),
            'vid' => ternary($orderRes['order_trade_no'], ''),
            'order_no' => ternary($orderRes['order_pay_id'], ''),
            'yuming' => ternary($orderRes['order_yuming'], ''),
            'pay_time' => ternary($orderRes['order_pay_time'], ''),
            'total_money' => sprintf("%.2f", ternary($orderRes['order_money'], '0.00')),
            'refund_money' => sprintf("%.2f", ternary($orderRes['order_third_party_money'], '0.00')),
            'refund_no' => ternary($orderRes['order_trade_no'], '') . 'U' . ternary($orderRes['order_id'], ''),
            'origin' => sprintf("%.2f", ternary($orderRes['order_money'], '0.00')),
        );

        /*$refund_data = array(
            // 支付方式
            'type' => ternary($orderRes['pay_type'], ''),
            // 订单编号
            'order_no' => ternary($orderRes['order_no'], ''),
            // 支付时间
            'pay_time' => ternary($orderRes['pay_time'], ''),
            // 退款金额
            'origin' => sprintf("%.2f", ternary($orderRes['order_origin'], '0.00')),
            // 支付编号
            'vid' => ternary($orderRes['vid'], ''),
            // 退款编号
            'refund_no' => ternary($orderRes['vid'], '') . 'U' . ternary($orderRes['id'], ''),
        );*/

        // 操作日志
        $this->addOperationLogs("操作：第三方退款处理，order_id:{$order_id},退款平台：{$orderRes['order_service_name']}[{$orderRes['order_service']}]");

        $pay = new \Common\Org\PayNew();
        $res = $pay->thirdPartyRefund($refund_data);
        if (isset($res['error'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
        }
        $res['op_admin_user'] = "{$this->user['username']}[{$this->user['id']}],操作时间:" . date('Y-m-d H:i:s');
        file_put_contents('/tmp/return.log',var_export($res, true).'||',FILE_APPEND);
        $u_res = $team->thirdPartyRefundUpdateData($res);
        if (isset($u_res['message'])) {
            $this->ajaxReturn(array('code' => 0));
        }
        $this->ajaxReturn(array('code' => -1, 'error' => '退款失败！'));
    }
    
    /**
     * 青团账户余额批量退款处理
     */
    public function creditBatchRefund(){
        ini_set("max_execution_time", 1800);
        $order_id_str = I('post.order_ids', '', 'strval');
        if (!trim($order_id_str)) {
            $this->ajaxReturn(array('code' => -1,'error'=>'请选择批量退款的订单'));
        }
        $order_ids = explode(',', $order_id_str);
        $where = array(
            'order.id' => array('in',$order_ids),
            'order.state' => 'pay',
            'order.allowrefund' => 'Y',
            'order.rstate' => 'askrefund',
        );
        $order = M('order');
        
        $orderRes = $order->where($where)->select();
        if (!$orderRes) {
             $this->ajaxReturn(array('code' => -1,'error'=>'退款的订单不存在'));
        }
        $admin_logs = array();
        foreach($orderRes as $v){
            $coupon_res = M('coupon')->where(array('order_id' => $v['id'], 'consume' => array('neq', 'Y')))->field('id')->index('id')->select();
            $coupon_ids = array_keys($coupon_res);
            if(isset($v['express']) && $v['express']=='N' && !$coupon_ids){
                $admin_logs[]="订单号：{$v['id']},处理结果：该订单无可退款的券号";
                continue;
            }
            $res = $this->__creditRefund($v,$coupon_ids);
            if(isset($res['error']) && trim($res['error'])){
                $admin_logs[]="订单号：{$v['id']},处理结果：{$res['error']}";
                continue;
            }
            $log = "订单号：{$v['id']},处理结果：退款成功！";
            if($coupon_ids){
                $log = "{$log}退款券号：[".  implode(',', $coupon_ids)."]";
            }
            $admin_logs[]=$log;
        }
        
        // 写操作日志
        $this->addOperationLogs("操作：青团账户余额批量退款处理，".  implode('; ', $admin_logs));
        $this->ajaxReturn(array('code' => 0,'success'=>'余额批量操作退款成功！'));
    }

    /**
     * 支付宝批量退款
     */
    public function alipayBatchRefund() {
        ini_set("max_execution_time", 1800);
        $order_id_str = I('get.order_ids', '', 'strval');
        if (!trim($order_id_str)) {
            $this->redirect_message(U('CustomerService/refund'), array('error' => base64_encode('请选择批量退款的订单！')));
        }
        $order_ids = explode('_', $order_id_str);
        $where = array(
            'order.id' => array('in',$order_ids),
            'order.state' => 'pay',
            'order.allowrefund' => 'Y',
            'order.rstate' => 'askrefund',
        );
        $order = M('order');
        $field = array(
            'order.id' => 'order_id',
            'order.state' => 'order_state',
            'order.quantity' => 'order_quantity',
            'order.credit' => 'order_credit',
            'order.service' => 'order_service',
            'order.money' => 'order_money',
            'order.price' => 'order_price',
            'order.origin' => 'order_origin',
            'order.pay_time' => 'order_pay_time',
            'order.mobile' => 'order_mobile',
            'order.adminremark' => 'order_adminremark',
            'order.rereason' => 'order_rereason',
            'order.retime' => 'order_retime',
            'order.dorefund' => 'order_dorefund',
            'order.yuming' => 'order_yuming',
            'order.pay_id' => 'order_pay_id',
            'order.trade_no' => 'order_trade_no',
            'order.express' => 'order_express',
            'order.fare' => 'order_fare',
        );
        $orderRes = $order->field($field)->where($where)->select();
        if (!$orderRes) {
            $this->redirect_message(U('CustomerService/refund'), array('error' => base64_encode('退款的订单不存在！')));
        }
        $ali_pay_type = array('alipay','aliwap','aliapp','pcalipay','wapalipay');
        $refund_data = array();
        $team = D('Team');
        $coupon = M('coupon');
        $max_order_id=0;
        $order_ids_log = array();
        foreach($orderRes as $v){
            if(isset($v['order_service']) && !in_array($v['order_service'], $ali_pay_type)){
                continue;
            }
            $coupon_unpay_count = $coupon->where(array('order_id' => $v['order_id'], 'consume' => array('neq', 'Y')))->count();
            $v['order_credit_money'] = $v['order_credit'];
            $v['order_third_party_money'] = $v['order_money'];
            $team->setOrderRefundMoney($v, $coupon_unpay_count);
            

            $v['order_service_name'] = '';
            if (isset($v['order_service']) && trim($v['order_service']) != 'credit') {
                $v['order_service_name'] = order_service($v['order_service'], '第三方支付');
            }
            if($max_order_id < $v['order_id']){
                $max_order_id = $v['order_id'];
            }
            $order_ids_log[$v['order_id']] = $v['order_id'];
            $refund_data[] = array(
                'order_id'=>$v['order_id'],
                'trade_no' => ternary($v['order_trade_no'], ''),
                'total_money' => sprintf("%.2f", ternary($v['order_money'], '0.00')),
                'refund_money' => sprintf("%.2f", ternary($v['order_third_party_money'], '0.00')),
                'refund_no' => ternary($v['order_trade_no'], '') . 'U' . ternary($v['order_id'], ''),
            );
        };
        
        if(!$refund_data){
            $this->redirect_message(U('CustomerService/refund'), array('error' => base64_encode('退款的订单不存在或选择的订单不是支付宝支付的！')));
        }
        
        $data = array(
            'batch_max_order_id' =>$max_order_id,
            'batch_num' =>count($refund_data),
            'batch_data' =>$refund_data,
        );
        
        // 操作日志
        $this->addOperationLogs("操作：支付宝批量退款处理，order_id:[".  implode(',', $order_ids_log).']');

        $pay = new \Common\Org\Pay();
        $res = $pay->alipayBetchRefund($data);
        if (isset($res['error'])) {
            $this->redirect_message(U('CustomerService/refund'), array('error' => base64_encode($res['error'])));
        }
        return true;
    }

    /**
     * 用户提现列表
     */
    public function withDown() {
        $state = I('get.state', 'N', 'trim');
        $bank = I('get.bank', '', 'trim');
        $Model = M('user_pay');
        $where['state'] = $state;
        if ($bank == 'Y') {
            $where['bank'] = '支付宝';
        } else if ($bank == 'N') {
            $where['bank'] = array('neq', '支付宝');
        }
        $count = $Model->where($where)->count();
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $data = $Model->where($where)->order('id desc')->limit($limit)->select();
        if ($data) {
            foreach ($data as &$val) {
                $val['username'] = M('user')->where(array('id' => $val['user_id']))->getField('username');
            }
        }
        $displayWhere = array('state' => $state, 'bank' => $bank);
        $this->assign('display_where', $displayWhere);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 结算处理
     */
    public function doWithDown() {
        $id = I('get.id', 0, 'intval');
        $Model = M('user_pay');
        if ($id) {
            $data_info = $Model->find($id);
            if ($data_info) {
                $upData = array('id' => $id, 'state' => 'Y');
                $res = $Model->save($upData);
                if ($res) {
                    $this->redirect_message(U('CustomerService/withDown'), array('success' => base64_encode('结算成功')));
                } else {
                    $this->redirect_message(U('CustomerService/withDown'), array('error' => base64_encode('结算失败')));
                }
            } else {
                $this->redirect_message(U('CustomerService/withDown'), array('error' => base64_encode('结算信息不存在，无法结算！')));
            }
        } else {
            $this->redirect_message(U('CustomerService/withDown'), array('error' => base64_encode('结算ID不能为空！')));
        }
    }

}
