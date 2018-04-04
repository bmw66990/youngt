<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-23
 * Time: 10:06
 */

namespace Manage\Controller;

/**
 * 财务控制器
 * Class FinanceController
 * @package Manage\Controller
 */
class FinanceController extends CommonController {

    /**
     * 首页
     */
    public function index() {
        $this->display();
    }

    /**
     * 周统计时间获取
     * @return mixed
     */
    protected function _getCountTime() {
        $pageday = date('Y-m-d', strtotime("-7 days"));
        $daytime = strtotime($pageday);
        $w = date('w');
        $data['st'] = $daytime - $w * 86400;
        $data['et'] = $daytime + (7 - $w) * 86400;
        return $data;
    }

    /**
     * 交易金额统计
     */
    public function getOrderAmount() {
        $cityId = $this->_getCityId();
        $time = $this->_getCountTime();
        $stime = $time['st'];
        $etime = $time['et'];
        //成交总额
        $list = D('Order')->orderAmountCount($cityId, $stime, $etime);
        $orderData = array();
        while ($stime < $etime) {
            $row = date('Y-m-d', $stime);
            $tmp['dt'] = $row;
            $tmp['money'] = ternary($list[$row], 0);
            $orderData[] = $tmp;
            $stime = strtotime("+1 day", $stime);
        }
        $this->ajaxReturn(array('data' => $orderData));
    }

    /**
     * 退款订单
     */
    public function getOrderRefund() {
        $cityId = $this->_getCityId();
        $time = $this->_getCountTime();
        $stime = $time['st'];
        $etime = $time['et'];

        $list = D('Order')->orderRefundCount($cityId, $stime, $etime);
        $orderData = array();
        while ($stime < $etime) {
            $row = date('Y-m-d', $stime);
            $tmp['dt'] = $row;
            $tmp['num'] = ternary($list[$row], 0);
            $orderData[] = $tmp;
            $stime = strtotime("+1 day", $stime);
        }
        $this->ajaxReturn(array('data' => $orderData));
    }

    /**
     * 订单手续费
     */
    public function getOrderFee() {
        $cityId = $this->_getCityId();
        $stime = strtotime(date('Y-m-d 00:00:00'));
        $etime = strtotime(date('Y-m-d 23:59:59')) + 1;
        $model = D('Order');
        $list = $model->orderSourceCount($cityId, $stime, $etime, 'daytotal');
        $orderData = $this->_orderSourceData($list, $stime, $etime, 'Y-m-d', 'day');
        //TODO 计算手续费
        $this->ajaxReturn(array('data' => $orderData[date('Y-m-d')]));
    }

