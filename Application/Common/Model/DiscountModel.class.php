<?php
/**
 * Created by PhpStorm.
 * User: zhoombin@126.com
 * Date: 2016/4/21
 * Time: 16:32
 */

namespace Common\Model;

use Common\Model\CommonModel;

class DiscountModel extends CommonModel {

    const PAYMENT_DEAL_TIME = 3;

    // 支付方式
    private $_payAction = array(
        'creditpay' => '余额支付',
        'credit' => '余额支付',
        'tenpay' => '财付通支付',
        'alipay' => '支付宝支付',
        'umspay' => '全民付客户端',
        'wechatpay' => '微信支付',
        'wxpay' => '微信支付',
        'unionpay' => '银联支付',
        'lianlianpay' => '连连支付',
        'wapepay' => 'e支付',
        'wepay' =>  '京东支付'
    );
    private $payType = array(
        'alipay' => '支付宝',
        'pcalipay' => 'pc支付宝',
        'tenpay' => '财付通',
        'pctenpay' => 'pc财付通',
        'pcwxpaycode' => 'pc微信扫码支付',
        'wxpay' => '客户端微信支付',
        'umspay' => '银联全民捷付',
        'unionpay' => '银联支付',
        'wapunionpay' => 'wap银联支付',
        'lianlianpay' => '连连支付',
        'wapalipay' => 'wap支付宝',
        'waptenpay' => 'wap财付通',
        'wapumspay' => 'wap全民付',
        'wapwechatpay' => 'wap微信',
        'wepay' =>  '京东支付'
    );

    /**
     * 验证支付方式
     */
    public function isPayAction($payAction) {

        if (!trim($payAction)) {
            return false;
        }
        return isset($this->_payAction[$payAction]);
    }

