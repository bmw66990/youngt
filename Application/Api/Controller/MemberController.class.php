<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-09
 * Time: 11:57
 */

namespace Api\Controller;

class MemberController extends CommonController {

    protected $checkUser = true;
    protected $signCheck = true;
    /**
     * 获取用户信息
     */
    public function getUserInfo() {
        $model = D('User');
        $common_field = I('get.common_field', '', 'trim');
		
        $user = $model->info($this->uid, 'id,username,mobile,money,avatar,score,unid,sns');
        if (empty($user)) {
            $this->_writeDBErrorLog($user, $model, 'api');
            $this->outPut('', 1);
        }
        $user['avatar'] = getImagePath($user['avatar']);
        if ($common_field) {
            unset($user['unid'], $user['sns']);
            $this->outPut($user, 0);
        }
        $user['noreviewnum'] = D('Comment')->getUnReviewNum($this->uid);
        $this->_writeDBErrorLog($user['noreviewnum'], D('Comment'), 'api');
        $where = "user_id={$this->uid} && (state='pay' or (state='unpay' && rstate='berefund'))";
        $order = D('Order')->getTotal($where);
        $this->_writeDBErrorLog($order, D('Order'), 'api');
        $user['is_new'] = empty($order) ? 'N' : 'Y';
        $user['money'] = $user['money'] > 0 ? rtrim(rtrim($user['money'], '0'), '.') : '0';
        $this->outPut($user, 0);
    }

    /**
     * 获取订单
     */
    public function getOrders() {
        $model = D('Order');
        $state = I('get.state');
        $where = $this->setPage('id');
        $limit = $this->reqnum + 1;
        $sort = $this->sort;
        $data = array();
		
        $common_where = " AND is_display='Y' AND team_type <> 'cloud_shopping'";
        $field = 'id,team_id,state,rstate,price,quantity,create_time,allowrefund,origin,mail_order_pay_state,allowrefund,pay_time,retime,is_display';
        switch (strtoupper(trim($state))) {
            case 'UNPAY':   //未付款
                $where['_string'] = "user_id={$this->uid} AND state='unpay' AND rstate='normal' {$common_where}";
                $data = $model->getList($where, $sort, $limit, $field);
                break;
            case 'UNUSE':   //未使用
                $where = $this->setPage('pay_time');
                $sort = $this->sort;
                $where['_string'] = "user_id={$this->uid} AND state='pay' AND rstate='normal' {$common_where}";
                $data = $model->getIsUseOrders($this->uid, 'N', $where, $sort, $limit, $field);
                //$data = $model->getNewIsUseOrders($this->uid, 'N', I('get.lastid'), 'pay_time', $limit);
                break;
            case 'USED':    //已使用
                $where['_string'] = "user_id={$this->uid} AND state='pay' AND rstate='normal' {$common_where}";
                $data = $model->getIsUseOrders($this->uid, 'Y', $where, $sort, $limit, $field);
                //$data = $model->getNewIsUseOrders($this->uid, 'Y', I('get.lastid'), 'id', $limit);
                break;
            case 'UNREVIEW':    //未评价
                $where['_string'] = "user_id={$this->uid} AND state='pay' AND rstate='normal' {$common_where}";
                $data = $model->getIsReviewOrders($this->uid, 'N', $where, $sort, $limit, $field);
                break;
            case 'REVIEWED':    //已评价
                $where['_string'] = "user_id={$this->uid} AND state='pay' AND rstate='normal' {$common_where}";
                $data = $model->getIsReviewOrders($this->uid, 'Y', $where, $sort, $limit, $field);
                break;
            case 'REFUND':  //退款
                $where = $this->setPage('retime');
                $sort = $this->sort;
                $where['_string'] = "user_id={$this->uid}  {$common_where} AND ((state='pay' AND rstate='askrefund') OR (state='unpay' AND rstate='berefund'))";
                $data = $model->getList($where, $sort, $limit, $field);
                break;
            default:
                $where['_string'] = "user_id={$this->uid}  {$common_where}";
                $data = $model->getAllOrders($where, $sort, $limit, $field);
                break;
        }
        if ($data === false) {
            $this->_writeDBErrorLog($data, $model, 'api');
            $this->outPut('', 1005);
        }

        // 设置hasnext
        $this->setHasNext(false, $data, $this->reqnum);

        $teamModel = D('Team');
        $team = $teamModel->getOrderTeam($data);
        //数据组装
        $resData = array();
        foreach ($data as $val) {
            $val['price'] = $val['price'] > 0 ? rtrim(rtrim($val['price'], '0'), '.') : '0';
            $val['origin'] = $val['origin'] > 0 ? rtrim(rtrim($val['origin'], '0'), '.') : '0';
            $val['title'] = $team[$val['team_id']]['product'];
            $val['image'] = getImagePath($team[$val['team_id']]['image']);
            // 针对未使用的订单
            if ($state == '' || $state == 'UNUSE') {
                if ($val['use_num'] == 0) {
                    $val['refund_money'] = $val['origin'];
                } else {
                    $val['refund_money'] = sprintf('%.2f', $val['unuse_num'] * $val['price']);
                }
                $val['refund_money'] = $val['refund_money'] > 0 ? rtrim(rtrim($val['refund_money'], '0'), '.') : '0';
                unset($val['use_num'], $val['unuse_num']);
            }

            // 处理退款单
            if ($state == 'REFUND') {
                // 申请退款
                if ($val['state'] == 'pay' && $val['rstate'] == 'askrefund') {
                    $val['status'] = 'applyrefund';
                }
                // 已退款
                if ($val['state'] == 'unpay' && $val['rstate'] == 'berefund') {
                    $val['status'] = 'refund';
                }
            }
            $val['status'] = ternary($val['status'], strtolower($state));
            //unset($val['info'], $val['state'], $val['rstate']);
            // 是否邮购
            $val['is_goods'] = 'N';
            if (isset($team[$val['team_id']]['team_type']) && trim($team[$val['team_id']]['team_type']) == 'goods') {
                $val['is_goods'] = 'Y';
            }

            if ($val['state'] == 'pay' && $val['rstate'] == 'norefund' && !trim($val['status'])) {
                $val['status'] = 'unuse';
                $val['expire_time'] = ternary($team[$val['team_id']]['expire_time'], '');
            }

            $resData[] = $val;
        }
        $this->outPut($resData, 0);
    }

