<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-07
 * Time: 11:03
 */

namespace Home\Controller;

class CartController extends CommonController {

    /**
     * 获取购物车列表
     */
    public function index() {
        $data = $this->_getCartListWithDB();
        $this->assign($data);
        if (IS_AJAX) {
            $this->ajaxReturn($data);
            exit();
        }
        $this->_getWebTitle(array('title' => '购物车'));
        $this->display();
    }

    /**
     * 添加购物车
     */
    public function addCart() {
        $this->_checkblank('team_id');
        $team_id = I('post.team_id', 0, 'intval');
        $num = I('post.num', 1, 'intval');
        $model = D('Cart');
        $res = $model->createCart($this->uid, $team_id, $num, false);
        if ($res && empty($res['error'])) {
            $data = '';
            if ($num) {
                $data = $model->getSelectCart($this->uid);
            }
            ajaxReturnNew($data, '购物车添加成功', 1);
        } else {
            if (empty($res['error'])) {
                $res['error'] = '购物车添加失败';
            }
            ajaxReturnNew('', $res['error'], 0);
        }
    }

    /**
     * 删除购物车
     */
    public function delCart() {
        $this->_checkblank('team_id');
        $team_id = I('get.team_id', 0, 'intval');
        $model = D('Cart');
        $res = $model->delCart($this->uid, $team_id);
        if ($res) {
            $data = $model->getSelectCart($this->uid);
            $data['totalNum'] = $model->getUserTotal($this->uid);
            ajaxReturnNew($data, '购物车删除成功', 1);
        } else {
            $this->_writeDBErrorLog($res, $model, 'home');
            ajaxReturnNew('', '购物车删除失败', 0);
        }
    }

    /**
     * 清空购物车
     */
    public function cleanCart() {
        $model = D('Cart');
        $res = $model->cleanCart($this->uid);
        if (isset($res['error'])) {
            ajaxReturnNew('', '清空购物车失败', 0);
        }
        if ($res) {
            ajaxReturnNew('', '清空购物车成功', 1);
        } else {
            $this->_writeDBErrorLog($res, $model, 'home');
            ajaxReturnNew('', '清空购物车失败', 0);
        }
    }

    /**
     * 选择/取消 购物车团单
     */
    public function selectCart() {
        $this->_checkblank(array('team_id', 'state'));
        $team_id = I('post.team_id');
        $state = I('post.state');
        $model = D('Cart');
        if (!in_array($state, array('Y', 'N'))) {
            ajaxReturnNew('', '参数错误', 0);
            exit();
        }
        $map = array(
            'user_id' => $this->uid,
            'team_id' => array('IN', $team_id)
        );
        $res = $model->where($map)->setField('state', $state);
        if ($res) {
            $data = $model->getSelectCart($this->uid);
            ajaxReturnNew($data, '购物车选择成功', 1);
        } else {
            ajaxReturnNew('', '购物车选择失败', 0);
        }
    }

    /**
     * ajax获取购物车数量
     */
    public function getCartNum() {
        if (IS_AJAX) {
            if ($this->_getUserId()) {
                $cartNums = D('Cart')->getUserTotal($this->uid);
            } else {
                $cartNums = isset($_COOKIE['cart']) ? count(json_decode($_COOKIE['cart'], true)) : 0;
            }
            $cartNums = ternary($cartNums, 0);
            $this->ajaxReturn(array('html' => $cartNums));
        } else {
            redirect(__APP__ . '/');
        }
    }

    /**
     * 数据库读取购物车
     * @return array
     */
    protected function _getCartListWithDB() {
        $model = D('Cart');
        $list = array();
        if ($this->_getUserId()) {
            $list = $model->getCartList($this->uid);
        } else {
            if (!empty($_COOKIE['cart'])) {
                $cookieData = json_decode($_COOKIE['cart'], true);
                $list = $model->getCartListWithCookie($cookieData);
            }
        }

        $total = $num = 0;
        $cookieList = array();
        $team_ids = array();
        foreach ($list as &$row) {
            $row['image'] = getImagePath($row['image']);
            $row['sum'] = sprintf('%.2f', $row['team_price'] * $row['num']);
            if ($row['state'] == 'Y') {
                $total += $row['sum'];
                $num += 1;
            }
            $team_ids[] = $row['team_id'];

            $cookieList[] = array(
                'team_id' => $row['team_id'],
                'num' => $row['num'],
                'price' => $row['team_price'],
                'state' => $row['state']
            );
        }

        setcookie('cart', json_encode($cookieList), strtotime('+7 days'), '/', APP_DOMAIN);
        return array(
            'list' => $list,
            'total' => sprintf('%.2f', $total),
            'team_ids'=>  json_encode($team_ids),
            'cartNum' => $num
        );
    }