    /**
     * 订单支付数量处理
     * @param $data
     * @param $stime
     * @param $etime
     * @param $format
     * @param $len
     * @return array
     */
    protected function _orderSourceData($data, $stime, $etime, $format, $len) {
        $list = array();
        //TODO 统计各种支付类型的数量,计算手续费
        $free = C('ORDER_FREE');
        while ($stime < $etime) {
            $row = date($format, $stime);
            // 支付宝
            $alipay = ternary($data['alipay'][$row], 0) + ternary($data['pcalipay'][$row], 0);
            $alipayApp = ternary($data['aliapp'][$row], 0);
            $alipayWap = ternary($data['aliwap'][$row], 0) + ternary($data['wapalipay'][$row], 0);
            // 财付通
            // $tenpay = ternary($data['tenpay'][$row], 0) + ternary($data['pctenpay'][$row], 0);
            // $tenpayApp = ternary($data['tenapp'][$row], 0);
            // $tenpayWap = ternary($data['tenwap'][$row], 0) + ternary($data['waptenpay'][$row], 0);
            // 微信
            $wxpay = ternary($data['wechatpay'][$row], 0) + ternary($data['wapwechatpay'][$row], 0) + ternary($data['pcwxpaycode'][$row], 0);
            // 全名付
            // $qmpay = ternary($data['umspay'][$row], 0) + ternary($data['wapumspay'][$row], 0);
            // 银联
            $unionpay = ternary($data['unionpay'][$row], 0) + ternary($data['wapunionpay'][$row], 0);;
            // 连连支付
            $lianlianpay = ternary($data['lianlianpay'][$row], 0);
            // 京东支付
            $wepay = ternary($data['wepay'][$row], 0);
//            $yeepay       = ternary($data['yeepay'][$row], 0);
//            $chinabank    = ternary($data['chinabank'][$row], 0);
//            $chinabankWap = ternary($data['chinawap'][$row], 0);
//            $epay         = ternary($data['epay'][$row], 0);
//            $epayWap      = ternary($data['wapepay'][$row], 0);
//            $other        = ternary($data['other'][$row], 0);
            //TODO 订单手续费= alipay*2*C('ALIPAY_FEE') + otherpay*2*C('OTHER_PAY')
            $list[$row][0]['device'] = '支付宝';
            $list[$row][0]['money'] = ($alipay + $alipayApp + $alipayWap) * 2 * $free['ALIPAY'];
            $list[$row][1]['device'] = '微信';
            $list[$row][1]['money'] = $wxpay * 2 * $free['WECHATPAY'];
            $list[$row][2]['device'] = '银联';
            $list[$row][2]['money'] = $unionpay * 2 * $free['UNIONPAY'];
            $list[$row][3]['device'] = '连连';
            $list[$row][3]['money'] = $lianlianpay * 2 * $free['LIANLIANPAY'];
            $list[$row][4]['device'] = '京东';
            $list[$row][4]['money'] = $wepay * 2 * $free['WEPAY'];
//            $list[$row][4]['device'] = '易宝支付';
//            $list[$row][5]['money']  = $yeepay;
//            $list[$row][5]['device'] = '网银在线';
//            $list[$row][5]['money']  = $chinabank + $chinabankWap;
//            $list[$row][6]['device'] = '翼支付';
//            $list[$row][6]['money']  = $epay + $epayWap;
//            $list[$row][7]['device'] = '其他';
//            $list[$row][7]['money']  = $other;

            $stime = strtotime("+1 {$len}", $stime);
        }
        return $list;
    }

    /**
     * 订单分类占比统计
     */
    public function getOrderCategory() {
        $cityId = $this->_getCityId();
        $stime = strtotime(date('Y-m-d 00:00:00'));
        $etime = strtotime(date('Y-m-d 23:59:59')) + 1;
        $list = D('Order')->orderCategoryCount($cityId, $stime, $etime, 'daytotal');
        $category = $this->_getCategoryList('group', array('fid' => 0));
        $orderData = array();
        $total = 0;
        foreach ($list as $key => $val) {
            $tmp = array();
            $tmp['label'] = $category[$val['cid']]['name'];
            $tmp['value'] = ternary($list[$key]['num'], 0);
            $total += $tmp['value'];
            $orderData[] = $tmp;
        }
        foreach ($orderData as $k => $v) {
            if ($total == 0) {
                break;
            } else {
                $orderData[$k]['value'] = sprintf('%.2f', $v['value'] / $total * 100);
            }
        }
        $orderData = empty($orderData) ? '' : $orderData;
        $this->ajaxReturn(array('data' => $orderData));
    }

    /**
     * //TODO 暂未使用
     * 统计数据组合
     * @param $attr
     * @param $data
     * @param $stime
     * @param $etime
     * @param $format
     * @param $type
     * @return array
     */
    protected function _combinationData($attr, $data, $stime, $etime, $format, $type) {
        $list = array();
        while ($stime < $etime) {
            $row = date($format, $stime);
            if (is_string($attr)) {
                $list[$attr][$row] = ternary($data[$attr][$row], 0);
            } else {
                foreach ($attr as $val) {
                    $list[$val][$row] = ternary($data[$val][$row], 0);
                }
            }
            $stime = strtotime("+1 {$type}", $stime);
        }
        return $list;
    }

