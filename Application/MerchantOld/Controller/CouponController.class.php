<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class CouponController extends CommonController {

    /**
     * 卷号验证
     * @param type $partner_id
     */
    public function index($partner_id = '') {

        $where = array(
            'partner_id' => $partner_id,
            'end_time' => array('EGT', strtotime(date('Y-m-d'))),
        );
        if (!trim($partner_id)) {
            $partner_id = $this->partner_id;
            $where_partner_ids = $this->_getPartnerByid($partner_id, true);
            if (!$where_partner_ids) {
                $where_partner_ids = $partner_id;
            }
            $where['partner_id'] = $where_partner_ids;
        }

        $team = M('team');
        // 查询项目
        $field = 'id,product,title,begin_time,end_time,now_number,pre_number,team_price,ucaii_price,state';
        $count = $team->where($where)->count();
        $Page = $this->pages($count, $this->reqnum);
        $list = $team->field($field)
                ->where($where)
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        if($list){
            $team_ids=array();
            foreach($list as &$v){
                $team_ids[$v['id']] = $v['id'];
            }
            unset($v);
            $team_pay_res_count = array();
            if($team_ids){
                $team_pay_res = M('coupon')->where(array('team_id' => array('in',  array_values($team_ids)), 'consume' => array('in', array('Y', 'N'))))->field('team_id,count(id) as coupon_count')->group('team_id')->select();
                if($team_pay_res){
                    foreach($team_pay_res as &$v){
                        $team_pay_res_count[$v['team_id']] = $v;
                    }
                    unset($v);
                }
                
            }
            
            foreach($list as &$v){
               $v['now_number'] = ternary($team_pay_res_count[$v['id']]['coupon_count'], 0);
            }
            unset($v);
        }
        
        // 查询分店
        $partner = D('Partner');
        $partner_breach = $partner->getPartnerBranch($this->partner_id);

        //查询积分商品
        $voucher_data  = M('points_team')->field('name,consume_num,limit_num,begin_time,end_time')
                                        ->where(array('partner_id' => $partner_id))
                                        ->select();

        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'voucher_data'=>$voucher_data,
            'list' => $list,
            'partner_breach' => $partner_breach,
            'partner_id' => $partner_id
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 校验青团券是否存在
     */
    public function checkCouponExist() {

        // 接收参数
        $couponId = I('post.id', '', 'strval');
        $couponId = str_replace(' ', '', $couponId);

        if (!trim($couponId)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请输入券号'));
        }
        $where_partner_ids = $this->_getPartnerByid($this->partner_id, false);
        if (!$where_partner_ids) {
            $where_partner_ids = $this->partner_id;
        }
        // 查询条件
        $where = array(
            'partner_id' => $where_partner_ids,
            'id' => $couponId
        );
        $couponCount = M('coupon')->where($where)->count();
        if (!$couponCount || intval($couponCount)<=0) {
            $where['operation_type'] = 'refund';
            $coupon_refund_count = M('coupon_delete')->where($where)->count();
            if ($coupon_refund_count && intval($coupon_refund_count) > 0) {
                $this->ajaxReturn(array('code' => -1, 'error' => '该团券号已经退款'));
            }
            $this->ajaxReturn(array('code' => -1, 'error' => '该券号不是本店券号或券号错误'));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 青团密码查询校验
     */
    public function indexCheckCoupon() {

        $action = I('post.action', '', 'strval');
        $couponId = I('post.id', '', 'strval');
        $couponId = str_replace(' ', '', $couponId);

        $where_partner_ids = $this->_getPartnerByid($this->partner_id, false);
        if (!$where_partner_ids) {
            $where_partner_ids = $this->partner_id;
        }
        // 查询条件
        $where = array(
            'partner_id' => $where_partner_ids,
            'id' => $couponId
        );
        $coupon = M('coupon');
        $couponRes = $coupon->field('id,team_id,partner_id,consume,expire_time,order_id,consume_time')->where($where)->find();

        // 获取对应团单的信息
        $teamRes = M('team')->field('product,title,team_price,expire_time')->where(array('id' => $couponRes['team_id']))->find();

        // 获取对应商家信息
        $partnerRes = M('partner')->field('username,mobile')->where(array('id' => $couponRes['partner_id']))->find();

        if (trim($action) == 'number' || $couponRes['consume'] == 'Y' || $couponRes['expire_time'] < strtotime(date('Y-m-d'))) {
            $data = array(
                'team' => $teamRes,
                'partner' => $partnerRes,
                'time' => time(),
                'coupon' => $couponRes,
                'action' => 'number'
            );
            $this->assign($data);
            $this->display('Coupon/check_coupon');
            exit;
        }

        $where = array(
            'order_id' => $couponRes['order_id'],
            'consume' => 'N',
            'expire_time' => array('egt', strtotime(date('Y-m-d')))
        );
        $coupons = $coupon->where($where)->field('id')->select();
        $count = count($coupons);
        if ($count == 1) {
            $coupons = array_pop($coupons);
        }

        $data = array(
            'team' => $teamRes,
            'partner' => $partnerRes,
            'count' => $count,
            'coupon' => $coupons,
            'action' => 'consume'
        );
        $this->assign($data);
        $this->display('Coupon/check_coupon');
    }

    /**
     * 执行校验
     */
    public function indexExecuteCheckCoupon() {

        // 接收参数
        $couponId = I('post.coupon_id', '');
        $action = I('post.action', '', 'strval');
        $action = trim($action);

        if ($action && !$couponId) {
            $this->ajaxReturn(array('code' => -1, 'error' => '所选参数不能为空！'));
        }

        $coupon = M('coupon');
        $couponCopy = array();
        $couponRes = array();
        $field = 'id,consume,team_id,order_id,consume_time,partner_id';
        if (is_array($couponId)) {
            $count = count($couponId);
            if ($count > 1) {
                $coupon->where(array('id' => array('IN', $couponId)));
            } else {
                $coupon->where(array('id' => end($couponId)));
            }
            $couponRes = $coupon->field($field)->select();
            if ($action) {
                $res = $this->__checkCouponUpdateData($couponRes, true);
                if (!$res) {
                    $this->ajaxReturn(array('code' => -1, 'error' => '校验失败！'));
                }
            }
            $couponRes = end($couponRes);
            $couponCopy['num'] = $count;
            $couponCopy['su'] = @implode(' ', $couponId);
        } else {
            $couponRes = $coupon->where(array('id' => $couponId))->field($field)->find();
            if ($action) {
                $res = $this->__checkCouponUpdateData($couponRes);
                if (!$res) {
                    $this->ajaxReturn(array('code' => -1, 'error' => '校验失败！'));
                }
            }
            $couponCopy['num'] = 1;
            $couponCopy['su'] = $couponId;
        }

        if ($action) {
            $this->ajaxReturn(array('code' => 0));
        }

        // 获取券号信息
        $couponRes = $coupon->where(array('id' => $couponRes['id']))->field($field)->find();

        // 获取对应团单的信息
        $teamRes = M('team')->field('product,title,team_price,expire_time')->where(array('id' => $couponRes['team_id']))->find();

        // 获取对应商家信息
        $partnerRes = M('partner')->field('username,mobile,enable')->where(array('id' => $couponRes['partner_id']))->find();

        // 整理返回数据
        $data = array(
            'coupon_id' => $couponId,
            'scoupon_id' => $couponCopy,
            'coupon' => $couponRes,
            'team' => $teamRes,
            'partner' => $partnerRes,
        );
        $this->assign($data);
        $this->display('Coupon/check_coupon_result');
    }

    /**
     * 校验青团券 更新数据库
     */
    private function __checkCouponUpdateData($couponRes = array(), $isMulti = false) {

        if (!$couponRes) {
            return false;
        }
        $order_id = '';
        $ids = array();
        $couponResArr = array();
        if (!$isMulti) {
            $ids[] = ternary($couponRes['id'], '');
            $order_id = ternary($couponRes['order_id'], '');
            $couponResArr[] = $couponRes;
        } else {
            foreach ($couponRes as &$v) {
                $ids[] = ternary($v['id'], '');
            }
            $endCoupon = end($couponRes);
            $order_id = ternary($endCoupon['order_id'], '');
            $couponResArr = $couponRes;
        }

        $data = array(
            'id' => $ids,
            'list' => $couponResArr,
            'order_id' => $order_id,
        );
        $coupon = D('Coupon');
        $res = $coupon->consumeCoupon($data, $this->partner_id, '商户端pc后台');
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 卷号详情
     * @param type $partner_id
     */
    public function couponDetail() {

        // 接收参数
        $partner_id = I('get.partner_id', '', 'intval');
        $team_id = I('get.tid', '');
        $consume = I('get.consume', '');
        $coupon = I('get.coupon', '');
        $start_time = I('get.start_time', '');
        $end_time = I('get.end_time', '');
        $create_time = I('get.create_time', '');
        $order = I('get.order', '');
        $action = I('get.action', '');
        if (!trim($consume)) {
            $consume = I('get.consume', '');
        }
        if (!trim($start_time)) {
            $start_time = I('get.start_time', '');
        }
        if (!trim($end_time)) {
            $end_time = I('get.end_time', '');
        }
        if (!trim($team_id)) {
            $team_id = I('get.tid', '');
        }
        if (!trim($partner_id)) {
            $partner_id = I('get.partner_id', '', 'intval');
        }

        // 团券列表跳转过来传参
        $act = I('get.act', '', 'strval');
        $pay_id = I('get.pay_id', '', 'strval');


        if (!trim($partner_id)) {
            $partner_id = $this->partner_id;
        }

        $team = M('team');
        // 获取该商户所有的项目
        $teamRes = $team->where(array('partner_id' => $partner_id))->field('id,product')->select();

        // 查询条件
        $where = array(
            'coupon.partner_id' => $partner_id
        );
        if (trim($team_id)) {
            $where['coupon.team_id'] = $team_id;
        }
        if (trim($consume)) {
            $where['coupon.consume'] = $consume;
        }
        if (trim($coupon)) {
            $where['coupon.id'] = array('like', "$coupon%");
        }
        if (trim($start_time) && trim($end_time)) {
            $where['coupon.consume_time'] = array(array('gt', strtotime($start_time)), array('lt', strtotime($end_time)));
        }

        if (trim($act)) {
            $where['coupon.consume'] = $act;
        }

        if (trim($pay_id) != '') {
            $incomeWhere = array(
                'partner_income.pay_id' => $pay_id,
                'partner_income.partner_id' => $partner_id,
                'partner_income.create_time' => array('elt', time()),
            );
            $partner_income = M('partner_income');
            $cids = $partner_income->field('coupon_id')->where($incomeWhere)->select();

            foreach ($cids as &$v) {
                if (isset($v['coupon_id'])) {
                    $v = $v['coupon_id'];
                }
            }
            unset($v);
            $cids = array_values($cids);
            $where['coupon.id'] = array('in', $cids);
            if (!$cids) {
                $where['coupon.id'] = array('in', array(''));
            }

            // 查询partner_income统计信息
            $field = array(
                'partner_income.team_id' => 'team_id',
                'count(partner_income.team_id)' => 'num',
                'sum(partner_income.money)' => 'totalmoney',
                'team.product' => 'product',
            );
            $partner_income_list = $partner_income->field($field)->where($incomeWhere)
                            ->join('LEFT JOIN team ON team.id=partner_income.team_id')->group('partner_income.team_id')->select();
            $this->assign('partner_income', $partner_income_list);
        }

        // 从消费明细跳转过来
        if (trim($action)) {
            $_where = array(
                'team_id' => $team_id,
                'partner_id' => $partner_id,
                'create_time' => array(array('egt', strtotime($create_time . '00:00:00')), array('lt', strtotime('+1 day ' . $create_time . '00:00:00'))),
            );
            $cids = M('partner_income')->field('coupon_id')->where($_where)->select();

            foreach ($cids as &$v) {
                if (isset($v['coupon_id'])) {
                    $v = $v['coupon_id'];
                }
            }
            unset($v);
            $cids = array_values($cids);
            if ($cids) {
                $where['coupon.id'] = array('in', $cids);
            }
            $where['coupon.team_id'] = $team_id;
        }
        // 排序
        //$sort = array('coupon.team_id' => 'desc');
        $sort = array('coupon.consume_time' => 'desc');
        if (trim($order)) {
            $sort = array("coupon.$order" => 'desc');
        }

        // 查询数据
        $coupon = M('coupon');
        $count = $coupon->where($where)->count();
        $Page = $this->pages($count, $this->reqnum);
        $field = array(
            'coupon.id' => 'id',
            'coupon.team_id' => 'team_id',
            'coupon.consume_time' => 'consume_time',
            'coupon.consume' => 'consume',
            'coupon.create_time' => 'create_time',
            'coupon.team_price' => 'coupon_team_price',
            'coupon.ucaii_price' => 'coupon_ucaii_price',
            'team.team_price' => 'team_team_price',
            'team.ucaii_price' => 'team_ucaii_price',
            'coupon.expire_time' => 'expire_time',
            'user.username' => 'username',
            'team.product' => 'product',
        );
        $list = $coupon->field($field)->order($sort)
                ->where($where)->join("left join user on user.id=coupon.user_id")->join('left join team on team.id=coupon.team_id')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
  
        // 整理数据
        if ($list) {
            foreach ($list as &$v) {
                $v['team_price'] = ternary($v['coupon_team_price'], '0');
                if (!isset($v['team_price']) || $v['team_price'] <= 0) {
                    $v['team_price'] = ternary($v['team_team_price'], '');
                }
                $v['ucaii_price'] = ternary($v['coupon_ucaii_price'], '0');
                if (!isset($v['ucaii_price']) || $v['ucaii_price'] <= 0) {
                    $v['ucaii_price'] = ternary($v['team_ucaii_price'], '');
                }
            }
            unset($v);
        }

        // 查询分店数据
        $partner = D('Partner');
        $partner_breach = $partner->getPartnerBranch($this->partner_id);

        $data = array(
            'partner_id' => $partner_id,
            'tid' => $team_id,
            'consume' => $consume,
            'coupon_id' => $coupon,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'order' => $order,
            'list' => $list,
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
            'team' => $teamRes,
            'partner_breach' => $partner_breach,
        );

        $this->assign($data);
        $this->display('CouponDetail/index');
    }

    public function team() {
        $id = I('get.id', 0, 'intval');
        $team = M('team');
        $order = M('order');
        $partner = M('partner');
        $coupon = M('coupon');

        $teamRes = $this->_getRowDataToOTS('team', array('id' => $id));
        if (!$teamRes || isset($teamRes['error'])) {
            $teamRes = $team->where(array('id' => $id))->find();
        }
        $partnerRes = $partner->where(array('id' => $teamRes['partner_id']))->find();
        $pays = $order->field('count(id) as paycount,sum(quantity) as buycount,sum(money) as onlinepay,sum(credit) as creditpay,sum(card) as cardpay')->where(array('state' => 'pay', 'team_id' => $id))->find();
              
        $consumed = $coupon->where(array('team_id' => $id, 'consume' => 'Y'))->count();
        $pays['buycount'] = $coupon->where(array('team_id' => $id))->count();
        $settled = $consumed * $teamRes['ucaii_price'];
        $teamRes['state'] = team_state($teamRes);

        $data = array(
            'pays' => $pays,
            'team' => $teamRes,
            'settled' => $settled,
            'partner' => $partnerRes,
            'paycount' => ternary($pays['paycount'], 0),
            'consumed' => $consumed,
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 查询积分订单
     */
    public function checkPointsVoucher(){
        if(IS_AJAX && IS_POST){
            $partner_id = $this->partner_id;
            $voucher = str_replace(' ','',I('post.voucher'));
            if($voucher && strlen($voucher) == 10){
                $where = array('partner_id' => $partner_id,'code'=>$voucher);
                $info = $this->_getPointsVoucher($where);
                if($info){
                    $data = getPromptMessage($info,'success',1);
                }else{
                    $data = getPromptMessage('兑换券错误或该兑换券对应商品不是本店商品');
                }
            }else{
                $data = getPromptMessage('兑换券长度不合法，请确认后再操作');
            }
        }else{
            $data = getPromptMessage('非法请求！');
        }
        $this->ajaxReturn($data);
    }


    /**
     * 验证积分兑换券
     */
    public function consumePointsVoucher(){
        if(IS_AJAX && IS_POST) {
            $partner_id = $this->partner_id;
            $voucher    = str_replace(' ', '', I('post.voucher'));
            if ($voucher && strlen($voucher) == 10) {
                $where = array('partner_id' => $partner_id, 'code' => $voucher);
                $info  = M('points_order')->where($where)->find();
                if($info){
                    if ($info['consume'] == 'Y') {
                        $data = getPromptMessage('兑换券已兑换领取');
                    } else {
                        if ($info['expire_time'] < strtotime(date('Y-m-d', time()))) {
                            $data = getPromptMessage('兑换券已过期');
                        } else {
                            $up_data = array('id' => $info['id'], 'consume' => 'Y', 'consume_time' => time());
                            $res     = M('points_order')->save($up_data);
                            if ($res) {
                                $info = $this->_getPointsVoucher($where);
                                $data = getPromptMessage($info, 'success', 1);
                            } else {
                                $data = getPromptMessage('兑换失败');
                            }
                        }
                    }
                }else{
                    $data = getPromptMessage('兑换券长度不合法，请确认后再操作');
                }
            }else{
                $data = getPromptMessage('兑换券长度不合法，请确认后再操作');
            }
        }else{
            $data = getPromptMessage('非法请求！');
        }
        $this->ajaxReturn($data);
    }


    /**
     * @param $where
     * @return mixed
     */
    protected function _getPointsVoucher($where){
        $info = M('points_order')->where($where)->find();
        if($info) {
            if ($info['consume'] == 'Y') {
                $info['status']      = 'Y';
                $info['status_name'] = '已使用';
                $info['consume_time'] = date('Y-m-d', $info['consume_time']);
            } else {
                if ($info['expire_time'] < strtotime(date('Y-m-d', time()))) {
                    $info['status']      = 'E';
                    $info['status_name'] = '已过期';
                } else {
                    $info['status']      = 'N';
                    $info['status_name'] = '未使用';
                }
                $info['expire_time'] = date('Y-m-d', $info['expire_time']);
            }
            $info['name']        = M('points_team')->getFieldById($info['team_id'], 'name');
        }
        return $info;
    }
}