    /**
     * 从OTS中读取购物车团单信息
     * @return array
     */
    protected function _getCartListWithOTS() {
        //TODO 从OTS中读取数据,计算相应的信息
        $model = D('Cart');
        if ($this->_getUserId()) {
            $list = $model->getUserCart($this->uid);
        } else {
            if (!empty($_COOKIE['cart'])) {
                $list = json_decode($_COOKIE['cart'], true);
            }
        }
        if (empty($list))
            return array();
        $teamId = $newList = array();
        foreach ($list as $row) {
            $team = $this->_getRowDataToOTS('team', array('id' => $row['team_id']), array('id,image,product,team_price'));
            if ($team === false) {
                $teamId[] = $row['team_id'];
                break;
            }
            if (!empty($team) && is_array($team)) {
                $row = array_merge($row, $team);
                $newList[$row['team_id']] = $row;
            }
        }

        if (count($teamId) > 0) {
            $newList = $model->getCartList($this->uid);
        }
        $total = $num = 0;
        $cookieList = array();
        foreach ($newList as &$row) {
            $row['image'] = getImagePath($row['image']);
            $row['sum'] = sprintf('%.2f', $row['team_price'] * $row['num']);
            if ($row['state'] == 'Y') {
                $total += $row['sum'];
                $num += 1;
            }
            $cookieList[] = array(
                'team_id' => $row['team_id'],
                'num' => $row['num'],
                'price' => $row['team_price'],
                'state' => $row['state']
            );
        }
        setcookie('cart', json_encode($cookieList), strtotime('+7 days'), '/', APP_DOMAIN);
        return array(
            'list' => $newList,
            'total' => $total,
            'cartNum' => $num
        );
    }

    /**
     * 购买
     */
    public function buy() {
        $this->_checkblank('mobile');
        $mobile = I('post.mobile', '', 'strval');
        $mobile = trim($mobile);
        if (empty($mobile)) {
            redirect(U('Cart/index', array('error' => base64_encode('手机号码不能为空！'))));
        }
        if (!checkMobile($mobile)) {
            redirect(U('Cart/index', array('error' => base64_encode('手机号码格式错误，请填写正确手机号'))));
        }
        $uid = $this->_getUserId();
        if (!$uid) {
            redirect(U('Public/login'));
        }

        $isCookie = I('post.iscookie', 0, 'intval');
        $cartData = array();
        if (empty($isCookie)) {
            // 直接读取表单数据
            $cartData = $this->_getBuyDataWithForm();
        } else {
            // 读取cookie数据
            if (empty($_COOKIE['cart'])) {
                redirect(U('Cart/index', array('error' => base64_encode('购物车为空'))));
            }
            $cartData = $this->_getBuyDataWithCookie();
        }
        if ($cartData['total'] == 0) {
            redirect(U('Cart/index', array('error' => base64_encode('请选择需要提交的团单'))));
        } else if ($cartData['total'] > 10) {
            redirect(U('Cart/index', array('error' => base64_encode('最大可提交10个团单'))));
        }
        if ($cartData['error'] != '') {
            redirect(U('Cart/index', array('error' => base64_encode($cartData['error']))));
        }
        $team = D('Team');
        $order = $teamIds = array();
        $data = $cartData['data'];
        foreach ($data as $row) {
            $res = $team->teamBuy($this->uid, $row['team_id'], $row['num'], $mobile, 'pc');
            if (!$res || isset($res['error'])) {
                $product = $team->where(array('id' => $row['team_id']))->getField('product');
                redirect(U('Cart/index', array('error' => base64_encode("下单失败：团单{$product}下单失败，原因：" . ternary($res['error'], '')))));
                break;
            }
            $order[] = $res['order_id'];
            $teamIds[] = $row['team_id'];
        }
        // 生成订单清空cookie和cart表
        setcookie('cart', null, time() - 1, '/');
        D('Cart')->delCart($this->uid, $teamIds);

        redirect(U('Cart/pay', array('id' => implode('_', $order)))); //跳转选择支付页面
    }