    /**
     * 订单详情
     */
    public function getOrderDetail() {
        $this->_checkblank('id');
		
        $id = I('get.id', 0, 'intval');
        $order = $this->_checkExistOrder($id);
        //team
        $team = D('Team')->info($order['team_id'], 'title,product,image,summary,team_price,market_price,team_type');
        $this->_writeDBErrorLog($team, D('Team'), 'api');
        $coupon = M('Coupon')->where('order_id=' . $id)->select();
        $this->_writeDBErrorLog($coupon, M('Coupon'), 'api');

        $team['image'] = getImagePath($team['image']);
        $data['id'] = $order['id'];
        $data['time'] = $order['create_time'];
        $data['mobile'] = $order['mobile'];
        $data['quantity'] = $order['quantity'];
        $data['money'] = $order['price'] > 0 ? rtrim(rtrim($order['price'], '0'), '.') : 0;
        $data['allowrefund'] = $order['allowrefund'];
        $data['mail_order_pay_state'] = $order['mail_order_pay_state'];
        $data['pay_time'] = $order['pay_time'];
        $data['team_id'] = $order['team_id'];
        $data['origin'] = $order['origin'];
        $data['fare'] = $order['fare'];
        $address = @json_decode($order['address'], true);
        $data['address'] = $address ? $address : (object) array();
        $optional_model = @json_decode($order['optional_model'], true);
        $data['optional_model'] = $optional_model ? $optional_model :  array();
        $data['is_goods'] = 'N';
        $data['state'] = $order['state'];
        $data['coupon'] = $coupon;
        $data['team'] = $team;
        if (isset($team['team_type']) && trim($team['team_type']) == 'goods') {
            $data['is_goods'] = 'Y';
        }

        if ($order['rstate'] == 'berefund' && $order['state'] == 'unpay') {
            $data['refund_state'] = 'Y';
        } else {
            $data['refund_state'] = 'N';
        }

        $data['status_text'] = getUserOrderState($order);

        if (!empty($coupon)) {
            // 退款信息
            $data['refund_num'] = 0;
            foreach ($coupon as $val) {
                if ($val['consume'] == 'N') {
                    $data['refund_num'] += 1;
                }
            }
            if ($data['refund_num'] == count($coupon)) {
                $data['refund_money'] = $order['origin'];
            } else {
                $data['refund_money'] = sprintf('%.2f', $data['refund_num'] * $order['price']);
            }
            $data['refund_money'] = $data['refund_money'] > 0 ? rtrim(rtrim($data['refund_money'], '0'), '.') : '0';
        }
        $this->outPut($data, 0);
    }

    /**
     * 判断订单是否存在
     * @param $id
     * @return mixed
     */
    protected function _checkExistOrder($id) {
        $order = D('Order')->isExistOrder($id, $this->uid);
		
        if (empty($order)) {
            $this->_writeDBErrorLog($order, M('Order'), 'api');
            $this->outPut('', 1003);
        }
        return $order;
    }

    /**
     * 团单评论
     */
    public function putReview() {
        $this->signCheck === false;
        $this->_checkblank(array('id', 'content', 'num'));
        $id = I('post.id', 0, 'intval');
		
        $order = $this->_checkExistOrder($id);

        if (!empty($_FILES)) {
            $res = $this->upload('img', 'pic', '', array('maxSize' => 1024 * 1024 * 5));
            ob_end_clean(); //之前有输出,清空掉
            if ($res) {
                $image = array();
                foreach ($res as $row) {
                    $image[] = getImagePath($row['newpath'] . '/' . $row['savename']);
                }
                $data['image'] = $image;
            } else {
                $this->outPut('', -1, '', '晒图失败');
            }
        }

        $data['content'] = I('post.content');
        $data['comment_num'] = I('post.num');
        //检测团单是否评价过
        $model = D('Comment');
        if (($res = $model->checkIsComment($order)) !== true) {
            $this->outPut('', $res['code']);
        }
        $res = $model->addComment($id, $this->uid, $data);
        if ($res) {
            $this->outPut($res, 0);
        } else {
            $this->_writeDBErrorLog($res, $model, 'api');
            $this->outPut('', 1004);
        }
    }

