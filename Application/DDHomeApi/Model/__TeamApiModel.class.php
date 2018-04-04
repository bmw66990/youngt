<?php

/**
 * Created by PhpStorm.
 * User: runtoad
 * Date: 15-4-8
 * Time: 下午4:58
 */

namespace Api\Model;

use \Think\Model;

/**
 * Class UserTokenModel
 */
class TeamApiModel extends Model {

    // 分页每页显示的数据条数
    const PAGE_NUM = 20;
    // 地球半径
    const EARTH_RADIUS = 6378.138;
    // 限量订单超时时间
    const LIMITED_ORDER_TIME_OUT = 7200;
    
    protected $tableName = 'team';

    // 支付方式
    private $_payAction = array(
        'credit' => '余额支付',
        'tenpay' => '财付通支付',
        'alipay' => '支付宝支付',
        'umspay' => '全民付客户端',
        'wechatpay' => '微信支付',
        'wapepay' => 'e支付',
    );

    /**
     * 获取团单详情
     * @param type $id
     * @return type
     */
    public function teamDetail($id) {

        $team = M('team');
        $res = $team->where(array('id' => $id))->find();
        if (!$res) {
            return array('error' => '团单获取失败！');
        }
        return $res;
    }

    /**
     * 团单评论列表
     * @param type $teamId
     * @return type
     */
    public function commentList($where, $order) {

        if (!trim($order)) {
            $order = 'id desc';
        }
        $comment = M('comment');
        $res = $comment->where($where)->limit(self::PAGE_NUM)->order($order)->select();

        if (!$res) {
            return array('error' => '评论列表获取失败！');
        }
        return $res;
    }

    /**
     * 获取商家详情
     * @param type $partnerId
     * @return type
     */
    public function partnerDetail($partnerId) {
        $partner = M('partner');
        $res = $partner->where(array('id' => $partnerId))->find();
        if (!$res) {
            return array('error' => '商家详情获取失败！');
        }
        return $res;
    }

    /**
     * 获取相同商户的其他团单
     * @param type $where
     * @return type
     */
    public function samePartnerOtherTeam($where, $order, $pageNum = 20) {

        if (!$order) {
            $order = 'id asc';
        }
        if (!trim($pageNum)) {
            $pageNum = self::PAGE_NUM;
        }
        $team = M('team');
        $res = $team->where($where)->order($order)->limit($pageNum)->select();
        if (!$res) {
            return array('error' => '团单获取失败！');
        }
        return $res;
    }

    /**
     * 限量团单
     */
    public function limitTeam($where, $order) {

        if (!trim($order)) {
            $order = 'id asc';
        }

        $team = M('team');
        $where['team_type'] = 'limited';
        $res = $team->where($where)->limit(self::PAGE_NUM)->order($order)->select();
        if (!$res) {
            return array('error' => '团单获取失败！');
        }
        return $res;
    }

    /**
     * 秒杀团单
     */
    public function secondKillTeam($where, $order) {

        if (!trim($order)) {
            $order = 'id asc';
        }

        $team = M('team');
        $where['team_type'] = 'timelimit';

        $res = $team->where($where)->order($order)->limit(self::PAGE_NUM)->select();
        if (!$res) {
            return array('error' => '团单获取失败！');
        }
        return $res;
    }

    /**
     * 今日新单/今日推荐新单
     */
    public function todayRecommendTeam($last_time = '') {

        $now_time = time();
        if (!trim($last_time)) {
            $last_time = $now_time;
        }

        $start_time = strtotime('-3 day ' . date('Y-m-d'));

        $team = M('team');
        $where = array('begin_time' => array('EGT', $start_time), 'end_time' => array('LT', $now_time), 'team_type' => 'normal');
        $_where = "begin_time<$last_time";
        $res = $team->where($where)->where($_where)->order('begin_time desc')->limit(self::PAGE_NUM)->select();
        //exit($res);

        if (!$res) {
            return array('error' => '暂无今日团单！');
        }
        return $res;
    }

    /**
     * 附近团单
     */
    public function nearByTeam($lng, $lat, $lastId = 0, $distance = 1) {

        if (!trim($lng) || !trim($lat)) {
            return array('error' => '用户当前地理位置不能为空！');
        }

        // 查询where条件
        $lngLatSquarePoint = $this->__returnSquarePoint($lng, $lat, $distance);

        $_where['long'] = array('EGT', $lngLatSquarePoint['left-top']['lng']);
        $_where['lat'] = array('EGT', $lngLatSquarePoint['left-bottom']['lat']);
        $where = "`long`<={$lngLatSquarePoint['right-bottom']['lng']} AND `lat`<={$lngLatSquarePoint['left-top']['lat']}";
        $outWhere = "t.distance>$lastId and t.distance<=$distance AND t.distance IS NOT NULL";

        // 返回的字段
        $distanceField = $this->__getMysqlDistanceField($lat, $lng);
        $field = array(
            'id' => 'partner_id',
            'group_id' => 'group_id',
            'title' => 'part_title',
            'images' => 'images',
            'long' => 'long',
            'lat' => 'lat',
            'username' => 'username',
            $distanceField => 'distance'
        );

        // 排序字段
        $sort = array('distance' => 'asc');

        $partner = M('partner');
        $subQuery = $partner->where($where)->where($_where)->field($field)->order($sort)->select(false);
        $res = $partner->table("($subQuery) as t")->where($outWhere)->limit(self::PAGE_NUM)->select();
        if (!$res) {
            return array();
        }

        return $res;
    }