    /**
     * 团单付款
     * @param type $uid
     * @param type $orderId
     * @param type $payAction
     * @param type $plat
     * @return type
     */
    public function discountPay($uid, $orderId, $payAction, $plat) {

        // 获取订单信息
        $order = M('discountOrder');
        $orderRes = $order->where(array('id' => $orderId, 'user_id' => $uid))->find();
        if (!$orderRes) {
            return array('error' => '订单不存在！');
        }

        if (!isset($orderRes['state']) || strtolower(trim($orderRes['state'])) == 'pay') {
            return array('error' => '该订单已经支付，不能重复支付！');
        }

        $partnerId = $orderRes['partner_id'];
        $partner = M('partner');
        $partnerRes = $partner->where(array('id' => $partnerId))->find();
        if (!$partnerRes) {
            return array('error' => '商户不存在！');
        }

        $pay_id  = $orderRes['pay_id'];
        $money   = $orderRes['money'];
        $title   = '实时消费';
        $product = '实时消费';

        // 根据非法支付方式获取相关参数信息
        $data = array('order_id' => $orderId, 'payAction' => $payAction);
        $pay = new \Common\Org\Pay();
        switch ($payAction) {
            case 'tenpay':

                // 判断该平台是否支持财付通支付
                if (!C('TEN_PAY')) {
                    return array('error' => '不支持财付通支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getTenPayData($pay_id, $title, $product, $payFee, $plat, $orderId);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'alipay':

                // 判断该平台是否支持支付宝支付
                if (!C('ALI_PAY')) {
                    return array('error' => '不支持支付宝支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getALiPayData($pay_id, $title, $product, $payFee, $plat);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'umspay':

                // 判断该平台是否支持全民付支付
                if (!C('UMS_PAY')) {
                    return array('error' => '不支持全民付支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getUmsPayData($pay_id, $title, $product, $payFee, $plat);
                if (!isset($payRes['content']) || !isset($payRes['transId'])) {
                    return false;
                }
                $data['pay_url_params'] = array('payKey' => $payRes['content']);
                // 全民付 保存TransId
                $res = $order->where(array('id' => $orderRes['id']))->save(array('trade_no' => $payRes['transId']));
                if ($res === false) {
                    return array('error' => '全民付支付参数获取失败！');
                }
                break;
            case 'wechatpay':

                // 判断该平台是否支持微信支付
                if (!C('WX_PAY')) {
                    return array('error' => '不支持微信支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getWXPayData($pay_id, $title, $product, $payFee, $plat);
                if (isset($payRes['error'])) {
                    return $payRes;
                }
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'unionpay':

                // 判断该平台是否支持银联支付支付
                if (!C('UNION_PAY')) {
                    return array('error' => '不支持银联支付！');
                }
                $pay_id = str_replace('-', 'U', $orderRes['pay_id']);
                $res = $order->where(array('id' => $orderId, 'user_id' => $uid))->save(array('pay_id' => $pay_id));
                if ($res === false) {
                    return array('error' => '支付id更新失败！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getAppUnionPayData($pay_id, $title, $product, $payFee, $plat);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'lianlianpay':

                // 判断该平台是否支持连连支付支付
                if (!C('LIANLIAN_PAY')) {
                    return array('error' => '不支持连连支付！');
                }
                // 获取用户信息
                $user_info = M('user')->where(array('id' => $uid))->field('id,create_time,mobile')->find();
                if (isset($orderRes['mobile']) && trim($orderRes['mobile'])) {
                    $user_info['mobile'] = $orderRes['mobile'];
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getAppLianlianPayData($pay_id, $title, $product, $payFee, $user_info);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'wepay':
                // 判断该平台是否支京东支付
                if (!C('WE_PAY')) {
                    return array('error' => '不支持京东支付！');
                }
                // 获取支付参数
                $payFee = sprintf("%.2f", $money);
                $payRes = $pay->getWePayData($pay_id, $title, $product, $payFee, $plat, $orderId);
                $data['pay_url_params'] = array('payKey' => $payRes);
                break;
            case 'wapepay':
                return false;
                break;
            case 'creditpay':
                // 余额支付
                if (isset($orderRes['state']) && strtolower(trim($orderRes['state'])) == 'pay') {
                    return array('error' => '该订单已经支付，不能重复支付！');
                }
                $user = M('user');
                $userRes = $user->where(array('id' => $uid))->field(array('money'))->find();
                if (!$userRes) {
                    return array('error' => '该用户不存在！');
                }
                $card_money = 0;
                if (isset($orderRes['card_id']) && trim($orderRes['card_id'])) {
                    $cardRes = M('card')->where(array('id' => $orderRes['card_id']))->getField('credit');
                    if ($cardRes) {
                        $card_money = $cardRes;
                    }
                }
                if (!isset($userRes['money']) || $userRes['money'] < 0 || !isset($orderRes['origin']) || sprintf("%.2f", $userRes['money'] + $card_money) < $orderRes['origin']) {
                    return array('error' => '余额不足！');
                }
                // 支付
                if (isset($orderRes['rstate']) && trim($orderRes['rstate']) != 'normal') {
                    return array('error' => '订单状态错误！');
                }
                $res = $this->updateOrderUser($orderRes, 0, 'CNY', 'credit', '', '');
                if (!$res) {
                    return array('error' => '余额支付失败！');
                }
                break;
            default:
                return false;
                break;
        }

        // 更新支付类型
        if (isset($data['pay_url_params'])) {
            M('order')->where(array('id' => $orderId))->save(array('service' => $payAction));
        }

        return $data;
    }

    /*
     * 第三方支付回调
     */
    public function paySuccess($pay_id, $total_fee, $payAction, $trade_no) {
        $order = M('discountOrder');
        $order_id = trim($pay_id);
        if (strpos($order_id, '-') !== false) {
            list($_, $order_id, $_, $_) = @explode('-', $order_id, 4);
        }
        $where['id'] = $order_id;
        $orderRes = $order->where($where)->order('origin DESC')->select();
        $orderCount = count($orderRes);
        if ($orderCount < 1) {
            return array('error' => 'fail');
        }
        if ($orderCount == 1) {
            $orderRes = array_pop($orderRes);
            if ($orderRes && isset($orderRes['state']) && $orderRes['state'] == 'unpay') {
                // 支付成功后更新数据库信息
                $upRes = $this->updateOrderUser($orderRes, $total_fee, 'CNY', $payAction, $this->payType[$payAction], $trade_no);
                if (!$upRes) {
                    return array('error' => 'fail');
                }
            }
            return array('message' => 'success');
        }
    }

    /**
     * 保存流水信息收入
     * @param type $order
     * @param type $money
     * @param type $state
     * @param type $action
     * @return type
     */
    public function addFlowData($order, $money, $state, $action) {
        $data = array(
            'user_id' => $order['user_id'],
            'money' => $money,
            'direction' => $state,
            'action' => $action,
            'detail_id' => 0,
            'create_time' => time(),
            'team_id' => 0,
            'partner_id' => $order['partner_id'],
            'marks' => '',
        );
        if (($action == 'refund' || $action == 'paycharge') && isset($order['pay_id'])) {
            $data['detail_id'] = $order['pay_id'];
        }
        return M('flow')->add($data);
    }

    /**
     * 支付回调成功后修改相关信息
     * @param type $orderRes
     * @param type $money
     * @param type $currency
     * @param type $service
     * @param type $bank
     * @param type $trade_no
     */
    public function updateOrderUser($orderRes, $money, $currency = '', $service = '', $bank = '', $trade_no = '') {

        // 用户余额扣减
        $user = M('user');
        $userRes = $user->where(array('id' => $orderRes['user_id']))->find();
        if (!$userRes) {
            return false;
        }

        // 更新订单
        $order = M('discountOrder');
        $_credit = sprintf("%.2f", $orderRes['credit']);
        if (!$_credit || $_credit < 0) {
            $_credit = 0;
        }

        // 流水数量
        $pay = M('pay');
        $pay_count_res = $pay->where(array('id' => $orderRes['pay_id'], 'order_id' => $orderRes['id']))->count();

        // 事务开启
        $model = M();
        $model->startTrans();

        $data = array(
            'service' => $service,
            'state' => 'pay',
            'trade_no' => $trade_no,
            'money' => $money,
            'credit' => $_credit,
        );
        $res = $order->where(array('id' => $orderRes['id']))->save($data);

        if (!$res) {
            $this->errorInfo['info'] = $order->getDbError();
            $this->errorInfo['sql'] = $order->_sql();
            $model->rollback();
            return false;
        }
        $res = $order->where(array('id' => $orderRes['id']))->save(array('pay_time' => time()));

        // 添加支付流水
        if ($money > 0 && trim($trade_no)) {
            if ($pay_count_res && $pay_count_res > 0) {
                return false;
            }
            $pdata = array(
                'id' => $orderRes['pay_id'],
                'vid' => $trade_no,
                'order_id' => $orderRes['id'],
                'bank' => $bank,
                'money' => $money,
                'currency' => $currency,
                'service' => $service,
                'create_time' => time()
            );
            $res = $pay->add($pdata);
            if (!$res) {
                $this->errorInfo['info'] = $pay->getDbError();
                $this->errorInfo['sql'] = $pay->_sql();
                $model->rollback();
                return false;
            }
        }

        if (isset($userRes['money']) && $userRes['money'] > 0 && isset($data['credit']) && $data['credit'] > 0) {
            $userMoney = sprintf("%.2f", $userRes['money'] - $data['credit']);
            if ($userMoney < 0) {
                $userMoney = 0;
            }
            $res = $user->where(array('id' => $orderRes['user_id']))->save(array('money' => $userMoney));
            if (!$res) {
                $this->errorInfo['info'] = $user->getDbError();
                $this->errorInfo['sql'] = $user->_sql();
                $model->rollback();
                return false;
            }
        }

        // 添加流水信息
        if ($money > 0) {
            $res = $this->addFlowData($orderRes, $money, 'income', 'paycharge');
        }
        $res = $this->addFlowData($orderRes, $orderRes['origin'], 'expense', 'buy');
        if (!$res) {
            $model->rollback();
            return false;
        }

        $model->commit();

        return true;
    }

    /**
     * 优惠买单
     * @param type $uid
     * @param type $id
     * @param type $mobile
     * @param type $price
     * @param type $fixed
     * @param type $plat
     * @param type $uniq_identify
     */
    public function discountBuy($uid, $id, $mobile, $price, $fixed = 0, $plat = '', $uniq_identify = '') {
        $plat = strtolower(trim($plat));
        // 获取商户信息
        $partner = M('partner');
        $partnerRes = $partner->where(array('id'=>$id))->find();
        if (!$partnerRes) {
            return array('error' => '商户不存在');
        }
        $discountRes = M('discount')->where(array('partner_id'=>$partnerRes['id']))->find();
        // 时间段检测
        $nowTime = time();
        $start_time = $discountRes['start_time'];
        $end_time = $discountRes['end_time'];
        if ($nowTime < $start_time || $nowTime > $end_time) {
            return array('error' => '不在优惠时间段，无法买单');
        } else {
            // 当天时间段限制
            $nTime = date('His',$nowTime);
            $stime = date('His',$start_time);
            $etime = date('His',$end_time);
            if ($nTime < $stime || $nTime > $etime) {
                return array('error' => '不在优惠时间段，无法买单');
            }
        }

        // 下单入库操作
        $addOrderRes = $this->addOrder($uid, $id, $mobile, $price, $discountRes, $fixed, $plat, $uniq_identify);
        if (!$addOrderRes) {
            return array('error' => '下单入库失败');
        }
        list($orderId, $service, $credit, $money) = $addOrderRes;

        // 返回支付信息
        $data = array(
            'order_id' => $orderId,
            'partner_id' => $partnerRes['id'],
            'product' => '实时消费',
            'price'   => sprintf("%.2f", $price),
            'credit'  => sprintf("%.2f", $credit),
            'money'   => $money,
            'pay_type' => array()
        );
        // 余额支付
        if (trim($service) == 'credit') {
            $data['creditpay'] = true;
            return $data;
        }

        // 其他方式支付
        $data['wechatpay'] = C('WX_PAY');
        $data['unionpay'] = C('UNION_PAY');
        $data['lianlianpay'] = C('LIANLIAN_PAY');
        $data['alipay'] = C('ALI_PAY');
        $data['tenpay'] = C('TEN_PAY');
        $data['umspay'] = C('UMS_PAY');
        $data['wapepay'] = C('E_PAY');
        $data['wepay'] = C('WE_PAY');

        $data['pay_type'] = array(
            array('alipay' => C('ALI_PAY')),
            array('wechatpay' => C('WX_PAY')),
            array('unionpay' => C('UNION_PAY')),
            array('lianlianpay' => C('LIANLIAN_PAY')),
            array('tenpay' => C('TEN_PAY')),
            array('umspay' => C('UMS_PAY')),
            array('wapepay' => C('E_PAY')),
            array('wepay' => C('WE_PAY')),
        );

        // ios 过滤京东支付
        if ($plat == 'ios') {
            unset($data['wepay'],$data['pay_type'][7]);
        }

        return $data;
    }

    /**
     * 订单入库
     */
    public function addOrder($uid, $id, $mobile, $price, $discount, $fixed = 0, $plat = '', $uniq_identify = '') {
        $sourcePlat = strtolower(trim($plat));
        if (!trim($plat)) {
            $sourcePlat = '-未知来源-' . $plat;
        }
        $partner = M('partner');
        $partnerRes = $partner->where(array('id'=>$id))->find();
        if (!$partnerRes) {
            return false;
        }

        $user_buy_ip = get_client_ip(0, true);
        if ($user_buy_ip) {
            $expressQuery = new \Common\Org\ExpressQuery();
            $user_buy_city_name = $expressQuery->getIPLoc_sina($user_buy_ip);
        }

        // 需要付款总金额
        $origin = round(($price - $fixed) * $discount['ratio'] + $fixed, 2);

        // 付款方式
        $user = M('user');
        $userRes = $user->where(array('id' => $uid))->find();
        $service = 'tenpay';
        $credit = 0;
        if (isset($userRes['money']) && $userRes['money'] > 0) {
            $userRes['money'] = round(doubleval($userRes['money']), 2);
            $credit = $userRes['money'];
            if ($userRes['money'] >= $origin) {
                $credit = $origin;
                $service = 'credit';
            }
        }

        if($origin <= 0){
            $credit = 0;
            $service = 'credit';
        }

        // 查看该用户 该产品是否存在未付款订单
        $order = M('discountOrder');
        $where = array('partner_id' => $id, 'user_id' => $uid, 'state' => 'unpay', 'is_display' => 'Y');
        $orderRes = $order->where($where)->field('id')->find();

        // 开启事务回滚
        $model = M();
        $model->startTrans();
        $money = sprintf("%.2f", $origin - $credit);
        $randId = strtr($money, array('.' => '-', ',' => '-'));
        if ($orderRes) {
            // 更新该订单
            $orderId = ternary($orderRes['id'], '');
            $updateData = array(
                'pay_id'  => "dis-$orderId-0-$randId",
                'city_id' => ternary($partnerRes['city_id'], 0),
                'partner_id' => $id,
                'mobile'  => $mobile,
                'price'   => $price,
                'fixed'   => $fixed,
                'ratio'   => $discount['ratio'],
                'aratio'  => $discount['aratio'],
                'origin'  => $origin,
                'credit'  => $credit,
                'money'   => $money,
                'service' => $service,
                'laiyuan' => $sourcePlat,
                'yuming'  => $sourcePlat,
                'device_uniq_identify' => $uniq_identify,
            );
            if ($plat != 'ios' && $plat != 'android' && $plat != 'wap') {
                unset($updateData['pay_id']);
            }

            if (!trim($uniq_identify)) {
                unset($updateData['device_uniq_identify']);
            }

            // 添加购买时的ip 和城市名称
            if ($user_buy_ip) {
                $updateData['user_buy_ip'] = $user_buy_ip;
                $updateData['user_buy_city_name'] = $user_buy_city_name;
            }

            // 更新订单
            $res = $order->where(array('id' => $orderId))->save($updateData);
            if ($res === false) {
                $this->errorInfo['info'] = $order->getDbError();
                $this->errorInfo['sql'] = $order->_sql();
                $model->rollback();
                return false;
            }
        } else {
            // 添加订单
            $data = array(
                'user_id' => $uid,
                'city_id' => ternary($partnerRes['city_id'], 0),
                'partner_id' => $id,
                'mobile'  => $mobile,
                'price'   => $price,
                'fixed'   => $fixed,
                'ratio'   => $discount['ratio'],
                'aratio'  => $discount['aratio'],
                'origin'  => $origin,
                'credit'  => $credit,
                'money'   => $money,
                'service' => $service,
                'yuming'  => $sourcePlat,
                'laiyuan' => $sourcePlat,
                'state'   => 'unpay',
                'device_uniq_identify' => $uniq_identify,
                'create_time' => time()
            );
            if (!trim($uniq_identify)) {
                unset($data['device_uniq_identify']);
            }
            // 添加购买时的ip 和城市名称
            if ($user_buy_ip) {
                $updateData['user_buy_ip'] = $user_buy_ip;
                $updateData['user_buy_city_name'] = $user_buy_city_name;
            }
            $orderId = $order->add($data);
            if (!$orderId) {
                $this->errorInfo['info'] = $order->getDbError();
                $this->errorInfo['sql'] = $order->_sql();
                $model->rollback();
                return false;
            }

            $updatedata = array('pay_id' => "dis-$orderId-0-$randId");
            $res = $order->where(array('id' => $orderId))->save($updatedata);
            if (!$res) {
                $this->errorInfo['info'] = $order->getDbError();
                $this->errorInfo['sql'] = $order->_sql();
                $model->rollback();
                return false;
            }
        }

        // 纪录客户端下单纪录
        $referer = M('referer');
        $data = array('user_id' => $uid, 'order_id' => $orderId);
        $refererRes = $referer->where($data)->find();
        $data['referer'] = $sourcePlat;
        if ($refererRes) {
            $res = $referer->where(array('user_id' => $uid, 'order_id' => $orderId))->save($data);
        } else {
            $data = array(
                'user_id' => $uid,
                'order_id' => $orderId,
                'referer' => $sourcePlat,
                'create_time' => time()
            );
            $res = $referer->add($data);
        }
        if ($res === false) {
            $this->errorInfo['info'] = $referer->getDbError();
            $this->errorInfo['sql'] = $referer->_sql();
            $model->rollback();
            return false;
        }
        // 提交事务
        $model->commit();

        return array($orderId, $service, $credit, $money);

    }

    /*
     * 获取优惠买单订单
     */
    public function getOrders($uid, $data, $limit = 20) {
        // 返回字段名称
        $field = array(
            'discount_order.id' => 'order_id',
            'discount_order.partner_id'  => 'partner_id',
            'discount_order.create_time' => 'create_time',
            'discount_order.pay_time'    => 'pay_time',
            'discount_order.price'  => 'price',
            'discount_order.money'  => 'money',
            'discount_order.credit' => 'credit',
            'discount_order.state'  => 'state'
        );
        $order = M('discountOrder');
        $sort['discount_order.pay_time'] = 'DESC';
        $where['discount_order.user_id'] = $uid;
        if ($data['lastId'] > 0) {
            $where['discount_order.pay_time'] = array('LT',$data['lastId']);
        }
//        $where['discount_order.state']   = 'unpay';
        $res = $order->field($field)->where($where)->order($sort)->limit($limit)->select();
        if (!$res) {
            $this->errorInfo['info'] = $order->getDbError();
            $this->errorInfo['sql'] = $order->_sql();
            return false;
        }
        return $res;
    }

    /**
     * 获取优惠买单申请提现
     * @param type $partner_id
     * @return boolean
     */
    public function paymentApply($partner_id = '') {

        if (!trim($partner_id)) {
            return false;
        }

        $discountOrder = M('discountOrder');
        $time          = time();
        $where         = array(
            'partner_id'  => $partner_id,
            'state'       => 'pay',
            'payed_id'    => 0,
            'create_time' => array('ELT', $time)
        );
        $amoneysql = $discountOrder->field('(origin - round(origin * aratio, 2)) as amoney')->where($where)->buildSql();
        $money = $discountOrder->table($amoneysql .' x')->sum('amoney');
        if ($money <= 10) {
            return false;
        }

        // 开启事务
        $model = M();
        $model->startTrans();

        // 添加申请结算表数据
        $nowTime    = time();
        $endTime    = strtotime('+' . self::PAYMENT_DEAL_TIME . ' day ');
        $data       = array(
            'partner_id'  => $partner_id,
            'money'       => $money,
            'end_time'    => $endTime,
            'create_time' => $nowTime,
            'is_express'  => 'D'
        );
        $partnerPay = M('partner_pay');
        $payedId    = $partnerPay->data($data)->add();
        if (!$payedId) {
            $model->rollback();
            $this->errorInfo['info'] = $partnerPay->getDbError();
            $this->errorInfo['sql']  = $partnerPay->_sql();
            return false;
        }

        // 修改商家结算表
        $res = $discountOrder->where($where)->save(array('payed_id' => $payedId));
        if (!$res) {
            $model->rollback();
            $this->errorInfo['info'] = $discountOrder->getDbError();
            $this->errorInfo['sql']  = $discountOrder->_sql();
            return false;
        }

        // 修改商家信息表
        $nowTime = date('Y-m-d H:i:s', $nowTime);
        $updata  = array(
            'partner_money' => 0.00,
            'remarks'       => "{$nowTime}{$money}元已申请提现，等待处理",
        );
        $partner = M('partner');
        $res     = $partner->where(array('id' => $partner_id))->save($updata);
        if (!$res) {
            $model->rollback();
            $this->errorInfo['info'] = $partner->getDbError();
            $this->errorInfo['sql']  = $partner->_sql();
            return false;
        }
        $model->commit();
        return true;
    }

    /**
     * 本月交易利润
     * @param type $city_id
     * @param type $apply_begin_time
     * @param type $apply_end_time
     * @return type
     */
    public function get_month_discount_profit($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0) {
        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }

        $where = array(
            'city_id' => $city_id,
            'state' => 'pay',
            'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );

        $field = array(
            'count(id)' => 'order_count',        // 订单数
            'sum(origin)' => 'order_sum_money',  // 交易金额
            'sum(round(origin * aratio, 2))' => 'profit_money',  // 利润
        );
        $all_partner_res = M('discountOrder')->where($where)->field($field)->find();

        return $all_partner_res;
    }
}