    /**
     * 显示流水信息
     * 线下充值/在线充值/购买充值/充值卡充值/提现记录/现金支付/退款记录
     */
    public function flow() {
        $type = I('get.type');
        $city_id = $this->_getCityId();
        switch ($type) {
            case 'store':
            case 'withdraw':
                $data = $this->_getFlowStore($city_id, $type);
                break;
            case 'charge':
            case 'paycharge':
            case 'cardstore':
                $data = $this->_getFlowCharge($city_id, $type);
                break;
            case 'cash':
                $data = $this->_getFlowCash($city_id);
                break;
            case 'refund':
                $data = $this->_getFlowRefund($city_id);
                break;
            default:
                $this->error('非法访问！');
        }
        $this->assign('data', $data);
        $this->display($type);
    }

    /**
     * 获取线下充值和提现记录
     * @param $city_id
     * @param $type
     * @return mixed
     */
    protected function _getFlowStore($city_id, $type) {
        if (!empty($city_id)) {
            $map['u.city_id'] = $city_id;
        }
        $map['f.action'] = $type;

        $count = D('Flow')->getCount($map);
        $page = $this->pages($count, $this->reqnum, $map);
        $show = $page->show();
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = D('Flow')->getFlowWithAdmin($city_id, $type, $limit);

        $this->assign('pages', $show);
        return $data;
    }

    /**
     * 在线充值/购买充值/充值卡充值记录
     * @param $city_id
     * @param $type
     * @return mixed
     */
    protected function _getFlowCharge($city_id, $type) {
        if (!empty($city_id)) {
            $map['u.city_id'] = $city_id;
        }
        $map['f.action'] = $type;

        $count = D('Flow')->getCount($map);
        $page = $this->pages($count, $this->reqnum, $map);
        $show = $page->show();
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = D('Flow')->getFlowWithPay($city_id, $type, $limit);
        $this->assign('pages', $show);
        return $data;
    }

    /**
     * 现金支付
     * @param $city_id
     * @return mixed
     */
    protected function _getFlowCash($city_id) {
        $where['o.state'] = 'pay';  //已支付
        $where['o.service'] = 'cash';   //现金
        if (!empty($city_id)) {
            $where['u.city_id'] = $city_id;
        }
        $count = D('Order')->getCount($where);
        $page = $this->pages($count, $this->reqnum, $where);
        $show = $page->show();
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = D('Order')->getCashOrder($city_id, $limit);
        $this->assign('pages', $show);
        return $data;
    }

    /**
     * 退款记录
     * @param $city_id
     * @return mixed
     */
    protected function _getFlowRefund($city_id) {
        $where['f.action'] = 'refund';
        if (!empty($city_id)) {
            $where['u.city_id'] = $city_id;
        }
        $count = D('Flow')->getCount($where);
        $page = $this->pages($count, $this->reqnum, $where);
        $show = $page->show();
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = D('Flow')->getFlowWithTeam($city_id, 'refund', $limit);
        $this->assign('pages', $show);
        return $data;
    }

    /*     * **********************************************代理结算*********************************************************** */