    /**
     * 团单购买
     * @param type $uid
     * @param type $id
     * @param type $mobile
     * @param type $plat
     */
    public function teamBuy($uid, $id, $num = 1, $mobile, $plat = '') {
       
        if (!trim($id)) {
            return array('error' => '商品id不能为空！');
        }
        if (!trim($plat)) {
            return array('error' => '平台不能为空！');
        }
        if (intval(trim($num)) <= 0) {
            return array('error' => '购买商品的数量必须大于0！');
        }
        if (!trim($mobile) || !checkMobile($mobile)) {
            return array('error' => '手机号码错误！');
        }

        // 获取商品的基本信息
        $team = M('team');
        $teamRes = $team->where(array('id' => $id))->find();
        if (!$teamRes) {
            return array('error' => '你购买的商品不存在！');
        }
        $endTime = $teamRes['end_time'];
        $nowTime = time();
        if ($teamRes['team_type'] != 'timelimit' && $endTime < $nowTime) {
            return array('error' => '你购买的商品已经过期！');
        }

        // 查询订单
        $order = M('order');
        $where = array('team_id' => $id, 'user_id' => $uid, 'state' => 'pay');
        $orderCount = $order->where($where)->sum('quantity');

        // 是否只能购买一次
        if (isset($teamRes['buyonce']) && strtolower(trim($teamRes['buyonce'])) == 'y' && $orderCount > 0) {
            return array('error' => '您已经成功购买了本单产品，请勿重复购买!');
        }
        // 最低购买的份数限制
        if (isset($teamRes['permin_number']) && intval($teamRes['permin_number']) > 0 && intval($teamRes['permin_number']) > $num) {
            return array('error' => '本商品最低购买:' . $teamRes['permin_number'] . '件！');
        }
        // 每人限购的份数判断
        if (isset($teamRes['per_number']) && intval($teamRes['per_number']) > 0 && intval($teamRes['per_number']) < intval($num + $orderCount)) {
            return array('error' => '该商品每人限购' . $teamRes['per_number'] . '件！');
        }

        // 根据团单类型 处理不同团单
        $teamType = isset($teamRes['team_type']) ? strtolower(trim($teamRes['team_type'])) : '';
        
        switch ($teamType) {
            case 'normal':

                // 判断商品数量充足
                if (isset($teamRes['now_number']) && isset($teamRes['max_number']) && trim($teamRes['max_number']) && $num + $teamRes['now_number'] > $teamRes['max_number']) {
                    return array('error' => '本产品数量不足，无法购买！');
                }
                break;
            case 'newuser':

                // 判断是否为新用户
                $where = array('user_id' => $uid, 'state' => 'pay');
                $newUser = $order->where($where)->find();
                if ($newUser) {
                    return array('error' => '您已不是新用户，请关注本店其他产品!');
                }
                break;
            case 'limited':

                // 查看每人限购的数量
                $where = array('team_id' => $id, 'state' => 'pay', 'user_id' => $uid);
                if (isset($teamRes['flv']) && strtolower(trim($teamRes['flv'])) == 'y') {
                    $where['pay_time'] = array('EGT' => strtotime(date('Y-m-d')));
                }
                $userCount = $order->where($where)->sum('quantity');
                if (isset($teamRes['per_number']) && $userCount > intval($teamRes['per_number'])) {
                    return array('error' => '超出每人限购数量！');
                }

                // 查看该商品限购数量
                unset($where['user_id']);
                $payCount = $order->where($where)->sum('quantity');
                if (isset($teamRes['max_number']) && $payCount >= $teamRes['max_number']) {
                    return array('error' => '活动已结束!');
                }
                $expire_time = time() - self::LIMITED_ORDER_TIME_OUT;
                $where['state'] = 'unpay';
                $where['create_time'] = array('EGT' => $expire_time);
                if (isset($where['pay_time'])) {
                    unset($where['pay_time']);
                }
                $unpayCount = $order->where($where)->sum('quantity');
                if (isset($teamRes['max_number']) && $unpayCount + $payCount >= $teamRes['max_number']) {
                    return array('error' => '活动已结束!');
                }
                break;
            case 'timelimit':
                // 秒杀团单购买！
                $beginTime = $teamRes['begin_time'];
                $endTime = $teamRes['end_time'];
                if (isset($teamRes['flv']) && strtolower(trim($teamRes['flv'])) == 'y') {
                    $beginTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $beginTime));
                    $endTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $endTime));
                }
                if ($nowTime < $beginTime || $nowTime > $endTime) {
                    return array('error' => '该产品已过期，不能下单！');
                }

                break;
            default:
                return array('error' => '团单暂不支持购买！');
                break;
        }

        // 下单
        $sourcePlat = $plat;
        if (!trim($plat)) {
            $sourcePlat = '-未知来源-' . $plat;
        }

        // 总价格
        $origin = $teamRes['team_price'] * $num;

        // 付款方式
        $user = M('user');
        $userRes = $user->where(array('id' => $uid))->find();
        $service = 'credit';
        $credit = '';
        if ($userRes['money'] < $origin) {
            $service = 'tenpay';
            $credit = $userRes['money'];
        }

        // 查看该用户 该产品是否存在未付款订单
        $where = array('team_id' => $id, 'user_id' => $uid, 'state' => 'unpay', 'rstate' => 'normal');
        $orderRes = $order->where($where)->find();
        $orderId = '';
        if ($orderRes) {
            // 更新该团单
            $orderId = $orderRes['id'];
            $updateData = array(
                'quantity' => $num,
                'origin' => $origin,
                'team_type' => $teamRes['team_type'],
                'price' => $teamRes['price'],
                'mobile' => $mobile,
                'service' => $service,
                'credit' => $credit,
                'laiyuan' => $sourcePlat,
                'money' => $origin-$credit,
                'yuming' => $sourcePlat,
            );

            // 更新订单
            $order->where(array('id' => $orderId))->save($updateData);
        } else {
            // 添加订单
            $data = array(
                'user_id' => $uid,
                'allowrefund' => $teamRes['allowrefund'],
                'quantity' => $num,
                'team_id' => $id,
                'city_id' => $teamRes['city_id'],
                'partner_id' => isset($teamRes['partner_id']) ? $teamRes['partner_id'] : 0,
                'price' => $teamRes['team_price'],
                'team_type' => 'normal',
                'origin' => $origin,
                'yuming' => $sourcePlat,
                'laiyuan' => $sourcePlat,
                'mobile' => $mobile,
                'state' => 'unpay',
                'credit' => $credit,
                'money' => $origin-$credit,
                'service' => $service,
                'create_time' => time()
            );
            $orderId = $order->add($data);
            $randId = strtolower($this->__getRandId(4));
            $updatedata = array('pay_id' => "go-$orderId-$num-$randId");
            $order->where(array('id' => $orderId))->save($updatedata);
        }


        // 纪录客户端下单纪录
        $referer = M('referer');
        $data = array('user_id' => $uid, 'order_id' => $orderId);
        $refererRes = $referer->where($data)->find();
        $data['referer'] = $sourcePlat;
        if ($refererRes) {
            $referer->where(array('user_id' => $uid, 'order_id' => $orderId))->save($data);
        } else {
            $data = array(
                'user_id' => $uid,
                'order_id' => $orderId,
                'referer' => $sourcePlat,
                'create_time' => time()
            );
            $referer->add($data);
        }

        // 返回支付信息
        $data = array(
            'order_id' => $orderId,
            'team_price'=>$teamRes['team_price'],
            'all_price'=>$origin,
            'num'=>$num,
            'product'=>$teamRes['product'],
            'credit'=>$credit,
            'money' => $origin-$credit,
        );

        // 现金支付
        if (trim($service) == 'credit') {
            $data['creditpay'] = true;
            return array('message' => '下单成功！', 'data' => $data);
        }

        // 其他方式支付
        $data['tenpay'] = C('TEN_PAY');
        $data['alipay'] = C('ALI_PAY');
        $data['umspay'] = C('UMS_PAY');
        $data['wechatpay'] = C('WX_PAY');
        $data['wapepay'] = C('E_PAY');

        return array('message' => '下单成功！', 'data' => $data);
    }

    /**
     * 团单付款
     * @param type $uid
     * @param type $orderId
     * @param type $payAction
     * @param type $plat
     * @return type
     */
    public function teamPay($uid, $orderId, $payAction, $plat) {

        if (!trim($uid)) {
            return array('error' => '用户未登录！');
        }
        if (!trim($orderId)) {
            return array('error' => '订单id不能为空！');
        }
        if (!trim($plat)) {
            return array('error' => '平台不能为空！');
        }
        if (!trim($payAction)) {
            return array('error' => '支付方式不能为空！');
        }
        $payAction = strtolower(trim($payAction));
        if (!isset($this->_payAction[$payAction])) {
            return array('error' => '非法支付方式！');
        }

        // 获取团单信息
        $order = M('order');
        $orderRes = $order->where(array('id' => $orderId, 'user_id' => $uid))->find();
        if (!$orderRes) {
            return array('error' => '该订单不存在！');
        }
        if (!isset($orderRes['state']) || strtolower(trim($orderRes['state'])) == 'pay') {
            return array('error' => '给订单已经支付，请不要重复支付！');
        }
        $teamId = $orderRes['team_id'];
        $team = M('team');
        $teamRes = $team->where(array('id' => $teamId))->find();
        if (!trim($teamId) || !$teamRes) {
            return array('error' => '你购买的该产品已经不存在了！');
        }

        // 团单时间判断
        $nowTime = time();
        $beginTime = $teamRes['begin_time'];
        $endTime = $teamRes['end_time'];
        if (isset($teamRes['team_type']) && $teamRes['team_type'] != 'timelimit' && isset($teamRes['flv']) && strtolower(trim($teamRes['flv'])) == 'y') {
            $beginTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $beginTime));
            $endTime = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $endTime));
        }
        if ($nowTime < $beginTime || $nowTime > $endTime) {
            return array('error' => '该产品已过期，不能购买！');
        }

        // 判断商品数量充足
        if (isset($teamRes['now_number']) && isset($teamRes['max_number']) && trim($teamRes['max_number']) && isset($orderRes['quantity']) && $orderRes['quantity'] + $teamRes['now_number'] > $teamRes['max_number']) {
            return array('error' => '本产品数量不足，无法购买！');
        }

        // 根据非法支付方式获取相关参数信息
        $data = array('order_id' => $orderId,'payAction'=>$payAction);
        $pay = new \Common\Org\Pay();
        switch ($payAction) {
            case 'tenpay':

                // 判断该平台是否支持财付通支付
                if (!C('TEN_PAY')) {
                    return array('error' => '暂时不支持财付通支付！');
                }

                // 获取支付参数
                $payFee = $orderRes['origin'] - $orderRes['credit'];
                $payRes = $pay->getTenPayData($orderRes['pay_id'], $teamRes['title'], $teamRes['product'], $payFee, $plat, $orderId);
                $data['pay_url_params'] = $payRes;

                break;
            case 'alipay':

                // 判断该平台是否支持支付宝支付
                if (!C('ALI_PAY')) {
                    return array('error' => '暂时不支持支付宝支付！');
                }

                // 获取支付参数
                $payFee = $orderRes['origin'] - $orderRes['credit'];
                $payRes = $pay->getALiPayData($orderRes['pay_id'], $teamRes['title'], $teamRes['product'], $payFee, $plat);
                $data['pay_url_params'] = $payRes;

                break;
            case 'umspay':

                // 判断该平台是否支持全民付支付
                if (!C('TEN_PAY')) {
                    return array('error' => '暂时不支持全民付支付！');
                }

                // 获取支付参数
                $payFee = $orderRes['origin'] - $orderRes['credit'];
                $payRes = $pay->getUmsPayData($orderRes['pay_id'], $teamRes['title'], $teamRes['product'], $payFee, $plat);
                $data['pay_url_params'] = $payRes;

                break;
            case 'wechatpay':

                // 判断该平台是否支持微信支付
                if (!C('WX_PAY')) {
                    return array('error' => '暂时不支持微信支付！');
                }

                // 获取支付参数
                $payFee = $orderRes['origin'] - $orderRes['credit'];
                $payRes = $pay->getWXPayData($orderRes['pay_id'], $teamRes['title'], $teamRes['product'], $payFee, $plat);
                if (isset($payRes['error'])) {
                    return $payRes;
                }
                $data['pay_url_params'] = $payRes;

                break;
            case 'wapepay':
                return array('error' => '暂不支持e支付');
                break;
            case 'creditpay':

                // 余额支付
                if (isset($orderRes['state']) && strtolower(trim($orderRes['state'])) == 'pay') {
                    return array('error' => '该订单已经支付，请勿重复支付');
                }
                $user = M('user');
                $userRes = $user->where(array('id' => $uid))->find();
                if (!$userRes) {
                    return array('error' => '该用户不存在');
                }
                if (!isset($userRes['money']) || !isset($orderRes['origin']) && intval($userRes['money']) < intval($orderRes['origin'])) {
                    return array('error' => '余额不足，无法购买');
                }

                // 支付
                if (isset($order['rstate']) && trim($order['rstate']) == 'normal') {
                    return array();
                }

                // 需开启事务
                $data = array(
                    'id' => $orderRes['id'],
                    'pay_id' => $orderRes['pay_id'],
                    'service' => 'credit',
                    'state' => 'pay',
                    'rstate' => 'normal',
                    'money' => 0,
                    'credit' => $orderRes['origin'],
                    'pay_time' => time()
                );
                $order->where(array('id' => $orderRes['id']))->save($data);

                // 更新团单已买数量
                $ots = new \Common\Org\OTS();
                $nowNumber = $orderRes['quantity'] + $teamRes['now_number'];
                $ots->updateRow('team', array('id' => $orderRes['team_id']), array('now_number' => $nowNumber));
                $team->where(array('id' => $orderRes['team_id']))->setInc('now_number', $orderRes['quantity']);

                // 扣减用户余额
                $user->where(array('id' => $userRes['id']))->save(array('money' => $userRes['money'] - $orderRes['origin']));

                // 添加流水
                $this->__addFlowData($orderRes, $orderRes['origin'], 'expense', 'buy');

                // 添加团购卷
                if ($orderRes['express'] == 'N') {
                    $this->__addCoupon($orderRes, $teamRes);
                }

                // 添加评论
                $comment = array(
                    'user_id' => $orderRes['user_id'],
                    'team_id' => $orderRes['team_id'],
                    'partner_id' => $orderRes['partner_id'],
                    'cate_id' => $teamRes['group_id'],
                    'order_id' => $orderRes['id'],
                    'isuser' => 'Y'
                );
                M('comment')->add($comment);

                // 购买成功后发送短信
                $this->__paySuccessSendSms($orderRes, $teamRes);
                break;
            default:
                return array('error' => '非法支付方式');
                break;
        }

        return array('message' => '操作成功！', 'data' => $data);
    }

    /**
     * 支付回调函数
     * @param type $data
     */
    public function payCallbackHandle($payAction, $data) {

        // 非法参数判断
        if (!trim($payAction)) {
            return array('error' => '回调支付方式不能为空！');
        }

        $pay = new \Common\Org\Pay();
        $order = M('order');
        $payAction = strtolower(trim($payAction));
        switch ($payAction) {
            case 'alipay':
                $res = $pay->getALiCallBackVerify();
                if (!$res) {
                    return array('error' => 'fail');
                }
                // 支付成功！
                if (isset($res['trade_status']) && $res['trade_status'] == "TRADE_SUCCESS") {
                    if (isset($res['refund_status'])) {
                        $refund_status = $isVerify['refund_status'];
                        $gmt_refund = $isVerify['gmt_refund'];
                        // Utils::ilog('支付宝-退款成功,退款订单ID:' . $v_oid . ' 退款时间：' . $gmt_refund, 'PAYERR');
                        return array('message' => 'success');
                    }

                    // 支付成功处理
                    list($_, $orderId, $_, $_) = explode('-', $res['out_trade_no'], 4);
                    $total_fee = $res['total_fee'];
                    $orderRes = $order->where(array('id' => $orderId))->find();
                    if ($orderRes && isset($orderRes['state']) && $orderRes['state'] == 'unpay' && isset($orderRes['rstate']) && $orderRes['rstate'] == 'normal') {
                        // 支付成功后更新数据库信息
                        $upRes = $this->__updateOrderUser($orderRes, $total_fee, 'CNY', $payAction, '支付宝', $res['trade_no']);
                        if (isset($upRes['error'])) {
                            return array('error' => 'fail');
                        }

                        return array('message' => 'success');
                    }
                    return array('error' => 'fail');
                }

                if (isset($res['trade_status']) && $res['trade_status'] == "WAIT_BUYER_PAY") {
                    return array('message' => 'success');
                }

                return array('error' => 'fail');
                break;
            case 'tenpay':
                $res = $pay->getTenCallBackVerify($_GET);
                if (!$res) {
                    return array('error' => 'fail');
                }
                list($_, $orderId, $_, $_) = explode('-', $res['sp_billno'], 4);
                $total_fee = $res['total_fee'];
                $orderRes = $order->where(array('id' => $orderId))->find();
                if ($orderRes && isset($orderRes['state']) && $orderRes['state'] == 'unpay' && isset($orderRes['rstate']) && $orderRes['rstate'] == 'normal') {
                    // 支付成功后更新数据库信息
                    $upRes = $this->__updateOrderUser($orderRes, $total_fee, 'CNY', $payAction, '财付通', $res['transaction_id']);
                    if (isset($upRes['error'])) {
                        return array('error' => 'fail');
                    }
                    return array('message' => 'success');
                }
                return array('error' => 'fail');
                break;
            case 'wxpay':
                $res = $pay->getWXCallBackVerify($_GET);
                if (!$res) {
                    return array('error' => 'fail');
                }

                list($_, $orderId, $_, $_) = explode('-', $res['out_trade_no'], 4);
                $total_fee = $res['total_fee'];
                $orderRes = $order->where(array('id' => $orderId))->find();
                if ($orderRes && isset($orderRes['state']) && $orderRes['state'] == 'unpay' && isset($orderRes['rstate']) && $orderRes['rstate'] == 'normal') {
                    // 支付成功后更新数据库信息
                    $upRes = $this->__updateOrderUser($orderRes, $total_fee, 'CNY', $payAction, '微信支付', $res['transaction_id']);
                    if (isset($upRes['error'])) {
                        return array('error' => 'fail');
                    }
                    return array('message' => 'success');
                }
                return array('error' => 'fail');

                break;
            case 'umspay':
                $res = $pay->getUmsCallBackVerify();
                if (!$res) {
                    return array('error' => 'fail');
                }

                list($_, $orderId, $_, $_) = explode('-', $res['MerOrderId'], 4);
                $total_fee = $res['TransAmt'];
                $orderRes = $order->where(array('id' => $orderId))->find();
                if ($orderRes && isset($orderRes['state']) && $orderRes['state'] == 'unpay' && isset($orderRes['rstate']) && $orderRes['rstate'] == 'normal') {
                    // 支付成功后更新数据库信息
                    $upRes = $this->__updateOrderUser($orderRes, $total_fee, 'CNY', $payAction, '银联全民捷付', $res['TransId']);
                    if (isset($upRes['error'])) {
                        return array('error' => 'fail');
                    }
                    return array('message' => 'success');
                }
                return array('error' => 'fail');
                break;
            default:
                break;
        }
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
    private function __updateOrderUser($orderRes, $money, $currency, $service, $bank, $trade_no) {

        // 添加支付流水
        $pay = M('pay');
        $res = $pay->where(array('id' => $orderRes['pay_id'], 'vid' => $trade_no, 'order_id' => $orderRes['id']))->find();
        if ($res) {
            return array('message' => '该支付流水已经添加！不用重复添加！');
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
        $pay->add($pdata);

        // 更新订单
        $order = M('order');
        $data = array(
            'service' => $service,
            'state' => 'pay',
            'trade_no' => $trade_no,
            'money' => $money,
            'credit' => $orderRes['origin'] - $money,
            'pay_time' => time()
        );
        $order->where(array('id' => $orderRes['id']))->save($data);

        $team = M('team');
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();

        // 团单已售数量更新
        $ots = new \Common\Org\OTS();
        $nowNumber = $orderRes['quantity'] + $teamRes['now_number'];
        $ots->updateRow('team', array('id' => $orderRes['team_id']), array('now_number' => $nowNumber));
        $team->where(array('id' => $orderRes['team_id']))->setInc('now_number', $orderRes['quantity']);

        // 用户余额扣减
        $user = M('user');
        $userRes = $user->where(array('id' => $orderRes['user_id']))->find();
        if (!$userRes) {
            return array('error' => '该用户不存在！');
        }
        $res = $user->where(array('id' => $orderRes['user_id']))->save(array('money' => $userRes['money'] - $data['credit']));

        // 添加流水信息
        $this->__addFlowData($orderRes, $money, 'income', 'paycharge');
        $this->__addFlowData($orderRes, $orderRes['origin'], 'expense', 'buy');

        // 添加团购卷
        if ($orderRes['express'] == 'N') {
            $this->__addCoupon($orderRes, $teamRes);
        }

        // 添加评论
        $comment = array(
            'user_id' => $orderRes['user_id'],
            'team_id' => $orderRes['team_id'],
            'partner_id' => $orderRes['partner_id'],
            'cate_id' => $teamRes['group_id'],
            'order_id' => $orderRes['id'],
            'isuser' => 'Y'
        );
        M('comment')->add($comment);

        // 购买成功后发送短信
        $this->__paySuccessSendSms($orderRes, $teamRes);
    }

    /**
     * 保存流水信息收入
     * @param type $order
     * @param type $money
     * @param type $state
     * @param type $action
     * @return type
     */
    private function __addFlowData($order, $money, $state, $action) {
        $flow = M('flow');
        $data = array();
        $data['user_id'] = $order['user_id'];
        $data['money'] = $money;
        $data['direction'] = $state;
        $data['action'] = $action;
        $data['detail_id'] = $order['team_id'];
        $data['create_time'] = time();
        return $flow->add($data);
    }

    /**
     * 添加团购卷
     */
    private function __addCoupon($orderRes, $teamRes) {

        $coupon = M('coupon');
        $count = $coupon->where(array('order_id' => $orderRes['id']))->count();
        $cids = array();
        if (isset($orderRes['quantity'])) {
            while ($count < $orderRes['quantity']) {
                $cid = mt_rand(200000, 999999) . mt_rand(100000, 999999) - time();
                $row = $coupon->where(array('id' => strval($cid)))->getField('id');
                if ($row) {
                    continue;
                }
                $cids[] = $cid;
                $count++;
            }
        }


        $partner_id = isset($teamRes['partner_id']) ? $teamRes['partner_id'] : '';
        if (!trim($partner_id) && isset($orderRes['partner_id'])) {
            $partner_id = $orderRes['partner_id'];
        }

        // 批量添加团卷
        foreach ($cids as $key => $val) {
            $coupon[$key] = array(
                'id' => $val,
                'user_id' => $orderRes['user_id'],
                'buy_id' => $orderRes['buy_id'],
                'partner_id' => $teamRes['partner_id'],
                'order_id' => $orderRes['id'],
                'credit' => $teamRes['credit'],
                'team_id' => $orderRes['team_id'],
                'secret' => '',
                'expire_time' => $teamRes['expire_time'],
                'create_time' => time()
            );
        };
        return $coupon->addAll($coupon);
    }

    /**
     * 支付成功后发送短信
     * @param type $orderRes
     * @param type $teamRes
     * @return type
     */
    private function __paySuccessSendSms($orderRes, $teamRes) {

        if (!isset($orderRes['mobile'])) {
            $orderRes['mobile'] = M('user')->where(array('id' => $orderRes['user_id']))->getField('mobile');
        }
        if (!isset($orderRes['mobile'])) {
            return array('error' => '电话号码为空！');
        }

        $sendSms = new \Common\Org\sendSms();

        // 获取发送内容
        $sellerPhone = M('partner')->where(array('id' => $orderRes['partner_id']))->getField('phone');
        $content = "您已经成功购买【{$teamRes['product']}】，请等待发货，商家电话 $sellerPhone ，详情请访问：m.youngt.com/team.php?id={$teamRes['id']}";
        if ($orderRes['express'] == 'N') {
            $coupons = M('coupon')->where(array('order_id' => $orderRes['id']))->select();
            if ($coupons) {
                $_coupon = array();
                foreach ($coupons as $i => $coupon) {
                    $_coupon[] = $coupon['id'];
                }
                $cids = implode(', ', $_coupon);
                $content = "【{$teamRes['product'] }】{$orderRes['quantity']}份，券号 $cids ，有效期至：" . date("Y-m-d", $teamRes["expire_time"]) . "，商家电话$sellerPhone 。";
            }
        }
        return $sendSms->sendMsg($orderRes['mobile'], $content);
    }

    /**
     * 团单搜索
     * @param type $data
     */
    public function teamSearch($data) {

        // 查询条件拼接
        $_where = '';
        if (isset($data['query']) && trim($data['query'])) {
            $_where = "title like '%{$data['query']}%' OR sel1 like '%{$data['query']}%' OR sel2 like '%{$data['query']}%' or sel3 like '%{$data['query']}%'";
        }

        // 获取where 和 排序字段
        list($where, $sort) = $this->getMysqlWhere($data);

        $team = M('team');
        $res = $team->where(array($_where))->where($where)->order($sort)->limit(self::PAGE_NUM)->select();

        return $res;
    }

    /**
     * 根据条件获取到商家数据
     * @param type $data
     * @return array
     */
    public function getTeamSearchPartner($data) {

        // 条件
        $where = "";
        $_where = array();
        $outWhere = array();

        // 返回字段名称
        $field = array(
            'id' => 'partner_id',
            'group_id' => 'group_id',
            'title' => 'part_title',
            'images' => 'images',
            'long' => 'long',
            'lat' => 'lat',
            'username' => 'username',
            'address' => 'address'
        );

        // 类型过滤
        if (isset($data['type']) && trim($data['type'])) {
            $_where['group_id'] = $data['type'];
            if (strpos($data['type'], '@') !== false) {
                @list($groupId, $subId) = explode('@', $data['type']);
                trim($groupId) && $_where['group_id'] = $groupId;
                trim($subId) && $_where['sub_id'] = $subId;
            }
        }

        // 城市过滤
        if (isset($data['cityId']) && trim($data['cityId'])) {
            $_where['city_id'] = $data['cityId'];
        }

        // 街道过滤
        if (isset($data['streetId']) && trim($data['streetId'])) {
            $_where['zone_id'] = $data['streetId'];
            if (strpos($data['streetId'], '@') !== false) {
                @list($zoneId, $stationId) = explode('@', $data['streetId']);
                trim($zoneId) && $_where['zone_id'] = $zoneId;
                trim($subId) && $_where['station_id'] = $stationId;
            }
        }

        // 经纬度条件删选
        if (isset($data['lng']) && trim($data['lng']) && isset($data['lat']) && trim($data ['lat'])) {
            $lng = $data['lng'];
            $lat = $data['lat'];
            $distance = isset($data['distance']) && trim($data['distance']) ? $data['distance'] : 1;

            $lngLatSquarePoint = $this->__returnSquarePoint($lng, $lat, $distance);
            $_where['long'] = array('EGT', $lngLatSquarePoint['left-top']['lng']);
            $_where['lat'] = array('EGT', $lngLatSquarePoint['left-bottom']['lat']);
            $where = "`long`<={$lngLatSquarePoint['right-bottom']['lng']} AND `lat`<={$lngLatSquarePoint['left-top']['lat']}";
            $outWhere['t.distance'] = array('ELT', $distance);
            $distanceField = $this->__getMysqlDistanceField($lat, $lng);
            $field[$distanceField] = 'distance';
        }

        // 排序方式
        $sort = array();
        if (isset($data['order']) && $data['order']) {

            $lastId = isset($data['lastId']) && trim() ? $data['lastId'] : 0;
            $sort['sort'] = 'ASC';
            $sortField = $this->__getMysqlSortField($data['order'], '+');
            trim($lastId) && $outWhere['t.sort'] = array('GT', $lastId);
            if (strpos($data['order'], '@') !== false) {
                list($sortType, $_sortField) = explode('@', $data['order']);
                if (trim($sortType) == '-') {
                    $sort['sort'] = 'DESC';
                    trim($lastId) && $outWhere['t.sort'] = array('LT', $lastId);
                    $sortField = $this->__getMysqlSortField($_sortField, '-');
                }
            }
            $field[$sortField] = 'sort';
        }
        $partner = M('partner');
        $subQuery = $partner->field($field)->where($_where)->where($where)->order($sort)->select(false);

        $res = $partner->table("($subQuery) as t")->where($outWhere)->where('t.sort IS NOT NULL')->limit(self::PAGE_NUM)->select();

        return $res;
    }

    /**
     * 获取搜索服务的查询条件
     * @param type $data
     * @return type
     */
    public function getSearchWhere($data) {
        // 拼接search查询条件
        $query = "";
        if (isset($data['query']) && trim($data['query'])) {
            $query = "title:'{$data['query']}' OR sel1:'{$data['query']}' OR sel2:'{$data['query']}' OR sel3:'{$data['query']}'";
        }

        // 查询条件拼接
        $nowTime = time();
        $where = "end_time>$nowTime AND team_type='normal'";
        if (isset($data['cityId']) && trim($data['cityId'])) {
            $cityWhere = "city_id='{$data['cityId']}' OR city_ids='{$data['cityId']}'";
            $where = "$where AND ($cityWhere)";
        }

        // 排序字段
        $sort = array();
        if (isset($data['order']) && trim($data['order'])) {
            $lastId = isset($data['lastId']) && trim() ? $data['lastId'] : 0;
            $lastIdWhere = "";
            if (strpos($data['order'], '@') !== false) {
                @list($sortType, $sortFildel) = explode('@', $data['order'], 2);
                $sort[$sortFildel] = '+';
                trim($lastId) && $lastIdWhere = "$sortFildel>$lastId";
                if (trim($sortType) == '-') {
                    $sort[$sortFildel] = '-';
                    trim($lastId) && $lastIdWhere = "$sortFildel<$lastId";
                }
            } else {
                $sort[$data['order']] = '+';
                trim($lastId) && $lastIdWhere = "{$data['order']}>$lastId";
            }
            trim($lastIdWhere) && $where = "$lastIdWhere AND $where";
        }

        return array($query, $where, $sort);
    }

    /**
     * 获取数据为where 和 sort
     * @param type $data
     * @return type
     */
    public function getMysqlWhere($data) {
        // 查询条件拼接
        $where = array('end_time' => array('LT', time()), 'team_type' => 'normal');
        if (isset($data['city_id']) && trim($data['city_id'])) {
            $where['city_id'] = $data['city_id'];
        }

        // 排序字段
        $sort = array();
        if (isset($data['order']) && $data['order']) {
            $lastId = isset($data['lastId']) && trim() ? $data['lastId'] : 0;
            if (strpos($data['order'], '@') !== false) {
                @list($sortType, $sortFildel) = explode('@', $data['order'], 2);
                $sort[$sortFildel] = 'ASC';
                trim($lastId) != '' && $where[$sortFildel] = array('GT', $lastId);
                if (trim($sortType) == '-') {
                    $sort[$sortFildel] = 'DESC';
                    trim($lastId) != '' && $where[$sortFildel] = array('LT', $lastId);
                }
            } else {
                $sort[$data['order']] = 'ASC';
                trim($lastId) != '' && $where[$data['order']] = array('GT', $lastId);
            }
        }

        return array($where, $sort);
    }

    /**
     * 统一处理团单数据
     * @param type $data
     * @param type $flag
     * @return type
     */
    public function dealTeamData($data = array(), $isOne = false, $isPartner = true) {

        if (!$data) {
            return array();
        }
        if ($isOne) {
            $data = array($data);
        }

        $_data = array();
        $partner = array();
        $partnerDB = M('partner');
        foreach ($data as $k => $v_res) {
            if (!isset($v_res['id'])) {
                continue;
            }
            if (!isset($v_res['partner_id'])) {
                continue;
            }
            if ($isPartner && !isset($partner[$v_res['partner_id']])) {
                $partner[$v_res['partner_id']] = $partnerDB->where(array('id' => $v_res['partner_id']))->find();
            }

            $line = array(
                'id' => isset($v_res['id']) ? $v_res['id'] : '',
                'product' => isset($v_res['product']) ? $v_res['product'] : '',
                'team_price' => isset($v_res['team_price']) ? $v_res['team_price'] : '',
                'image' => isset($v_res['image']) && trim($v_res['image']) ? getImagePath($v_res['image']) : '',
                'market_price' => isset($v_res['market_price']) ? $v_res['market_price'] : '',
                'now_number' => isset($v_res['now_number']) ? $v_res['now_number'] : '',
                'partner_id' => isset($v_res['partner_id']) ? $v_res['partner_id'] : '',
            );
            if ($isOne) {
                $line['detail'] = isset($v_res['detail']) ? $v_res['detail'] : '';
            }
            if ($isPartner) {
                $line['partner'] = array(
                    'id' => isset($partner[$v_res['partner_id']]['id']) ? $partner[$v_res['partner_id']]['id'] : '',
                    'group_id' => isset($partner[$v_res['partner_id']]['group_id']) ? $partner[$v_res['partner_id']]['group_id'] : '',
                    'part_title' => isset($partner[$v_res['partner_id']]['part_title']) ? $partner[$v_res['partner_id']]['part_title'] : '',
                    'images' => isset($partner[$v_res['partner_id']]['images']) && trim($partner[$v_res['partner_id']]['images']) ? getImagePath($partner[$v_res['partner_id']]['images']) : '',
                    'lng' => isset($partner[$v_res['partner_id']]['lng']) ? $partner[$v_res['partner_id']]['lng'] : '',
                    'lat' => isset($partner[$v_res['partner_id']]['lat']) ? $partner[$v_res['partner_id']]['lat'] : '',
                    'username' => isset($partner[$v_res['partner_id']]['username']) ? $partner[$v_res['partner_id']]['username'] : '',
                    'address' => isset($partner[$v_res['partner_id']]['address']) ? $partner[$v_res['partner_id']]['address'] : '',
                );
            }

            $_data[] = $line;
        }

        if ($isOne) {
            $_data = array_pop($_data);
        }

        return $_data;
    }

    /**
     * 根据某个经纬度 和 范围值  获取经纬度范围的四个点
     * @param type $lng 经度
     * @param type $lat 纬度
     * @param type $distance 距离 单位km
     */
    private function __returnSquarePoint($lng, $lat, $distance) {
        $dlng = 2 * asin(sin($distance / (2 * self::EARTH_RADIUS)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = $distance / self::EARTH_RADIUS;
        $dlat = rad2deg($dlat);
        return array(
            'left-top' => array('lat' => $lat + $dlat, 'lng' => $lng - $dlng),
            'right-top' => array('lat' => $lat + $dlat, 'lng' => $lng + $dlng),
            'left-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng - $dlng),
            'right-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng + $dlng)
        );
    }

    /**
     * 根据经纬度 获取 mysql计算经纬度的字段 距离经度保留小数点后4位
     * @param type $lat 纬度
     * @param type $lng 经度
     */
    private function __getMysqlDistanceField($lat, $lng) {
        $pi = pi() / 180;
        $lat = $lat * $pi;
        $lng = $lng * $pi;
        $latFiled = "`lat`*$pi";
        $lngFiled = "`long`*$pi";
        $calcLongitude = "($lngFiled-$lng)/2";
        $calcLatitude = "($latFiled-$lat)/2";

        $stepOne = "POW(SIN($calcLatitude),2)";
        $steptow = cos($lat) . "*COS($latFiled)";
        $stepThree = "POW(SIN($calcLongitude),2)";
        $sqlField = "ROUND(" . self::EARTH_RADIUS . "*2*ASIN(LEAST(1,sqrt($stepOne+$steptow*$stepThree)))*10000)/10000";

        return trim($sqlField);
    }

    /**
     * 获取排序字段
     * @param type $order  排序的字段名
     * @param type $orderType  排序方式 升序+，降序-   默认升序
     */
    private function __getMysqlSortField($order, $orderType = '+') {

        $field = "MIN($order)";
        if (trim($orderType) == '-') {
            $field = "MAX($order)";
        }
        $nowTime = time();
        $sortField = "(SELECT $field FROM team WHERE team.`partner_id`=partner.`id` AND end_time<$nowTime and team_type like '%normal%')";
        return $sortField;
    }

    /**
     * 得到随机len的字符串
     * @param type $len
     * @return type
     */
    private function __getRandId($len = 6) {
        $secret = '';
        for ($i = 0; $i < $len; $i++) {
            $secret .= chr(rand(65, 90));
        }
        return $secret;
    }

}