    /**
     * 从cookie中获取订单数据
     * @return array
     */
    protected function _getBuyDataWithCookie() {
        $data = json_decode($_COOKIE['cart'], true);
        $total = 0;
        $str = '';
        foreach ($data as $key => $row) {
            if ($row['state'] == 'Y') {
                $total += 1;
                $num = intval($row['num']);
                if (!$num || $num < 1 || $num > 50) {
                    $str .= '非法购买数量,数量在1-500之间';
                    break;
                }
            } else {
                unset($data[$key]);
            }
        }

        return array(
            'data' => $data,
            'total' => $total,
            'error' => $str
        );
    }

    /**
     * 从表单获取订单数据
     * @return array
     */
    protected function _getBuyDataWithForm() {
        $tid = I('post.tid');
        $team_id = I('post.team_id');
        $num = I('post.quantity');

        if (empty($tid)) {
            redirect(U('Cart/index', array('error' => base64_encode('请选择购买团单'))));
        }
        $list = array();
        for ($i = 0; $i < count($team_id); $i++) {
            $list[$team_id[$i]] = $num[$i];
        }

        $data = array();
        $total = 0;
        $str = '';
        foreach ($team_id as $row) {
            if (in_array($row, $tid)) {
                $total += 1;
                $num = intval($row['num']);
                if (!$num || $num < 1 || $num > 50) {
                    $str .= '非法购买数量,数量在1-50之间';
                    break;
                }
                $data[] = array(
                    'team_id' => $row,
                    'num' => $list[$row],
                    'state' => 'Y'
                );
            }
        }

        return array(
            'data' => $data,
            'total' => $total,
            'error' => $str
        );
    }

    /**
     * 选择支付
     */
    public function pay() {
        $this->_checkUser();
        $this->_checkblank('id');
        $oid = I('get.id', '', 'strval');
        $id = explode('_', trim($oid));
        $uid = $this->_getUserId();

        if (!trim($uid)) {
            // 未登录
            redirect(U('Public/login'));
        }
        if (!trim($oid) || !$id) {
            redirect(__APP__ . '/');
        }
        $team = D('Team');
        $field = 'id,state,condbuy,team_id,user_id,origin,quantity,mobile,price';
        $order = D('Order');
        $map = array(
            'id' => array('IN', $id)
        );
        $orderRes = $order->getList($map, 'id DESC', '', $field);
        if (count($orderRes) != count($id)) {
            redirect(__APP__ . '/');
        }

        $teamId = array();
        $id = array();
        $origin = 0;
        $all_origin = 0;
        $order_items = array();
        foreach ($orderRes as &$row) {
            //判断是否支付,如果已支付unset
            if ($row['state'] == 'pay' || ($row['state'] == 'unpay') && $row['rstate'] == 'berefund') {
                unset($row);
                continue;
            }
            // 重新下单生成数据
            if (isset($row['team_id']) && isset($row['quantity']) && isset($row['mobile'])) {
                $teamBuyRes = $team->teamBuy($uid, $row['team_id'], $row['quantity'], $row['mobile'], 'pc');
                if (!$teamBuyRes || isset($teamBuyRes['error'])) {
                    unset($row);
                    continue;
                }
            }
            $all_origin = $all_origin+$row['origin'];
            $order_items[]=array($row['team_id'],$row['price'],$row['quantity']);
            
            $teamId[$row['team_id']] = array(
                'quantity' => $row['quantity'],
                'origin' => $row['origin']
            );
            $origin += $row['origin'];
            $id[] = $row['id'];
            if (isset($row['condbuy']) && trim($row['condbuy'])) {
                $row['condbuy'] = str_replace('@', ',', $row['condbuy']);
            }
        }
        $all_origin = sprintf("%.2f",$all_origin);
        if (!$orderRes || !$id) {
            redirect(U('Cart/payResult', array('type' => 'credit', 'oid' => $oid)));
        }
        $team_map = array(
            'id' => array('IN', array_keys($teamId))
        );

        $field = 'id,product';
        $teamRes = $team->field($field)->where($team_map)->select();
        foreach ($teamRes as &$_row) {
            if (isset($teamId[$_row['id']])) {
                $_row = array_merge($_row, $teamId[$_row['id']]);
            }
        }

        $user = M('user');
        $userRes = $user->field('money')->where(array('id' => $this->uid))->find();
        $credit = $money = 0;
        if ($origin > 0) {
            $money = $origin;
            if (isset($userRes['money']) && $userRes['money'] > 0) {
                $credit = $userRes['money'];
                $money = $origin - $userRes['money'];
                if ($userRes['money'] >= $origin) {
                    $credit = $origin;
                    $money = 0;
                }
            }
        }
        $data = array(
            'credit' => $credit,
            'money' => $money,
            'order' => $orderRes,
            'team' => $teamRes,
            'order_ids' => @implode('_', $id),
            'all_origin' => $all_origin,
            'order_items' => json_encode($order_items),
        );
        $this->_getWebTitle(array('title' => '下单支付'));
        $this->assign($data);
        $this->display();
    }

