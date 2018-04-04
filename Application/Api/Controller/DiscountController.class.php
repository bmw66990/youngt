<?php
/**
 * Created by PhpStorm.
 * User: zhoombin@126.com
 * Date: 2016/4/20
 * Time: 14:09
 */

namespace Api\Controller;

class DiscountController extends CommonController {

//    protected $signCheck = false;

    /*
     * 优惠买单商户列表
     */
    public function partners(){
        // 接收参数
        $data = array(
            'pt_query' => I('get.pt_query', '', 'strval'), // 搜索关键字
            'query'    => I('get.query', '', 'strval'), // 搜索关键字
            'lng'      => I('get.lng', '', 'doubleval'),
            'lat'      => I('get.lat', '', 'doubleval'),
            'lastId'   => I('get.lastId', ''),
            'end_id'   => I('get.end_id', ''),
            'distance' => I('get.distance', 0, 'doubleval'),
        );

        if (!trim($data['lng']) || !trim($data['lat'])) {
           $this->outPut(null, -1, null, '定位失败，请开启定位权限！');
        }
        $limit_num = 20;
        $team = D('Team');
        //$data['order'] = 'distance';
        $data['is_discount'] = 1;
        $data['query'] = $data['pt_query'];

        $pdata = $team->getTeamSearchPartner($data, $limit_num);
        if (!$pdata || !is_array($pdata)) {
            $pdata = array();
        } else {
            foreach ($pdata as $key => $v_data) {
                $partner_id = $v_data['partner_id'];
                $discount = M('discount')->where(array('partner_id'=>$partner_id))->find();
                $nowTime = time();
                if (!$discount || ($nowTime < $discount['start_time'] || $nowTime > $discount['end_time'])) {
                    unset($pdata[$key]);
                }else {
                    $pdata[$key]['discount'] = $discount;
                    $snum = M('order')->where(array('partner_id'=>$partner_id,'state'=>'pay'))->count('id');
                    $pdata[$key]['snum'] = $snum;
                }
            }
        }

        $hasnext = $this->setHasNext(false, $pdata, $limit_num - 1);

        $this->setHasNext($hasnext);
        $this->outPut(array_values($pdata), 0);
    }


    /*
     * 优惠买单/确认买单，生成订单
     */
    public function okbuy() {
        // 接收参数
        $this->_checkblank(array('id', 'price', 'fixed', 'plat'));
        $id     = I('post.id', 0, 'intval');
        $price  = I('post.price', 0, 'doubleval');
        $fixed  = I('post.fixed', 0, 'doubleval');
        $mobile = I('post.mobile', '', 'strval');         // 电话
        $plat   = I('post.plat', '', 'strval');           // 平台
        $uniq_identify = I('post.uniq_identify', '', 'trim');  // 设备唯一标识
        // 校验用户
        $this->check();
        // 参数检测
        if (!checkMobile($mobile)) {
            $this->outPut(null, 1023);
        }

        $discount = D('Discount');
        // $this->uid = 1;
        $res = $discount->discountBuy($this->uid, $id, $mobile, $price, $fixed, $plat, $uniq_identify);

        // 结果处理
        if (!$res || isset($res['error'])) {
            $this->_writeDBErrorLog($res, $discount, 'api');
            $this->outPut(null, 3018, null, ternary($res['error'], ''));
        }

        $this->outPut($res, 0);
    }

    /*
     * 优惠买单/确定支付,付款
     */
    public function okpay() {
        // 接收参数
        $this->_checkblank(array('orderId', 'payAction', 'plat'));
        $orderId = I('post.orderId', 0, 'intval');
        $payAction = I('post.payAction', '', 'strval');
        $plat = I('post.plat', '', 'strval');

        // 校验用户
        $this->check();

        // 验证支付方式
        $discount = D('Discount');
        $payAction = strtolower(trim($payAction));
        if (!$discount->isPayAction($payAction)) {
            $this->outPut(null, 3015);
        }

        // 优惠买单支付
        $res = $discount->discountPay($this->uid, $orderId, $payAction, $plat);
        // 结果处理
        if (!$res || isset($res['error'])) {
            $this->_writeDBErrorLog($res, $discount, 'api');
            if ($payAction == 'creditpay') {
                $this->outPut(null, 3016, null, ternary($res['error'], ''));
            }
            $this->outPut(null, 3014, null, ternary($res['error'], ''));
        }
        $this->outPut($res, 0);
    }

    /*
     * 优惠买单/我的订单
     */
    public function orders() {
        // 校验用户
        $this->check();

        // 接收参数
        $data = array(
            'lastId' => I('get.lastId', ''),
        );

        $limit_num = 9;
        $discount = D('Discount');
        $pdata = $discount->getOrders($this->uid, $data, $limit_num);
        if (!$pdata || !is_array($pdata)) {
            $pdata = array();
        } else {
            foreach($pdata as  $k => $v) {
                $pdata[$k]['title'] = M('partner')->where(array('id'=>$v['partner_id']))->getField('title');
            }
        }
        $hasnext = $this->setHasNext(false, $pdata, $limit_num - 1);
        $this->setHasNext($hasnext);
        $this->outPut(array_values($pdata), 0);
    }

}