    /**
     * 获取团购券列表
     */
    public function getCoupons() {
        $where = $this->setPage('order_id');
		
        $map = array(
            'user_id' => $this->uid,
            'consume' => 'N',
                //'expire_time' => array('EGT', time()),
        );

        $map = array_merge($map, $where);
        $model = D('Coupon');
        $data = $model->getUserCoupons($map, 'o.id DESC', $this->reqnum + 1);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $model, 'api');
            $this->outPut('', 1005);
        }
        $this->setHasNext(false, $data, $this->reqnum);
        $this->outPut($data, 0);
    }

    /**
     * 获取团购券详情
     */
    public function getCouponDetail() {
        $this->_checkblank('id');
        $id = I('get.id');

        // 检测改订单如果未支付，则去第三方去查询，如支付成功，则修改库。
        D('Team')->appSynchronousPayCallbackHandle($id);

        $order = $this->_checkExistOrder($id, $this->uid);
        $team = D('Team')->info($order['team_id'], 'product,team_price,delivery');
        if ($team['delivery'] == 'thirdpart') {
            $this->outPut('', -1, '', '此订单生成商户券');
        }
        $where = $this->setPage('id');
        $where['order_id'] = $id;
        $this->reqnum=999;
        $model = D('Coupon');
        if ($order['state'] == 'unpay' && $order['rstate'] == 'berefund') {
            $data = $model->getAllList($where, $this->sort, $this->reqnum + 1, 'id,consume,expire_time');
            if (empty($data)) {
                $this->outPut('', -1, '', '该订单已退款成功');
            }
        } else {
            $data = $model->getList($where, $this->sort, $this->reqnum + 1, 'id,consume,expire_time');
            if ($data === false) {
                $this->_writeDBErrorLog($data, M('Coupon'), 'api');
                $this->outPut('', 1005);
            }
        }
        // 设置hasnext
        $this->setHasNext(false, $data, $this->reqnum);

        $vouchers = D('Card')->isCreateCard($order);
        if ($vouchers === true) {
            $isVouchers = 'Y';
        } else {
            $isVouchers = 'N';
        }
        $count = D('Coupon')->where('order_id=' . $id)->count();
        if ($data) {
            foreach ($data as &$v) {
                //设置状态 Y 已消费  N 未消费  A 申请退款  R 已退款
                if ($order['rstate'] == 'askrefund' && $v['consume'] == 'N') {
                    $v['consume'] = 'A';
                }

                if ($order['rstate'] == 'berefund' && $v['operation_type'] == 'refund') {
                    // 将从coupon_delete表中取出的数据设置为R
                    $v['consume'] = 'R';
                }
                
                if(isset($v['id']) && trim($v['id'])){
                  //  $v['id'] = implode(' ',str_split($v['id'],4));
                }

                $v['allowrefund'] = $order['allowrefund'];
                $v['title'] = ternary($team['product'], '');
                $v['price'] = ternary($team['team_price'], 0);
                $v['price'] = $v['price'] > 0 ? rtrim(rtrim($v['price'], '0'), '.') : '0';
                $v['order_id'] = $id;
                $v['is_vouchers'] = $isVouchers;
                $v['total'] = $count;

                unset($v['opertion_type']);
            }
            unset($v);
        }
        $this->outPut($data, 0);
    }

    /**
     * 申请退款
     */
    public function putApplyRefund() {
        $this->_checkblank('id');
        $id = I('param.id', 0, 'intval');
        $order = $this->_checkExistOrder($id);
        $this->_checkIsRefund($order);
        $model = D('Coupon');
        $where['order_id'] = $id;
        $data = $model->getList($where, $this->sort, '', 'id,consume,expire_time');
        $num = 0;
        foreach ($data as $row) {
            if ($row['consume'] == 'N') {
                $num += 1;
            }
        }
        if ($num == count($data)) {
            $res['refund_money'] = sprintf('%.2f', $order['origin']);
        } else {
            $res['refund_money'] = sprintf('%.2f', $num * $order['price']);
        }

        if ($order['card_id']) {
            if ($order['card'] > 0) {
                $card = $order['card'];
            } else {
                $card = M('Card')->where('id=' . $order['card_id'])->getField('credit');
            }
            $res['refund_money'] = bcsub($res['refund_money'], $card, 2);
        }
        if (isset($order['express']) && $order['express'] == 'Y') {
            $res['refund_money'] = sprintf('%.2f', $order['origin'] - $order['fare']);
        }

        $res['refund_money'] = $res['refund_money'] > 0 ? rtrim(rtrim($res['refund_money'], '0'), '.') : '0';
        $res['coupon'] = $data;
        $this->outPut($res, 0);
    }

    /**
     * 处理退款
     */
    public function putDoRefund() {
        $this->_checkblank(array('id', 'type', 'reason'));
        $id = I('param.id', 0, 'intval');
        $order = $this->_checkExistOrder($id);  //检测订单是否存在
        $this->_checkIsRefund($order);  //检测订单是否可以退款

        $reason = I('post.reason');
        $type = I('post.type');
        if (!in_array($type, array(1, 2))) {
            //参数错误
            $this->outPut('', 1001);
        }

        if ($type == 1) {
            $info = '退至青团账户';
        } else if ($type == 2) {
            $info = '原路退回';
        }
        $refundData = array(
            'tn' => $info,
            'retime' => time(),
            'rereason' => $reason,
            'rstate' => 'askrefund'
        );
        if ($rs = M('Order')->where('id=' . $id)->save($refundData)) {
            //提交成功
            //$data = D('Order')->info($id);
            $this->outPut('', 0);
        } else {
            $this->_wri0teDBErrorLog($rs, M('Order'), 'api');
            $this->outPut('', 1006);
        }
    }

    /**
     * 获取退款详情
     */
    public function getRefundDetail() {
        $this->_checkblank('id');
        $id = I('param.id', 0, 'intval');
        $order = $this->_checkExistOrder($id);
        $data = array();
		var_dump(aaa);
        if (($order['state'] == 'pay' && $order['rstate'] == 'askrefund') || ($order['state'] == 'unpay' && $order['rstate'] == 'berefund')) {
            // code
        } else {
            $this->outPut('', -1, '', '此订单不是退款订单');
        }

        $num = $order['quantity'];
        $data['is_goods'] = 'N';
        if ($order['express'] == 'Y') {
            $data['refund_money'] = sprintf('%.2f', $order['origin'] - $order['fare']);
            $data['is_goods'] = 'Y';
        } else {
            $map = array(
                'order_id' => $id,
                'user_id' => $this->uid,
                'consume' => 'N'
            );
            if ($order['state'] == 'pay') {
                $coupon = M('Coupon')->where($map)->getField('id', true);
            } else {
                $map['operation_type'] = 'refund';
                $coupon = M('CouponDelete')->where($map)->getField('id', true);
            }
            if ($order['quantity'] == count($coupon)) {
                $data['refund_money'] = sprintf('%.2f', $order['origin']);
            } else {
                $data['refund_money'] = sprintf('%.2f', $order['price'] * count($coupon));
            }
            $num = count($coupon);
        }

        if ($order['state'] == 'pay') {
            $data['status'] = 'applyrefund';
        } else {
            $data['status'] = 'refund';
        }

        if ($order['card_id']) {
            if ($order['card'] > 0) {
                $card = $order['card'];
            } else {
                $card = M('Card')->where('id=' . $order['card_id'])->getField('credit');
            }
            $data['refund_money'] = bcsub($data['refund_money'], $card, 2);
        }

        $data['refund_money'] = $data['refund_money'] > 0 ? rtrim(rtrim($data['refund_money'], '0'), '.') : '0';
        $data['coupon'] = implode(',', $coupon);
        $data['num'] = $num;
        $data['type'] = $order['tn'];
        $data['create_time'] = $order['create_time'];

        /* if(empty($order['refund_ptime'])) {
          $process_time  = $order['retime'] + 3 * 86400;
          $process_state = 'N';
          } else {
          $process_time  = $order['refund_ptime'];
          $process_state = 'Y';
          } */
        if (empty($order['refund_etime'])) {
            $success_time = $order['retime'] + 3 * 86400;
            $success_state = 'N';
            $success_info = '前完成';
        } else {
            $success_time = $order['refund_etime'];
            $success_state = 'Y';
            $success_info = '已完成';
        }
        $data['step'] = array(
            array(
                'title' => '申请退款',
                'info' => '已受理',
                'time' => date('Y-m-d H:i', $order['retime']),
                'detail' => '',
                'state' => 'Y'
            ),
            array(
                'title' => '退款成功',
                'info' => $success_info,
                'time' => date('Y-m-d H:i', $success_time),
                'detail' => '支付平台已操作回款，' . $data['refund_money'] . '元退款将在3-5个工作日内退至您的账户',
                'state' => $success_state
            )
        );
        $this->outPut($data, 0);
    }

    /**
     * 检查订单是否可退款
     * @param $order
     */
    protected function _checkIsRefund($order) {
        $res = D('Order')->checkIsRefund($order);
		var_dump(bbb);
        if (isset($res['error'])) {
            $this->outPut('', -1, '', $res['error']);
        }
    }

    /**
     * 申请提现
     */
    public function putApplyCash() {

        // 屏蔽余额提现功能！
        //$this->outPut(null, -1, null, '此功能没有开通！');

        $this->_checkblank(array('money', 'bank', 'account', 'uname'));
        $data = array(
            'bank' => I('post.bank'),
            'money' => I('post.money'),
            'account' => I('post.account'),
            'uname' => I('post.uname'),
        );

        $model = D('User');
        $user = $model->info($this->uid, 'id,money');
        //TODO 判断用户余额来源,如果是登陆获取,无法提现
        if (!isset($user['money']) || !isset($data['money']) || !trim($data['money']) || !trim($user['money']) || $user['money'] < $data['money']) {
            //提现金额不正确
            $this->outPut('', 1011);
        }

        if (($res = $model->applyCash($this->uid, $data))) {
            $this->outPut('', 0);
        }
        $this->_writeDBErrorLog($res, $model, 'api');
        $this->outPut('', 1012);
    }

    /**
     * 交易记录
     */
    public function getDealRecord() {
        $whereArr = $this->setPage('id');
        $where = array(
            'user_id' => $this->uid,
            'action' => array('in', 'daysign,buy,charge,refund,store,withdraw'),
        );
        $where = array_merge($where, $whereArr);
        $model = D('Flow');
        $data = $model->getList($where, $this->sort, $this->reqnum + 1, 'id,team_id,detail_id,action,money,create_time');
        if ($data === false) {
            //获取失败
            $this->_writeDBErrorLog($data, D('Flow'), 'api');
            $this->outPut('', 1005);
        }
        $this->setHasNext(false, $data, $this->reqnum);
        $arr = array(
            'daysign' => '每日签到',
            'buy' => '购买 - ',
            'charge' => '在线充值',
            'refund' => '退款记录',
            'store' => '线下充值',
            'withdraw' => '现金提现',
            'paycharge' => '购买充值',
            'bind' => '绑定微信'
        );
        $teamId = array();
        foreach ($data as $row) {
            if ($row['action'] == 'buy' || $row['action'] == 'paycharge') {
                if (intval($row['detail_id']) > 0) {
                    $teamId[] = intval($row['detail_id']);
                    continue;
                }
                if (intval($row['team_id']) > 0) {
                    $teamId[] = intval($row['team_id']);
                }
            }
        }
        if (!empty($teamId)) {
            $team = M('Team')->where('id in (' . implode(',', array_unique($teamId)) . ')')->getField('id,product', true);
            $this->_writeDBErrorLog($team, M('Team'), 'api');
        }

        //数据组织
        foreach ($data as $key => $val) {
            $val['money'] = $val['money'] > 0 ? rtrim(rtrim($val['money'], '0'), '.') : '0';
            if ($val['action'] == 'buy' || $val['action'] == 'paycharge') {
                if (!isset($team[$val['detail_id']])) {
                    $val['title'] = $arr[$val['action']] . ternary($team[$val['team_id']], '');
                } else {
                    $val['title'] = $arr[$val['action']] . ternary($team[$val['detail_id']], '');
                }
                $val['state'] = 'Y';
            } else {
                $val['title'] = $arr[$val['action']];
            }
            unset($val['detail_id'], $val['action']);
            $data[$key] = $val;
        }
        $this->outPut($data, 0);
    }

    /**
     * 提现记录
     */
    public function getApplyCashRecord() {
		
        $whereArr = $this->setPage('id');
        $where = array(
            'user_id' => $this->uid,
            'action' => 'withdraw',
        );
        $where = array_merge($where, $whereArr);
        $data = D('Flow')->getList($where, $this->sort, $this->reqnum + 1, 'id,money,create_time');
        if ($data === false) {
            //获取失败
            $this->_writeDBErrorLog($data, D('Flow'), 'api');
            $this->outPut('', 1005);
        }
        $this->setHasNext(false, $data, $this->reqnum);
        foreach ($data as &$row) {
            $row['money'] = $row['money'] > 0 ? rtrim(rtrim($row['money'], '0'), '.') : '0';
            $row['title'] = '现金提现';
        }
        unset($row);
        $this->outPut($data, 0);
    }

    /**
     * 修改头像
     */
    public function uploadPic() {
        $res = $this->upload('img', 'user', '', array('maxSize' => 1024 * 1024 * 5));
        if ($res) {
            $path = $res[0]['newpath'] . '/' . $res[0]['savename'];
            if ($rs = M('User')->where('id=' . $this->uid)->setField('avatar', $path)) {
                $this->outPut(getImagePath($path), 0);
            }
            $this->_writeDBErrorLog($rs, M('User'), 'api');
        }
        $this->outPut('', -1, '', $this->error);
    }

    /**
     * 修改用户名
     */
    public function putUpdateAccount() {
        $this->_checkblank('account');
        $model = D('User');
        $account = trim(I('post.account'));

        if ($model->checkAccount($account, $this->uid)) {
            //用户名已存在
            $this->outPut('', 2002);
        }

        $len = mb_strlen($account);
        if ($len > 30) {
            $this->outPut('', -1, '', '用户名长度过长');
        }

        if ($rs = $model->where('id=' . $this->uid)->setField('username', $account)) {
            $this->outPut('', 0);
        }
        $this->_writeDBErrorLog($rs, $model, 'api');
        $this->outPut('', 1016);
    }

    /**
     * 绑定手机
     */
    public function putUpdateMobile() {
        $this->_checkblank(array('mobile', 'action', 'captcha'));
        $mobile = I('post.mobile');
        $captcha = I('post.captcha');
        $action = I('post.action', '', 'strval');
        //2016.4.19 校验新接口
        $jy = I('post.jy', '');
        if($jy)
        {
            $jy=strtolower($jy);
        }else{
            $jy='old';
        }
        if (!checkMobile($mobile)) {
            $this->outPut('', -1, '', '手机号码格式错误');
        }
        if (!in_array($action, array('bindmobile', 'changemobile'))) {
            $this->outPut('', -1, '', '参数错误');
        }
        $model = D('User');
        $userRes = $model->isRegister(array('mobile' => trim($mobile)));
        if ($userRes) {
            $this->outPut('', -1, '', '该手机号已经绑定！不能重复绑定！');
        }
        $res = $model->checkMobileVcode($captcha, $mobile, $action,$jy);
        if (!$res) {
            $this->outPut('', -1, '', '手机绑定失败');
        }
        if ($rs = M('User')->where('id=' . $this->uid)->setField('mobile', $mobile)) {
            //修改成功
            $this->outPut('', 0);
        }
        $this->_writeDBErrorLog($rs, M('User'), 'api');
        $this->outPut('', 1015);
    }

    /**
     * 修改密码
     */
    public function putUpdatePwd() {
        $this->_checkblank(array('oldpwd', 'newpwd', 'renewpwd'));
        $model = D('User');
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

    /**
     * 绑定第三方登陆
     */
    public function putBindAccount() {
        $this->_checkblank(array('type', 'val'));
        $type = I('post.type');
        $val = I('post.val');
        $is_cover = I('post.is_cover', 0, 'intval');
        $model = D('User');
        $param = $this->_getBindAccountParam($type);
        $field = $param['field'];
        $info = $param['info'];
        $user = $model->info($this->uid);
        if (empty($user[$field])) {
            $res = $model->bindAccount($this->uid, $field, $val, $is_cover);
            if (isset($res['error'])) {
                $this->outPut('', -1, '', $res['error']);
            } else {
                if ($res) {
                    $this->outPut('', 0);
                } else {
                    $this->_writeDBErrorLog($res, $model, 'api');
                    $this->outPut('', -1, '', $info . '绑定失败');
                }
            }
        } else {
            $this->outPut('', -1, '', '账号已绑定' . $info . '账号');
        }
    }

    /**
     * 解绑第三方账号
     */
    public function putUnbindAccount() {
        $this->_checkblank('type');
        $type = I('post.type');
        $param = $this->_getBindAccountParam($type);
        $model = D('User');
        $field = $param['field'];
        $info = $param['info'];
        $user = $model->info($this->uid, 'id,' . $field);
        if (empty($user[$field])) {
            $this->outPut('', -1, '', '您的账号未绑定' . $info . '账号');
        }
        $res = $model->unBindAccount($this->uid, $field);
        if ($res) {
            $this->outPut('', 0);
        } else {
            $this->_writeDBErrorLog($res, $model, 'api');
            $this->outPut('', -1, '', $info . '解除绑定失败');
        }
    }

    /**
     * 获取第三方账号绑定类型
     * @param $type
     * @return array
     */
    protected function _getBindAccountParam($type) {
        switch (trim($type)) {
            case 'wx':
                $data = array(
                    'field' => 'unid',
                    'info' => '微信'
                );
                break;
            case 'qq':
                $data = array(
                    'field' => 'sns',
                    'info' => 'QQ'
                );
                break;
            default:
                $this->outPut('', -1, '', '非法参数');
        }
        return $data;
    }

    /**
     * 积分流水
     */
    public function getCredits() {
        $map = array(
            'user_id' => $this->uid,
            'create_time' => array('gt', strtotime('2014-08-20')),
        );
        $where = $this->setPage('id');
        $map = array_merge($map, $where);
        $model = D('Credit');
        $data = $model->getList($map, $this->sort, $this->reqnum + 1, 'id,score,create_time,action,detail_id');
        if (empty($data)) {
            $this->_writeDBErrorLog($data, D('Credit'), 'api');
            $this->outPut(array(), 0);
        }
        $this->setHasNext(false, $data, $this->reqnum);
        $action = array(
            'binding' => '绑定第三方登陆',
            'comment' => '购买评价',
            'exchange' => '积分换券',
            'lottery' => '抽奖',
            'register' => '注册会员',
            'pay' => '购买商品',
            'daysign' => '每日签到',
            'refund' => '退款',
            'score_goods' => '兑换商品'
        );

        $teamId = array();
        $points_team_ids = array();
        foreach ($data as $row) {
            if (($row['action'] == 'comment' || $row['action'] == 'pay' || $row['action'] == 'refund') && $row['detail_id']) {
                $teamId[$row['detail_id']] = $row['detail_id'];
                continue;
            }

            if ($row['action'] == 'score_goods' && $row['detail_id']) {
                $points_team_ids[$row['detail_id']] = $row['detail_id'];
                continue;
            }
        }
        $team = array();
        if (!empty($teamId)) {
            $team = M('Team')->where(array('id' => array('in', $teamId)))->getField('id,product', TRUE);
            $this->_writeDBErrorLog($team, M('Team'), 'api');
        }
        $points_team = array();
        if (!empty($points_team_ids)) {
            $points_team = M('points_team')->where(array('id' => array('in', $points_team_ids)))->getField('id,name', TRUE);
            $this->_writeDBErrorLog($team, M('Team'), 'api');
        }
        //组织数据
        foreach ($data as $key => $val) {
            if ($val['score'] > 0) {
                if ($val['action'] == 'comment' || $val['action'] == 'pay' || $val['action'] == 'refund') {
                    $val['detail'] = $action[$val['action']] . '：' . ternary($team[$val['detail_id']], '');
                } else if ($val['action'] == 'lottery') {
                    $val['detail'] = $action[$val['action']] . '获取';
                } else {
                    $val['detail'] = $action[$val['action']];
                }
                $val['score'] = '+' . $val['score'];
            } else {
                if ($val['action'] == 'score_goods') {
                    $val['detail'] = $action[$val['action']] . '：' . ternary($points_team[$val['detail_id']], '');
                } else {
                    $val['detail'] = $action[$val['action']] . '消耗';
                }
            }
            unset($val['action']);
            $data[$key] = $val;
        }
        //dump($data);
        $this->outPut($data, 0);
    }

    /**
     * 签到
     */
    public function putDaySign() {
        $model = D('User');
        if ($model->isDaySign($this->uid)) {
            //今日已签到
            $this->outPut('', 1018);
        }
        $res = $model->daySign($this->uid);
        if (empty($res)) {
            //失败
            $this->_writeDBErrorLog($res, $model, 'api');
            $this->outPut('', 1013);
        } else {
            $data['score'] = $model->where('id=' . $this->uid)->getField('score');
            $this->outPut($data, 0);
        }
    }

    /**
     * 获取成长值
     */
    public function getGrowth() {
        $growth = D('Flow')->getUserGrowth($this->uid);
        if ($growth === false) {
            $this->_writeDBErrorLog($growth, D('Flow'), 'api');
            $this->outPut('', 1005);
        }
        $this->outPut(array('growth' => $growth), 0);
    }

    /**
     * 获取成长值明细
     */
    public function getGrowthDetail() {
        $map = $this->setPage('id');
        $where['user_id'] = $this->uid;
        $where['action'] = array('in', 'buy,refund');
        $where = array_merge($where, $map);
        $data = D('Flow')->getUserGrowthList($where, $this->sort, $this->reqnum + 1);
        if ($data === false) {
            $this->_writeDBErrorLog($data, D('Flow'), 'api');
            $this->outPut('', 1005);
        }
        $this->setHasNext(false, $data, $this->reqnum);
        $this->outPut($data, 0);
    }

    /**
     * 获取站内信列表
     */
    public function getStationMails() {
        $map['user_id'] = $this->uid;
        $where = $this->setPage('id');
        $map = array_merge($map, $where);
        $model = D('StationMail');
        $data = $model->getList($map, $this->sort, $this->reqnum + 1);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $model, 'api');
            $this->outPut('', 1005);
        }
        $this->setHasNext(false, $data, $this->reqnum);

        $order = array();
        foreach ($data as $row) {
            if (!$row['type_id']) {
                continue;
            }
            switch ($row['type']) {
                case 'coupon':
                case 'review':
                    $order[] = $row['type_id'];
            }
        }

        if (!empty($order)) {
            $con = array(
                'id' => array('IN', array_unique($order))
            );
            $orderList = D('Order')->getAllOrders($con, '', '', 'id,team_id,state,rstate,price,quantity,origin,allowrefund,create_time');
            //$orderList = M('Order')->index('id')->field('id,team_id,price,quantity,create_time')->where($con)->select();
        }
        foreach ($data as &$row) {
            if ($row['type_id'] && in_array($row['type'], array('coupon', 'review'))) {
                if (isset($orderList[$row['type_id']])) {
                    unset($orderList[$row['type_id']]['state'], $orderList[$row['type_id']]['rstate']);
                    $orderList[$row['type_id']]['origin'] = $orderList[$row['type_id']]['origin'] > 0 ? rtrim(rtrim($orderList[$row['type_id']]['origin'], '0'), '.') : '0';
                    $row = array_merge($row, $orderList[$row['type_id']]);
                }
                $row['id'] = $row['type_id'];
            } else {
                $row['status'] = $row['type'];
            }
        }
        unset($row);

        $this->outPut($data, 0);
    }

    /**
     * 获取团单收藏
     */
    public function getCollects() {

        // 经纬度
        $lng = I('get.lng', '', 'trim');
        $lat = I('get.lat', '', 'trim');

        $map['user_id'] = $this->uid;
        $where = $this->setPage('id');
        $map = array_merge($map, $where);
        $model = D('Collect');
        $data = $model->getList($map, $this->sort, $this->reqnum + 1, 'id,team_id,create_time');
        if ($data === false) {
            $this->_writeDBErrorLog($data, $model, 'api');
            $this->outPut('', 1005);
        }
        $this->setHasNext(false, $data, $this->reqnum);
        if (!empty($data)) {
            $teamModel = D('Team');
            $team = $teamModel->getOrderTeam($data);
            //数据组装
            $partner_ids = array();
            $now_time = time();
            foreach ($data as &$val) {
                $val['product'] = $team[$val['team_id']]['title'];
                $val['title'] = $team[$val['team_id']]['product'];
                $val['now_number'] = $team[$val['team_id']]['now_number'];
                $val['team_price'] = $team[$val['team_id']]['team_price'];
                $val['team_price'] = $val['team_price'] > 0 ? rtrim(rtrim($val['team_price'], '0'), '.') : '0';
                $val['market_price'] = $team[$val['team_id']]['market_price'];
                $val['market_price'] = $val['market_price'] > 0 ? rtrim(rtrim($val['market_price'], '0'), '.') : '0';
                $val['team_type'] = $team[$val['team_id']]['team_type'];
                $val['end_time'] = $team[$val['team_id']]['end_time'];
                $val['begin_time'] = $team[$val['team_id']]['begin_time'];
                $val['now_time'] = strval($now_time); //market_price

                $val['image'] = getImagePath($team[$val['team_id']]['image']);
                $val['partner_id'] = $team[$val['team_id']]['partner_id'];
                $partner_ids[$team[$val['team_id']]['partner_id']] = $team[$val['team_id']]['partner_id'];
            }
            unset($val);
            if ($lat && $lng) {
                $partner_res = array();
                if ($partner_ids) {
                    $partner_res = M('partner')->where(array('id' => array('in', array_keys($partner_ids))))->field(array('id', 'long' => 'lng', 'lat'))->index('id')->select();
                }
                foreach ($data as &$val) {
                    if (isset($partner_res[$val['partner_id']])) {
                        $val['partner'] = $partner_res[$val['partner_id']];
                    }
                }
                unset($val);
                // 计算经纬度
                $teamModel->getTeamDistance($data, array('lng' => $lng, 'lat' => $lat));
            }
        }
        $this->outPut($data, 0);
    }

    /**
     * 获取抵金券
     */
    public function getCardList() {
        $id = I('get.id', '', 'strval');
        $order_id = I('get.order_id', 0, 'intval');
        if (!trim($id)) {
            $id = $this->uid;
        }

        $money = '';
        $card = D('Card');
        if (trim($order_id)) {
            $money = M('order')->where(array('id' => $order_id))->getField('origin');
            $money = $card->getMoneyUseRules($money);
        }
        $data = $card->getCardList($id, $money);
        if (!$data) {
            $data = array();
        }
        $this->outPut($data, 0);
    }

    /**
     * 订单确认收货
     */
    public function orderConfirmReceipt() {
        $order_id = I('post.order_id', '', 'trim');

        if (!$order_id) {
            $this->outPut(null, -1, null, '订单id不能为空！');
        }

        // 检测用户是否登录
        $this->check();

        if (!isset($this->uid) || !trim($this->uid)) {
            $this->outPut(null, -1, null, '用户未登录，不能点击确认收货！');
        }

        $order = D('Order');
        $res = $order->orderConfirmReceipt($order_id, $this->uid);
        if (isset($res['error']) && trim($res['error'])) {
            $this->outPut(null, -1, null, $res['error']);
        }
        $this->outPut(array(), 0);
    }

    /**
     * 订单物流查看
     */
    public function orderLogisticsView() {
        $order_id = I('post.order_id', '', 'trim');

        if (!$order_id) {
            $this->outPut(null, -1, null, '订单id不能为空！');
        }

        // 检测用户是否登录
        $this->check();

        if (!isset($this->uid) || !trim($this->uid)) {
            $this->outPut(null, -1, null, '用户未登录，不能查看物流！');
        }

        $where = array(
            'id' => $order_id,
            'rstate' => 'normal', //
        );
        $order_res = M('order')->where($where)->field('user_id,state,mail_order_pay_state,team_id,express_id,express_no')->find();
        if (!$order_res) {
            $this->outPut(null, -1, null, '订单不存在！');
        }
        if (!isset($order_res['user_id']) || intval($order_res['user_id']) !== intval($this->uid)) {
            $this->outPut(null, -1, null, '该订单不是自己的订单，不能查看物流！');
        }
        if (!isset($order_res['state']) || trim($order_res['state']) != 'pay') {
            $this->outPut(null, -1, null, '该订单未支付，不能查看物流！');
        }
        if (!isset($order_res['express_id']) || !trim($order_res['express_id'])) {
            $this->outPut(null, -1, null, '该订单没有寄件信息，不能查看物流！');
        }
        if (!isset($order_res['express_no']) || !trim($order_res['express_no'])) {
            $this->outPut(null, -1, null, '该订单没有寄件信息，不能查看物流！');
        }
        if (!isset($order_res['mail_order_pay_state']) || intval($order_res['mail_order_pay_state']) < 1) {
            $this->outPut(null, -1, null, '该订单未发货，不能查看物流！');
        }

        $express_res = $this->_getCategoryList('express');
        $type = ternary($express_res[$order_res['express_id']]['ename'], '');
        $express_query = new \Common\Org\ExpressQuery();
        $data = array();
        $res = $express_query->express_query($type, $order_res['express_no']);
        if (isset($res['status']) && $res['status'] == 200 && isset($res['data'])) {
            $data = $res['data'];
        }

        $r_data = array(
            'express_name' => ternary($express_res[$order_res['express_id']]['name'], ''),
            'express_no' => ternary($order_res['express_no'], ''),
            'list' => $data
        );
        $this->outPut($r_data, 0);
    }

    /**
     * 删除订单
     */
    public function delOrder() {
        $this->_checkblank('id');
        $id = I('post.id', '', 'trim');
        $id = explode(',', rtrim($id, ','));

        $map = array(
            'id' => array('IN', $id),
            'user_id' => $this->uid
        );
        $order = M('order')->where($map)->select();

        $where = array(
            'order_id' => array('IN', $id),
            'user_id' => $this->uid
        );
        $comment = M('comment')->where($where)->getField('order_id,is_comment', true);

        $ids = array();
        foreach ($order as $k => $v) {
            if (($v['state'] == 'unpay' && $v['rstate'] == 'normal') || ($v['state'] == 'unpay' && $v['rstate'] == 'berefund') || ($v['state'] == 'pay' && $v['rstate'] == 'normal' && $comment[$v['id']] == 'Y')) {
                $ids[] = $v['id'];
            } else {
                unset($order[$k]);
            }
        }

        if (count($ids) == 0) {
            $this->outPut('', -1, '', '提交订单无法删除');
        }

        $con = array(
            'id' => array('IN', $ids),
            'user_id' => $this->uid
        );

        if (M('order')->where($con)->setField('is_display', 'N')) {
            $this->outPut('', 0);
        } else {
            $this->outPut('', -1, '', '删除失败');
        }
    }

    /**
     * 2016-04-22 daipingshan
     * 获取用户的openid
     */
    public function getOpenId(){
        $uid = $this->uid;
        $openid = M('weixin_sy')->where(array('user_id'=>$uid))->getField('openid');
        if($openid){
            $this->outPut(array('openid'=>$openid),0);
        }else{
            $this->outPut(array('openid'=>$openid),-1,'该用户暂未关注，无法分销！');
        }
    }

}