    /**
     * 支付
     */
    public function doPay() {
        $payType = I('post.paytype', '', 'strval');
        $bankType = I('post.bank_type', '', 'strval');
        $bankTypeValue = I('post.bank_type_value', '', 'strval');
        $credit = I('post.credit', 0.0, 'doubleval');
        $money = I('post.money', 0.0, 'doubleval');
        $oid = I('post.oid', '', 'strval');
        $creditType = I('post.credittype', '', 'strval');
        $uid = $this->_getUserId();
        $orderIds = @explode('_', $oid);

        if (!trim($oid) || !trim($uid) || !$orderIds) {
            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('订单id为空'))));
        }

        $order = M('order');
        $orderIdsCount = count($orderIds);
        $orderCount = $order->where(array('id' => array('in', $orderIds),'state'=>'pay'))->count();
        if($orderCount && $orderCount==$orderIdsCount){
             redirect(U('Cart/payResult', array('oid' => $oid)));
        }
        
        $orderRes = $order->where(array('id' => array('in', $orderIds)))->select();
        if ($orderIdsCount != count($orderRes)) {
            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('团单数量错误！'))));
        }
        $user = M('user');
        $userRes = $user->where(array('id' => $uid))->find();

        $origin = 0;
        $teamIds = array();
        $orderIds = array();
        foreach ($orderRes as $k => $v) {
            if (isset($v['state']) && trim($v['state']) == 'pay') {
                unset($orderRes[$k]);
                continue;
            }
            if (isset($v['id'])) {
                $orderIds[] = $v['id'];
            }
            if (isset($v['origin']) && trim($v['origin'])) {
                $origin+=$v['origin'];
            }
            if (isset($v['team_id']) && trim($v['team_id'])) {
                $teamIds[] = $v['team_id'];
            }
        }
        $oid = implode('_', $orderIds);
        if (trim($payType) == 'freepay' && $origin > 0) {
            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('非法支付！'))));
        }
        if (trim($payType) == 'credit' && isset($userRes['money']) && $userRes['money'] < $origin) {
            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('非法支付！'))));
        }

        // 根据paytype 参数调整
        switch (trim($payType)) {
            case 'thirdparty':
                $payType = 'tenpay';
                if (trim($bankType) == 'alipay' || trim($bankType) == 'alipaycode' || trim($bankType) == 'pcwxcode') {
                    $payType = trim($bankType);
                }

                $money = $origin;
                $credit = 0;
                if ($creditType) {
                    $money = $origin - $userRes['money'];
                    $credit = $userRes['money'];
                }
                break;
            case 'credit':
                $credit = $origin;
                break;
            case 'freepay':
                $credit = 0;
                break;
            default:
                break;
        }

        // 获取teamTitle 和 teamProduct 
        $teamProduct = '';
        $teamTitle = '';
        $team = D('Team');
        if (isset($teamIds[0])) {
            $teamRes = $team->where(array('id' => $teamIds[0]))->field('product,title')->find();
            if (isset($teamRes['product']) && trim($teamRes['product'])) {
                $teamProduct = $teamRes['product'] . '...';
            }
            if (isset($teamRes['title']) && trim($teamRes['title'])) {
                $teamTitle = $teamRes['title'] . '...';
            }
        }

        // 更新pay_id
        $order_ids_max = max($orderIds);
        $randId = strtr(sprintf("%.2f", $money), array('.' => '_', ',' => '_'));
        $order_count = count($orderIds);
        $pay_id = "cart_{$order_ids_max}_{$order_count}_{$randId}";
        $res = $order->where(array('id' => array('in', $orderIds)))->save(array('pay_id' => $pay_id));
        if ($res === false) {
            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('支付标识更新失败！'))));
        }
        $pay = new \Common\Org\Pay();
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        $option = array(
            'return_url' => $host . U('Cart/payResult', array('type' => $payType, 'oid' => $oid)),
            'merchant_url' => $host . U('Cart/payResult', array('type' => $payType, 'oid' => $oid)),
            'return_CodeUrl' => $host . U('Cart/pay', array('id' => $oid)),
        );
        
        // 更新订单中的金额
        $credit_up = $credit;
        foreach ($orderRes as $v) {
            $money_up = $v['origin'];
            $update_data_order = array('money'=>$money_up);
            if ($credit_up >= $money_up) {
                $update_data_order = array('money'=>0,'credit'=>$money_up);
                $credit_up = sprintf("%.2f", $credit_up - $money_up);
                $money_up = 0;
            } else { 
                $money_up = sprintf("%.2f", $money_up - $credit_up);
                $update_data_order = array('money'=>$money_up,'credit'=>$credit_up);
                $credit_up = 0;
            }
            $res = $order->where(array('id'=>$v['id']))->save($update_data_order); 
        }

        // 根据支付类型支付
        switch (trim($payType)) {
            case 'freepay':
                foreach ($orderRes as $v_order) {
                    $this->doFreePay($order, $v_order, $team, $oid);
                }

                // 跳转到支付成功页面
                redirect(U('Cart/payResult', array('type' => 'credit', 'oid' => $oid)));
                break;
            case 'credit':
                foreach ($orderIds as $v) {
                    if (trim($v)) {
                        $res = $team->teamPay($uid, $v, 'creditpay', 'pc');
                        if (!$res || isset($res['error'])) {
                            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode(ternary($res['error'], '')))));
                        }
                    }
                }
                // 跳转到支付成功页面
                redirect(U('Cart/payResult', array('type' => 'credit', 'oid' => $oid)));
                break;
            case 'alipay':
                $data = array(
                    'out_trade_no' => $pay_id,
                    'subject' => $teamProduct,
                    'total_fee' => $money,
                    'body' => $teamTitle,
                    'show_url' => __APP__ . '/',
                );

                $pay->pcDoPay('pcAlipay', $data, $option);
                break;
            case 'tenpay':
                $data = array(
                    'bank_type' => $bankTypeValue,
                    'out_trade_no' => $pay_id,
                    'product_name' => $teamProduct,
                    'trade_mode' => 1,
                    'total_fee' => $money * 100,
                    'desc' => "商品：" . $teamProduct,
                );
                $pay->pcDoPay('pcTenpay', $data, $option);
                break;
            case 'alipaycode':
                $option = array(
                    'return_url' => $host . U('Cart/payResult', array('type' => $payType, 'oid' => $oid, 'code' => 'success')),
                    'merchant_url' => $host . U('Cart/payResult', array('type' => $payType, 'oid' => $oid, 'code' => 'success')),
                    'return_CodeUrl' => $host . U('Cart/payResult', array('type' => $payType, 'oid' => $oid, 'code' => 'fail')),
                );
                $data = array(
                    'out_trade_no' => $pay_id,
                    'subject' => $teamProduct,
                    'total_fee' => $money,
                    'body' => $teamTitle,
                    'show_url' => __APP__ . '/',
                );
                $html = $pay->pcDoPay('pcAlipay', $data, $option, 'code');
                $data = array(
                    'html' => $html,
                    'order_id' => $oid,
                    'money' => $money,
                );
                $this->assign($data);
                $this->display('Team/team_alipay_code');
                exit;
                break;
            case 'pcwxcode':
                $code_url = $pay->getPCWXpayData($pay_id, $teamTitle, $teamProduct, $money * 100, 'pc');
                $data = array(
                    'code_url' => $code_url,
                     'success_url' => U('Cart/payResult', array('oid' => $oid)),
                    'order_id' => $oid,
                    'money' => $money,
                );
                $this->assign($data);
                $this->display('Team/team_pcwx_code');
                exit;
            default:
                break;
        }
    }

    /**
     * 多单支付成功显示页面 450965_450971_450972
     */
    public function payResult() {
        $orderId = I('get.oid', '', 'strval');
        $code = I('get.code', '', 'strval');
        $type = I('get.type', '', 'strval');


        $orderId = @explode('_', $orderId);
        $uid = $this->_getUserId();
        $type = strtolower(trim($type));

        $team = D('Team');
        $res = $team->synchronousPayCallbackHandle($type);

        // 测试支付回调log输出
        if (C('PAY_CALLBACK_LOG')) {
            file_put_contents('/tmp/pay_callback_synchronous.log', var_export(array(
                'pay_time' => date('Y-m-d H:i:s'),
                'payCallbackHandle' => 'pc 同步 支付回调',
                'payAction' => $type,
                'getData' => var_export($_GET, true),
                'postData' => var_export($_POST, true),
                'res' => var_export($res, true),
                            ), true), FILE_APPEND);
        }

        // 支付宝扫码支付跳转处理
        $parent_url = '';
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        if (trim($code) && strtolower(trim($code)) == 'success') {
            $parent_url = $host . U('Cart/payResult', array('type' => $type, 'oid' => $orderId));
        } elseif (trim($code) && strtolower(trim($code)) == 'fail') {
            $parent_url = $host . U('Cart/pay', array('id' => $orderId));
        }
        if (trim($parent_url)) {
            $str = "<script>window.parent.location.href='$parent_url';</script>";
            die($str);
        }

        if (!$orderId) {
            $this->redirect(__APP__ . '/');
        }

        $order = M('order');
        $field = 'user_id,state,team_id,price,quantity,origin';
        $orderRes = $order->field($field)->where(array('id' => array('in', $orderId), 'state' => 'pay'))->select();
        if (count($orderId) != count($orderRes)) {
            $this->redirect(U('Cart/pay', array('id' => implode('_', $orderId))));
        }

        $orderState = '1';
        $teamIds = array();
        $order_items = array();
        $all_origin = 0;
        foreach ($orderRes as $v) {
            if (!$uid || !isset($v['user_id']) || $uid != trim($v['user_id'])) {
                $this->redirect(__APP__ . '/');
            }
            if (!isset($v['state']) || trim($v['state']) != 'pay') {
                $orderState = '0';
            }
            if (isset($v['team_id'])) {
                $teamIds[] = $v['team_id'];
            }
            $all_origin = sprintf("%.2f",$all_origin+$v['origin']);
            $order_items[]=array($v['team_id'],$v['price'],$v['quantity']);
        }

        if (trim($orderState)) {
            $team = M('team');
            $field = 'id,product,expire_time';
            $teamRes = $team->field($field)->where(array('id' => array('in', $teamIds)))->select();
            $teamArr = array();
            foreach ($teamRes as $v) {
                if (isset($v['id'])) {
                    $teamArr[$v['id']] = $v;
                }
            }
            $coupons = M('coupon')->where(array('order_id' => array('in', $orderId)))->select();
            foreach ($coupons as &$_v) {
                if (isset($teamArr[$_v['team_id']])) {
                    $_v['team'] = $teamArr[$_v['team_id']];
                }
            }
            $this->assign('coupons', $coupons);
        }

        $imageUrl = $this->_getQRImageUrl(implode('_', $orderId));
        $data = array(
            'orderState' => $orderState,
            'imgurl' => $imageUrl,
            'orderId' => implode('_', $orderId),
            'order_items' => json_encode($order_items),
            'all_origin' => $all_origin,
        );
        $this->_getWebTitle(array('title' => '支付结果'));
        $this->assign($data);
        $this->display('pay_result');
    }

    /**
     * 无须支付处理
     * @param type $order
     * @param type $orderRes
     * @param type $team
     * @return boolean
     */
    private function doFreePay($order, $orderRes, $team, $oid) {
        $model = M();
        $model->startTrans();
        $nowTime = time();
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();
        $data = array(
            'service' => 'credit',
            'state' => 'pay',
            'rstate' => 'normal',
            'money' => 0,
            'credit' => 0,
        );
        $res = $order->where(array('id' => $orderRes['id']))->save($data);
        if (!$res) {
            $model->rollback();
            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('订单状态更新失败！'))));
        }
        $res = $order->where(array('id' => $orderRes['id']))->save(array('pay_time' => $nowTime));

        // 更新团单已买数量
        $nowNumber = $orderRes['quantity'] + $teamRes['now_number'];
        $this->_updateRowDataToOTS('team', array('id' => $orderRes['team_id']), array('now_number' => $nowNumber));
        // 数据库更新
        $res = $team->where(array('id' => $orderRes['team_id']))->setInc('now_number', $orderRes['quantity']);
        if (!$res) {
            $model->rollback();
            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('团单销售量更新失败！'))));
        }

        if (isset($teamRes['delivery']) && trim($teamRes['delivery']) == 'coupon') {
            $res = $team->addCoupon($orderRes, $teamRes);
            if (!$res) {
                $model->rollback();
                redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('券号生成失败！'))));
            }
        }

        // 添加评论
        $res = $team->addComment($orderRes, $teamRes);
        if (!$res) {
            $model->rollback();
            redirect(U('Cart/pay', array('id' => $oid, 'error' => base64_encode('评论添加失败！'))));
        }
        $model->commit();
        // 购买成功后发送短信
        $team->paySuccessSendSms($orderRes, $teamRes, true);
        return true;
    }

}