    /**
     *   提现
     */
    public function withdrawals() {

        $cityId = $this->_getCityId();

        $order = D('Order');

        $apply_begin_time = $order->get_withdrawals_time($cityId);

        $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));

        //1.  本月交易利润
        $month_profit = array();
        //$month_profit['all_money'] = $order->get_month_all_profit($cityId, $apply_begin_time, $apply_end_time);

        // 2. 支付费用统计 Payment of fees
        $payment_fees = $order->get_payment_fees($cityId, $apply_begin_time, $apply_end_time);

        // 3. 短信费用
        $sms_charges = $order->get_SMS_charges($cityId, $apply_begin_time, $apply_end_time);
        // 4. 物流信息
        $express_res = $this->_getCategoryList('express');

        // 5. 计算优惠买单利润
        $dorder = D('Discount');
        $month_profit['discount_money'] = $dorder->get_month_discount_profit($cityId, $apply_begin_time, $apply_end_time);

        $data = array(
            'month' => $apply_begin_time,
            'plat_rate' => ternary($this->city['platform_rate'], 0),
            'all_profit_money' => ternary($month_profit['all_money']['profit_money'], 0),
            'month_profit' => $month_profit,
            'payment_fees' => $payment_fees,
            'sms_charges' => $sms_charges,
            'express_res' => $express_res
        );

        // 计算纯利润
        if ($data['plat_rate'] == 0.0) {//第一年什么都不扣
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money']);
        }elseif($data['plat_rate'] < 0.3){//省外超过2年扣除第三方费用切扣返点费用
            $all_profit_money = sprintf("%.2f", ($data['all_profit_money'] * (1 - $data['plat_rate'])) - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
            $data['net_profit_money'] = sprintf("%.2f", $all_profit_money);
        }elseif($data['plat_rate'] == 1.0){//够一年不够两年只扣第三方费用
            $all_profit_money = sprintf("%.2f",$data['all_profit_money'] - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
            $data['net_profit_money'] = sprintf("%.2f", $all_profit_money);
        }else{//省内3 7分
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money'] * (1 - $data['plat_rate']));
        }


        // 计算是否到提现日期
        $now_time = time();
        $withdrawals_time = strtotime('+5 day ' . date('Y-m-d H:i:s', $apply_end_time));
        $data['is_withdrawals_time'] = 'N';
        if ($now_time >= $withdrawals_time) {
            $data['is_withdrawals_time'] = 'Y';
        }

        $this->assign($data);

        $this->display();
    }
    //异步交易开始2016.5.16
    /***
     * 异步加载数据
     */
    public function withdrawalsNews() {
        // 计算是否到提现日期
        $now_time = time();
        $cityId = $this->_getCityId();

        $order = D('Order');

        $apply_begin_time = $order->get_withdrawals_time($cityId);

        $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));

        // 2. 支付费用统计 Payment of fees
        $payment_fees = $order->get_payment_fees($cityId, $apply_begin_time, $apply_end_time);

        // 3. 短信费用
        $sms_charges = $order->get_SMS_charges($cityId, $apply_begin_time, $apply_end_time);
        // 4. 物流信息
        $express_res = $this->_getCategoryList('express');

        // 5. 计算优惠买单利润
        $dorder = D('Discount');
        $month_profit['discount_money'] = $dorder->get_month_discount_profit($cityId, $apply_begin_time, $apply_end_time);

        $data = array(
            'month' => $apply_begin_time,
            'plat_rate' => ternary($this->city['platform_rate'], 0),
            'month_profit' => $month_profit,
            'payment_fees' => $payment_fees,
            'sms_charges' => $sms_charges,
            'express_res' => $express_res
        );

        $withdrawals_time = strtotime('+4 day ' . date('Y-m-d', $apply_end_time));
        $data['is_withdrawals_time'] = 'N';
        if ($now_time >= $withdrawals_time) {
            $data['is_withdrawals_time'] = 'Y';
        }
        $this->assign($data);

        $this->display();
    }

    /***
     * 本月交易利润
     */
    public function withdrawalsprofit() {
        $payment_fees=I('post.payment_fees','','trim');
        $sms_charges=I('post.sms_charges','','trim');
        $express_res=I('post.express_res','','trim');
        $cityId = $this->_getCityId();

        $order = D('Order');

        $apply_begin_time = $order->get_withdrawals_time($cityId);

        $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));

        //1.  本月交易利润
        $month_profit = array();
        $month_profit['all_money'] = $order->reception_profit_res($cityId, $apply_begin_time, $apply_end_time);

        $data = array(
            'plat_rate' => ternary($this->city['platform_rate'], 0),
            'month_profit' => $month_profit,
            'all_profit_money' => ternary($month_profit['all_money']['profit_money'], 0),
        );
        // 计算纯利润
        if ($data['plat_rate'] == 0.0) {//第一年什么都不扣
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money']);
        }elseif($data['plat_rate'] < 0.3){//省外超过2年扣除第三方费用切扣返点费用
            $all_profit_money = sprintf("%.2f", ($data['all_profit_money'] * (1 - $data['plat_rate'])) - $payment_fees - $sms_charges);
            $data['net_profit_money'] = sprintf("%.2f", $all_profit_money);
        }elseif($data['plat_rate'] == 1.0){//够一年不够两年只扣第三方费用
            $all_profit_money = sprintf("%.2f",$data['all_profit_money'] - $payment_fees - $sms_charges);
            $data['net_profit_money'] = sprintf("%.2f",$all_profit_money);
        }else{//省内3 7分
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money'] * (1 - $data['plat_rate']));
        }
        $this->ajaxReturn($data);
    }

    /***
     * 订单数跟交易金额
     */
    public function all_partner_res(){
        $cityId = $this->_getCityId();

        $order = D('Order');

        $apply_begin_time = $order->get_withdrawals_time($cityId);

        $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));

        //1.  本月交易利润
        $data = $order->all_partner_res($cityId, $apply_begin_time, $apply_end_time);
        $this->ajaxReturn($data);

    }

    /**
     * 退款数量
     */
    public function refund_all_partner_res()
    {
        $cityId = $this->_getCityId();

        $order = D('Order');

        $apply_begin_time = $order->get_withdrawals_time($cityId);

        $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));

        //1.  本月交易利润
        $data = $order->refund_all_partner_res($cityId, $apply_begin_time, $apply_end_time);
        $this->ajaxReturn($data);
    }
    //异步交易结束2016.5.16
    /**
     *  提现记录
     */
    public function withdrawals_record() {
        $cityId = $this->_getCityId();

        $where = array(
            'city_id' => $cityId,
        );

        $count = M('agent_pay')->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = M('agent_pay')->where($where)->limit($limit)->select();

        $data = array(
            'list' => $list,
            'page' => $page->show(),
            'count' => $count
        );
        $this->assign($data);
        $this->display();
    }

    /**
     *  本月交易利润  查看全部
     */
    public function withdrawals_profit_all() {
        $apply_begin_time = I('get.month', '', 'trim');
        $cityId = $this->_getCityId();
        if ($apply_begin_time) {
            $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));
            $data = $this->get_month_profit($cityId, $apply_begin_time, $apply_end_time);
            $this->assign($data);
        }
        $this->display();
    }

    /**
     *  本月优惠买单交易利润  查看全部
     */
    public function withdrawals_discount_profit_all() {
        $apply_begin_time = I('get.month', '', 'trim');
        $cityId = $this->_getCityId();
        if ($apply_begin_time) {
            $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));
            $data = $this->get_month_discount_profit($cityId, $apply_begin_time, $apply_end_time);
            $this->assign($data);
        }
        $this->display();
    }

    /**
     * 结算明细
     */
    public function withdrawals_view() {
        $agent_pay_id = I('get.agent_pay_id', '0', 'trim');

        $where = array(
            'id' => $agent_pay_id,
        );
        $agent_info = M('agent_pay')->where($where)->find();
        if ($agent_info && isset($agent_info['apply_time']) && $agent_info['apply_time'] > 0) {
            $cityId = $this->_getCityId();

            $order = D('Order');

            $apply_begin_time = $agent_info['apply_time'];

            $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));

            //1.  本月交易利润
            $month_profit = array();
            $month_profit['all_money'] = $order->get_month_all_profit($cityId, $apply_begin_time, $apply_end_time);

            // 2. 支付费用统计 Payment of fees
            $payment_fees = $order->get_payment_fees($cityId, $apply_begin_time, $apply_end_time);

            // 3. 短信费用
            $sms_charges = $order->get_SMS_charges($cityId, $apply_begin_time, $apply_end_time);
          // 5. 计算优惠买单利润
            $dorder = D('Discount');
            $month_profit['discount_money'] = $dorder->get_month_discount_profit($cityId, $apply_begin_time, $apply_end_time);

            $data = array(
                'month' => $apply_begin_time,
                'agent_info' => $agent_info,
                'plat_rate' => ternary($agent_info['platform_rate'], 0),
                'all_profit_money' => ternary($month_profit['all_money']['profit_money'], 0),
                'month_profit' => $month_profit,
                'payment_fees' => $payment_fees,
                'sms_charges' => $sms_charges,
            );
            // 计算纯利润
            if ($data['plat_rate'] == 0.0) {//第一年什么都不扣
                $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money']);
            }elseif($data['plat_rate'] < 0.3){//省外超过2年扣除第三方费用切扣返点费用
                $data['all_profit_money'] = sprintf("%.2f", ($data['all_profit_money'] * (1 - $data['plat_rate'])) - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
                $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money']);
            }elseif($data['plat_rate'] == 1.0){//够一年不够两年只扣第三方费用
                $data['all_profit_money'] = sprintf("%.2f",$data['all_profit_money'] - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
                $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money']);
            }else{//省内3 7分
                $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money'] * (1 - $data['plat_rate']));
            }
            /*
            if ($data['plat_rate'] < 0.3) {
                $data['all_profit_money'] = sprintf("%.2f", $data['all_profit_money'] - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
            }
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money'] * (1 - $data['plat_rate']));
             */
            $this->assign($data);

            $this->display();
        }
    }

    /**
     * 代理提现操作
     */
    public function doWithdrawals() {
        $express_no = I('post.express_no', '', 'trim');
        $express_id = I('post.express_id', '', 'trim');

        if (!$express_no) {
            $this->ajaxReturn(array('code' => -1, 'error' => '快递单号不能为空！'));
        }
        if (!$express_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择快递公司！'));
        }

        $cityId = $this->_getCityId();

        $order = D('Order');

        $apply_begin_time = $order->get_withdrawals_time($cityId);

        $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));

        //1.  本月交易利润
        $month_profit = $order->get_month_all_profit($cityId, $apply_begin_time, $apply_end_time);

        // 2. 支付费用统计 Payment of fees
        $payment_fees = $order->get_payment_fees($cityId, $apply_begin_time, $apply_end_time);

        // 3. 短信费用
        $sms_charges = $order->get_SMS_charges($cityId, $apply_begin_time, $apply_end_time);
       // 5. 计算优惠买单利润
        $dorder = D('Discount');
        $month_profit['discount_money'] = $dorder->get_month_discount_profit($cityId, $apply_begin_time, $apply_end_time);

        $data = array(
            'plat_rate' => ternary($this->city['platform_rate'], 0),
            'all_profit_money' => ternary($month_profit['profit_money'], 0),
            'month_profit' => $month_profit,
            'payment_fees' => $payment_fees,
            'sms_charges' => $sms_charges,
        );
        /*
        if ($data['plat_rate'] < 0.3) {
            $data['all_profit_money'] = sprintf("%.2f", $data['all_profit_money'] - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
        }
        $net_profit_money = sprintf("%.2f", $data['all_profit_money'] * (1 - $data['plat_rate']));
        */
        // 计算纯利润
        if ($data['plat_rate'] == 0.0) {//第一年什么都不扣
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money']);
        }elseif($data['plat_rate'] < 0.3){//省外超过2年扣除第三方费用切扣返点费用
            $data['all_profit_money'] = sprintf("%.2f", ($data['all_profit_money'] * (1 - $data['plat_rate'])) - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money']);
        }elseif($data['plat_rate'] == 1.0){//够一年不够两年只扣第三方费用
            $data['all_profit_money'] = sprintf("%.2f",$data['all_profit_money'] - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money']);
        }else{//省内3 7分
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money'] * (1 - $data['plat_rate']));
        }
        $net_profit_money = sprintf("%.2f", $data['all_profit_money'] * (1 - $data['plat_rate']));
        $where = array(
            'city_id' => $cityId,
            'apply_time' => $apply_begin_time,
        );
        $agent_pay_count = M('agent_pay')->where($where)->count();
        if ($agent_pay_count && $agent_pay_count >= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '您' . date('Y年m月份', $apply_begin_time) . '已经提过现，不能重复提现！'));
        }

        $add_data = array(
            'city_id' => $cityId,
            'money' => $net_profit_money,
            'old_money' => $net_profit_money,
            'express_id' => $express_id,
            'express_no' => $express_no,
            'all_profit_money' => ternary($month_profit['profit_money'], 0),
            'payment_all_rate_money' => ternary($payment_fees['all_rate_money'], 0),
            'sms_money' => ternary($sms_charges['sms_money'], 0),
            'platform_rate' => ternary($this->city['platform_rate'], 0),
            'paymark' => 0,
            'verify_state' => 'N',
            'apply_agent_id' => ternary($this->user['id'], 0),
            'handle_admin_id' => 0,
            'apply_time' => $apply_begin_time,
            'create_time' => time(),
            'end_time' => strtotime('+3 day'),
        );

        $res = M('agent_pay')->add($add_data);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '申请提现失败！'));
        }

        $this->ajaxReturn(array('code' => 0));
    }

    /***
     * 新结算
     */
    public function doWithdrawalsNews() {
        $express_no = I('post.express_no', '', 'trim');
        $express_id = I('post.express_id', '', 'trim');
        $net_profit_money=I('post.net_profit_money', '', 'trim');//利润
        $all_profit_money=I('post.all_profit_money', '', 'trim');//总利润
        $all_rate_money=I('post.all_rate_money', '', 'trim');//手续费
        $sms_money=I('post.sms_money', '', 'trim');//短信费
        if (!$express_no) {
            $this->ajaxReturn(array('code' => -1, 'error' => '快递单号不能为空！'));
        }
        if (!$express_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择快递公司！'));
        }

        $cityId = $this->_getCityId();
        $order = D('Order');
        $apply_begin_time = $order->get_withdrawals_time($cityId);
        $where = array(
            'city_id' => $cityId,
            'apply_time' => $apply_begin_time,
        );
        $agent_pay_count = M('agent_pay')->where($where)->count();
        if ($agent_pay_count && $agent_pay_count >= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '您' . date('Y年m月份', $apply_begin_time) . '已经提过现，不能重复提现！'));
        }

        $add_data = array(
            'city_id' => $cityId,
            'money' => $net_profit_money,
            'old_money' => $net_profit_money,
            'express_id' => $express_id,
            'express_no' => $express_no,
            'all_profit_money' => ternary($all_profit_money, 0),
            'payment_all_rate_money' => ternary($all_rate_money, 0),
            'sms_money' => ternary($sms_money, 0),
            'platform_rate' => ternary($this->city['platform_rate'], 0),
            'paymark' => 0,
            'verify_state' => 'N',
            'apply_agent_id' => ternary($this->user['id'], 0),
            'handle_admin_id' => 0,
            'apply_time' => $apply_begin_time,
            'create_time' => time(),
            'end_time' => strtotime('+3 day'),
        );

        $res = M('agent_pay')->add($add_data);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '申请提现失败！'));
        }

        $this->ajaxReturn(array('code' => 0));
    }
    /**
     *  获取本月利润表
     * @param type $city_id
     * @param type $apply_begin_time
     * @param type $apply_end_time
     * @param type $limit
     * @return type
     */
    protected function get_month_profit($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0, $limit = 20) {

        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }

        if (!$limit) {
            $limit = 20;
        }

        $where = array(
            'city_id' => $city_id,
            'partner_id' => array('gt', 0),
            '_string' => "rstate='berefund' or state='pay'",
            'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );

        $count = M('order')->where($where)->field('count(distinct(partner_id)) as count_order')->find();
        $count = isset($count['count_order']) && $count['count_order'] > 0 ? $count['count_order'] : 0;
        $page = $this->pages($count, $limit);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = M('order')->where($where)->group('partner_id')->limit($limit)->getField('partner_id,count(id) as order_count', true);
        if ($list) {
            $partner_ids = array_keys($list);

            // 获取商户名称
            $partner_info = M('partner')->where(array('id' => array('in', $partner_ids)))->getField('id,username', true);

            // 订单数（已减去退款） 交易金额（已减去退款）
            $order_where = array(
                'partner_id' => array('in', $partner_ids),
                'city_id' => $city_id,
                'rstate' => 'normal',
                'state' => 'pay',
                'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
            );

            $order_info = M('order')->where($order_where)->group('partner_id')->getField('partner_id,count(id) as order_count,sum(origin) as order_sum_money', true);

            // 退款
            $order_where['rstate'] = 'berefund';
            $order_where['state'] = 'unpay';
            $order_refund_info = M('order')->where($order_where)->group('partner_id')->getField('partner_id,count(id) as order_refund_count,sum(origin) as order_refund_sum_money', true);

            // 接待量
            $reception_profit_where = array(
                'team.city_id' => $city_id,
                'partner_income.partner_id' => array('in', $partner_ids),
                'partner_income.create_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
            );
            $reception_profit_info = M('partner_income')->where($reception_profit_where)
                            ->join('inner join team on team.id=partner_income.team_id')->group('partner_income.partner_id')->getField('partner_income.partner_id,count(partner_income.id) as reception_count,sum(team.team_price-team.ucaii_price) as profit_money', true);

            $_list = array();
            foreach ($list as $k => $v) {
                $_list[$k] = array(
                    'partner_id' => $k,
                    'partner_username' => ternary($partner_info[$k], ''),
                    'partner_order_count' => ternary($order_info[$k]['order_count'], 0),
                    'partner_order_sum_money' => ternary($order_info[$k]['order_sum_money'], 0),
                    'partner_order_refund_count' => ternary($order_refund_info[$k]['order_refund_count'], 0),
                    'partner_order_refund_sum_money' => ternary($order_refund_info[$k]['order_refund_sum_money'], 0),
                    'partner_reception_count' => ternary($reception_profit_info[$k]['reception_count'], 0),
                    'partner_profit_money' => ternary($reception_profit_info[$k]['profit_money'], 0),
                );
            }
            $list = $_list;
        }

        $data = array(
            'count' => $count,
            'page' => $page->show(),
            'list' => $list,
        );
        return $data;
    }

    /**
     *  获取本月优惠买单利润表
     * @param type $city_id
     * @param type $apply_begin_time
     * @param type $apply_end_time
     * @param type $limit
     * @return type
     */
    protected function get_month_discount_profit($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0, $limit = 20) {

        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }

        if (!$limit) {
            $limit = 20;
        }

        $where = array(
            'city_id' => $city_id,
            'partner_id' => array('gt', 0),
            'state' => 'pay',
            'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );

        $count = M('discountOrder')->where($where)->field('count(distinct(partner_id)) as count_order')->find();
        $count = isset($count['count_order']) && $count['count_order'] > 0 ? $count['count_order'] : 0;
        $page = $this->pages($count, $limit);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = M('discountOrder')->where($where)->group('partner_id')->limit($limit)->getField('partner_id,count(id) as order_count', true);
        if ($list) {
            $partner_ids = array_keys($list);
            // 获取商户名称
            $partner_info = M('partner')->where(array('id' => array('in', $partner_ids)))->getField('id,username', true);

            // 订单数（已减去退款） 交易金额（已减去退款）
            $where['partner_id'] = array('in', $partner_ids);
            $order_info = M('discountOrder')->where($where)->group('partner_id')->getField('partner_id,count(id) as order_count,sum(origin) as order_sum_money,sum(round(origin * aratio, 2)) as profit_money', true);

            $_list = array();
            foreach ($list as $k => $v) {
                $_list[$k] = array(
                    'partner_id' => $k,
                    'partner_username' => ternary($partner_info[$k], ''),
                    'partner_order_count' => ternary($order_info[$k]['order_count'], 0),
                    'partner_order_sum_money' => ternary($order_info[$k]['order_sum_money'], 0),
                    'partner_profit_money' => ternary($order_info[$k]['profit_money'], 0),
                );
            }
            $list = $_list;
        }

        $data = array(
            'count' => $count,
            'page' => $page->show(),
            'list' => $list,
        );
        return $data;
    }

}
