<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-27
 * Time: 10:36
 */

namespace Home\Controller;

class MemberController extends CommonController {

    protected $_ajaxPage = 10;
    protected $userCheck = true;

    public function __construct() {
        parent::__construct();

        $map = array(
            'user_id' => $this->uid,
            'is_comment' => 'N',
            'consume' => 'Y',
        );
        $unreview = D('Comment')->getTotal($map);
        $this->assign('unreview', $unreview);
        $curUrl = '/' . CONTROLLER_NAME . '/' . ACTION_NAME;
        $this->assign('curUrl', $curUrl);
    }

    /**
     * 首页订单列表
     */
    public function index() {
        $model = D('Order');
        $state = I('get.state');
        $state = $state ? strtoupper($state) : '';
        $data = array();
        switch ($state) {
            case 'UNPAY':   //未付款
                $data = $this->_getUnpayOrders();
                break;
            case 'UNUSE':   //未使用
                $data = $this->_getIsUseOrders('N');
                break;
            case 'EXPIRING':    //即将过期
                $data = $this->_getExpiringOrders();
                break;
            case 'EXPIRED':     //已过期
                $data = $this->_getExpiredOrders();
                break;
            case 'USED':    //已使用
                $data = $this->_getIsUseOrders('Y');
                break;
            case 'UNREVIEW':    //未评价
                $this->_getIsReviewOrder('N');
                break;
            case 'REVIEWED':    //已评价
                $this->_getIsReviewOrder('Y');
                break;
            case 'REFUND':  //退款
                $curUrl = '/' . CONTROLLER_NAME . '/' . ACTION_NAME . '/state/' . strtolower($state);
                $this->assign('curUrl', $curUrl);
                $data = $this->_getRefundOrders();
                break;
            default:
                $data = $this->_getAllOrders();
                break;
        }
        $this->_writeDBErrorLog($data, $model, 'admin');
        $teamModel = D('Team');
        $team = $teamModel->getOrderTeam($data);
        //数据组装
        foreach ($data as &$val) {
            $val['product'] = $team[$val['team_id']]['product'];
            $val['image'] = getImagePath($team[$val['team_id']]['image']);
            if (empty($val['expire_time'])) {
                $val['expire_time'] = $team[$val['team_id']]['expire_time'];
            }
            $val['end_time'] = $team[$val['team_id']]['end_time'];
            $val['status'] = ternary($val['status'], $state);

            //对于unuse的订单
            if ($state == '' || $val['status'] == 'unuse') {
                if (isset($val['use_num']) && $val['user_num'] > 0) {
                    $val['info'] = $val['use_num'] . '已使用<br/>' . $val['unuse_num'] . '未使用';
                }
            }
            if ($val['express'] == 'Y') {
                $val['info'] = getUserOrderState($val);
            }
        }

        //dump($data);

        $this->_getOrderTotal();

        $this->_getWebTitle(array('title' => '用户中心'));

        $this->assign('list', $data);
        $this->assign('state', strtolower($state));
        $this->display();
    }

    /**
     * 全部订单列表
     * @return mixed
     */
    protected function _getAllOrders() {
        $where = array(
            'user_id' => $this->uid,
            'is_display' => 'Y',
            'team_type' => array('neq', 'cloud_shopping'),
        );
        $model = D('Order');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = $model->getAllOrders($where, 'id DESC', $limit, 'id,team_id,state,rstate,price,quantity,create_time,allowrefund,origin,express,mail_order_pay_state');
        $this->assign('pages', $page->show());
        return $data;
    }

    /**
     * 未支付订单列表
     * @return mixed
     */
    protected function _getUnpayOrders() {
        $where = array(
            'user_id' => $this->uid,
            'state' => 'unpay',
            'rstate' => 'normal',
            'is_display' => 'Y',
            'team_type' => array('neq', 'cloud_shopping'),
        );
        $model = D('Order');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = $model->getList($where, 'id DESC', $limit, 'id,team_id,state,rstate,price,quantity,create_time,allowrefund,origin');
        foreach ($data as &$row) {
            $row['info'] = '待付款';
        }
        $this->assign('pages', $page->show());
        return $data;
    }

    /**
     * 使用/未使用订单列表
     * @param $state
     * @return mixed
     */
    protected function _getIsUseOrders($state) {
        $model = D('Order');
        $total = $model->getIsUseOrders($this->uid, $state, '', 'id DESC', 0, '', true);
        $data = array();
        if ($total > 0) {
            $page = $this->pages($total, $this->reqnum);
            $limit = $page->firstRow . ',' . $page->listRows;
            $field = 'id,team_id,state,rstate,price,origin,allowrefund,create_time,quantity';
            $map = array('is_display' => 'Y', 'team_type' => array('neq', 'cloud_shopping'),);
            $data = $model->getIsUseOrders($this->uid, $state, $map, 'pay_time DESC', $limit, $field);
            $this->assign('pages', $page->show());
        }
        return $data;
    }

    /**
     * 即将过期订单列表
     * @return mixed
     */
    protected function _getExpiringOrders() {
        $condition = array(
            'user_id' => $this->uid,
            'consume' => 'N',
            'is_display' => 'Y',
            'team_type' => array('neq', 'cloud_shopping'),
            'expire_time' => array('between', array(time(), strtotime('+7 days')))
        );
        $list = M('Coupon')->where($condition)->group('order_id')->order('NULL')->getField('order_id', true);
        $total = count($list);
        if ($total == 0) {
            return array();
        }
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $model = D('Order');
        $field = 'id,team_id,state,rstate,price,origin,allowrefund,create_time,quantity';
        $where = array(
            'id' => array('IN', $list),
            'state' => 'pay'
        );
        $data = $model->getList($where, 'id DESC', $limit);
        foreach ($data as &$row) {
            $row['info'] = '即将过期';
            $row['status'] = 'unuse';
        }
        $this->assign('pages', $page->show());
        return $data;
    }

    /**
     * 已过期订单列表
     * @return mixed
     */
    protected function _getExpiredOrders() {
        $condition = array(
            'user_id' => $this->uid,
            'consume' => 'N',
            'expire_time' => array('LT', time())
        );
        $data = M('Coupon')->field('order_id')->where($condition)->group('order_id')->order('NULL')->select();
        $total = count($data);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;

        $orderId = array();
        foreach ($data as $row) {
            $orderId[] = $row['order_id'];
        }
        $list = array();
        if (!empty($orderId)) {
            $model = D('Order');
            $map = array(
                'id' => array('IN', $orderId),
                'team_type' => array('neq', 'cloud_shopping'),
                'is_display' => 'Y',
            );
            $sort = 'id DESC';
            $field = 'id,team_id,state,rstate,price,origin,allowrefund,create_time,quantity';
            $list = $model->field($field)->where($map)->order($sort)->limit($limit)->select();
            foreach ($list as &$row) {
                $row['info'] = '已过期';
                $row['status'] = 'expired';
            }
            $this->assign('pages', $page->show());
        }
        return $list;
    }

    /**
     * 退款订单列表
     * @return mixed
     */
    protected function _getRefundOrders() {
        $where = array(
            'user_id' => $this->uid,
            'is_display' => 'Y',
            'team_type' => array('neq', 'cloud_shopping'),
            '_string' => "(state='pay' && rstate='askrefund') or (state='unpay' && rstate='berefund')"
        );
        $model = D('Order');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = $model->getList($where, 'retime DESC', $limit, 'id,team_id,state,rstate,price,quantity,create_time,allowrefund,origin');
        $this->assign('pages', $page->show());
        foreach ($data as &$row) {
            if ($row['state'] == 'unpay') {
                $row['info'] = '已退款';
            } else {
                $row['info'] = '退款中';
            }
        }
        return $data;
    }

    /**
     * 未评论/已评论订单列表
     * @param $state
     */
    protected function _getIsReviewOrder($state) {
        $map = array(
            'user_id' => $this->uid,
            'is_comment' => $state,
            'consume' => 'Y',
        );
        $total = D('Comment')->getTotal($map);
        $this->_writeDBErrorLog($total, D('Comment'), 'api');
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;

        $model = D('Order');
        $field = 'o.id,o.create_time,o.team_id,o.state,o.rstate,o.origin,o.price,o.quantity,c.comment_num,c.content,c.partner_content,c.is_pic';
        $sort = 'o.id DESC';
        if ($state == 'Y') {
            $sort = 'c.create_time DESC';
        }
        $where = array(
            'c.user_id' => $this->uid,
            'c.is_comment' => $state,
            'c.consume' => 'Y',
            'o.is_display' => 'Y',
            'o.team_type' => array('neq', 'cloud_shopping'),
        );
        $data = $model->alias('o')->join('comment c on o.id=c.order_id')->field($field)->where($where)->order($sort)->limit($limit)->select();
        $this->_writeDBErrorLog($data, $model, 'api');

        $teamModel = D('Team');
        $team = $teamModel->getOrderTeam($data);
        //数据组装
        foreach ($data as &$val) {
            $val['title'] = $team[$val['team_id']]['product'];
            $val['image'] = getImagePath($team[$val['team_id']]['image']);
            $val['expire_time'] = $team[$val['team_id']]['expire_time'];
            $val['comment_sum'] = $val['comment_num'] * 20 . '%';
            $val['end_time'] = $team[$val['team_id']]['end_time'];
            $val['score'] = ceil($val['origin']) * ($val['is_pic'] == 'Y' ? 2 : 1);
        }
        $this->assign('list', $data);
        if ($state == 'Y') {
            $tpl = 'reviewOrder';
            $this->_getWebTitle(array('title' => '用户中心-已评价订单'));
        } else {
            $tpl = 'unreviewOrder';
            $this->_getWebTitle(array('title' => '用户中心-未评价订单'));
        }
        $curUrl = '/' . CONTROLLER_NAME . '/' . ACTION_NAME . '/state/' . I('get.state');
        $this->assign('curUrl', $curUrl);
        $this->assign('pages', $page->show());
        $this->display($tpl);
        exit();
    }

    /**
     * 获取未使用,未付款,即将过期的订单总数
     */
    protected function _getOrderTotal() {
        $map = array(
            'user_id' => $this->uid,
            'state' => 'unpay',
            'rstate' => 'normal'
        );
        $model = D('Order');
        $unpay = $model->getTotal($map);
        $assign['unpayNum'] = ternary($unpay, 0);

        $where = array(
            'user_id' => $this->uid,
            'consume' => 'N',
        );
        $coupon = M('Coupon')->field('id')->where($where)->group('order_id')->order('NULL')->select();
        $unuse = count($coupon);
        $assign['unuseNum'] = ternary($unuse, 0);

        $condition = array(
            'user_id' => $this->uid,
            'consume' => 'N',
            'expire_time' => array('between', array(time(), strtotime('+7 days')))
        );

        $data = M('Coupon')->field('id')->where($condition)->group('order_id')->select();
        $expire = count($data);
        $assign['expireNum'] = ternary($expire, 0);
        $this->assign($assign);
    }

    /**
     * ajax获取评论晒图
     */
    public function getReviewPic() {
        if (IS_AJAX) {
            $this->_checkblank('id');
            $id = I('get.id', 0, 'intval');
            $map = array(
                'order_id' => $id,
                'type' => 'Y'
            );
            $list = M('CommentPic')->field('id,image')->where($map)->select();
            $str = '';
            foreach ($list as $row) {
                $str .= '
                <div class="img-box">
                    <a rel="img-box-list" href="' . getImagePath($row['image']) . '">
                        <img src="' . getImagePath($row['image']) . '" class="m-xf-box-img" />
                    </a>
                </div>';
            }
            $data['html'] = $str;
            $this->ajaxReturn($data);
        } else {
            $this->error('非法访问');
        }
    }

    /**
     * 订单详情
     */
    public function orderDetail() {
        $this->_checkblank('id');
        $id = I('get.id', 'intval', 0);
        $order = D('Order')->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->error('此订单不存在');
        }
        $team = D('Team')->info($order['team_id'], 'id,product,team_price,delivery,team_type');
        $map = array(
            'order_id' => $id,
            'user_id' => $this->uid
        );
        $coupon = array();
        if (isset($team['team_type']) && $team['team_type'] == 'goods' && isset($order['express']) && $order['express'] == 'Y') {
            $address = @json_decode($order['address'], true);
            if ($address) {
                $order['address'] = $address;
            }
            $optional_model = @json_decode($order['optional_model'], true);
            $order['pay_detail'] = "数量：{$order['quantity']}";
            if ($optional_model) {
                $optional_model_arr = array();
                foreach ($optional_model as $v) {
                    $optional_model_arr[] = $v['name'] . '*' . $v['num'];
                }
                if ($optional_model_arr) {
                    $order['pay_detail'] = implode("&nbsp;", $optional_model_arr);
                }
            }
        } else {
            $coupon = M('Coupon')->field('id,consume,expire_time')->where($map)->select();
            foreach ($coupon as &$row) {
                if ($row['consume'] == 'Y') {
                    $row['info'] = '已使用';
                } else {
                    if ($row['expire_time'] < strtotime(date('Y-m-d'))) {
                        $row['info'] = '已过期';
                    } else {
                        $row['info'] = '未使用';
                    }
                }
            }
        }

        // 获取OTA信息
        $ota = D('Ota');
        if ($ota->tmCheck($order['team_id'])) {
            $order['ota'] = $ota->where(array('order_id'=>$order['id']))->find();
            // print_r($order['ota']);
        }

        $this->_getWebTitle(array('title' => '用户中心-订单详情'));
        $this->assign('content', $order);
        $this->assign('team', $team);
        $this->assign('coupon', $coupon);
        $this->display();
    }

    /**
     * 查看团购券
     */
    public function viewCoupon() {
        $this->_checkblank('id');
        $id = I('get.id', 'intval', 0);
        $order = D('Order')->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->error('此订单不存在');
        }
        $map = array(
            'order_id' => $id,
            'user_id' => $this->uid
        );
        $coupon = M('Coupon')->field('id,consume,expire_time')->where($map)->select();
        foreach ($coupon as &$row) {
            if ($row['consume'] == 'Y') {
                $row['info'] = '已使用';
            } else {
                $row['info'] = '未使用';
                if ($row['expire_time'] < strtotime(date('Y-m-d'))) {
                    $row['info'] = '已过期';
                }
            }
        }
        $this->_getWebTitle(array('title' => '用户中心-券号详情'));
        $this->assign('coupon', $coupon);
        $this->assign('id', $id);
        $this->assign('mobile', $order['mobile']);
        $this->display();
    }

    /**
     * 账号信息
     */
    public function account() {
        if (IS_POST) {
            $city_id = I('post.city_id', 0, 'intval');
            $data['city_id'] = $city_id;
            $user = D('User')->info($this->uid);
            if ($user['manage'] == 'P') {
                $this->error('代理账号无法修改城市');
            }
            if (M('user')->where('id=' . $this->uid)->save($data)) {
                $this->success('修改成功', U('Member/account'));
            } else {
                $this->error('修改失败');
            }
        } else {
            $this->_getWebTitle(array('title' => '用户中心-账号信息'));
            $this->assign('info_type', 'info');
            $this->display();
        }
    }

    /**
     * 地址列表
     */
    public function addressList() {

        $where = array('user_id' => $this->uid);
        $address_list = M('address')->where($where)->select();
        // 整理数据
        if ($address_list) {
            foreach ($address_list as &$v) {
                unset($v['user_id']);
                $v['mobile_hide'] = substr($v['mobile'], 0, 4) . '****' . substr($v['mobile'], -4, 4);
            }
            unset($v);
        }

        $data = array(
            'info_type' => 'address',
            'address_list' => $address_list,
        );

        $this->assign($data);
        $this->display('Member/account');
    }

    /**
     * 添加地址
     */
    public function addAddress() {
        if (IS_POST) {
            $address_data = I('post.address', array(), '');
            $res = D('Address')->addUserAddress($this->uid, $address_data);
            if (isset($res['error']) && trim($res['error'])) {
                $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
            }
            $this->ajaxReturn(array('code' => 0));
        } else {
            $this->_getWebTitle(array('title' => '用户中心-收货地址添加'));
            $this->assign('post_url', 'addAddress');
            $this->assign('opt', '添加');
            $this->display('Member/addEditAddress');
        }
    }

    /**
     * 编辑地址
     */
    public function editAddress() {
        if (IS_POST) {
            $address_data = I('post.address', array(), '');
            $address_id = I('post.address_id', array(), '');
            $res = D('Address')->editUserAddress($this->uid, array_merge($address_data, array('address_id' => $address_id)));
            if (isset($res['error']) && trim($res['error'])) {
                $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
            }
            $this->ajaxReturn(array('code' => 0));
        } else {
            $address_id = I('get.address_id', '', 'trim');
            if (!$address_id) {
                redirect(U('Member/addressList', array('error' => base64_encode('修改的地址id不存在！'))));
            }
            $addres_res = M('address')->where(array('id' => $address_id))->find();
            $this->_getWebTitle(array('title' => '用户中心-收货地址编辑'));
            $this->assign('post_url', 'editAddress');
            $this->assign('address', $addres_res);
            $this->assign('opt', '编辑');
            $this->display('Member/addEditAddress');
        }
    }

    /**
     * 删除地址
     */
    public function deleteAddress() {
        $address_id = I('get.address_id', '', 'trim');

        $res = D('Address')->deleteUserAddress($this->uid, $address_id);
        if (isset($res['error']) && trim($res['error'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 设置为默认地址
     */
    public function setDefaultAddress() {
        $address_id = I('get.address_id', '', 'trim');

        $res = D('Address')->setDefaultAddress($this->uid, $address_id);
        if (isset($res['error']) && trim($res['error'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 提现
     */
    public function withdraw() {
        if (IS_POST) {

            $this->_checkblank(array('money', 'bank', 'account', 'uname'));
            $data = array(
                'bank' => I('post.bank', '', 'strval'),
                'money' => I('post.money', 0, 'floatval'),
                'account' => I('post.account', '', 'strval'),
                'uname' => I('post.uname', '', 'strval'),
            );

            $model = D('User');
            $user = $model->info($this->uid, 'id,money');
            if ($user['money'] < $data['money']) {
                $this->error('提现金额不正确');
            }

            $res = $model->applyCash($this->uid, $data);
            if ($res) {
                $this->success('提现成功', U('Member/withdrawList'));
                exit();
            }

            $this->_writeDBErrorLog($res, $model, 'api');
            $this->error('提现失败');
        } else {
            $this->_getWebTitle(array('title' => '用户中心-提现列表'));
            $this->display();
        }
    }

    /**
     * 获取成长值
     */
    public function getGrowth() {
        $growth = D('Flow')->getUserGrowth($this->uid);
        echo $growth;
    }

    /**
     * 成长值列表
     */
    public function growthList() {
        if (IS_AJAX) {
            $model = D('Flow');
            $where['user_id'] = $this->uid;
            $where['action'] = array('in', 'buy,refund');
            $total = $model->getTotal($where);
            $page = $this->pages($total, $this->_ajaxPage);
            $limit = $page->firstRow . ',' . $page->listRows;
            $data = $model->getUserGrowthList($where, 'id DESC', $limit);

            $this->assign('list', $data);
            $this->assign('pages', $page->show());
            $res['html'] = $this->fetch('growthTpl');
            $this->ajaxReturn($res);
        } else {
            $growth = D('Flow')->getUserGrowth($this->uid);
            $this->_getWebTitle(array('title' => '用户中心-成长值列表'));
            $this->assign('growth', $growth);
            $this->display();
        }
    }

    /**
     * 积分
     */
    public function creditList() {
        if (!IS_AJAX) {
            // 中奖列表
            $map = array(
                'score' => array('GT', 0),
                'action' => 'lottery'
            );
            $list = M('credit')->where($map)->order('create_time DESC')->group('user_id')->limit(20)->select();
            $this->_getWebTitle(array('title' => '用户中心-积分列表'));
            $this->assign('list', $list);
            $this->display();
            exit();
        }
        $map = array(
            'user_id' => $this->uid,
            'create_time' => array('gt', strtotime('2014-08-20')),
        );
        $model = D('Credit');
        $total = $model->getTotal($map);
        $page = $this->pages($total, $this->_ajaxPage);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = $model->getList($map, 'id DESC', $limit, 'id,score,sumscore,create_time,action,detail_id');
        $this->_writeDBErrorLog($data, D('Credit'), 'api');
        $action = array(
            'binding' => '绑定第三方登陆',
            'comment' => '评价',
            'exchange' => '积分换券',
            'lottery' => '抽奖',
            'register' => '注册会员',
            'daysign' => '每日签到',
            'pay' => '购买商品',
            'refund' => '退款',
            'score_goods' => '积分兑换商品'
        );

        $teamId = '';
        foreach ($data as $row) {
            if (($row['action'] == 'comment' || $row['action'] == 'pay' || $row['action'] == 'refund') && $row['detail_id']) {
                $teamId[] = $row['detail_id'];
            }
        }
        if (!empty($teamId)) {
            $team = M('Team')->where('id in(' . implode(',', array_unique($teamId)) . ')')->getField('id,product', TRUE);
            $this->_writeDBErrorLog($team, M('Team'), 'api');
        }
        //组织数据
        foreach ($data as $key => $val) {
            if ($val['score'] > 0) {
                if ($val['action'] == 'comment' || $val['action'] == 'pay' || $val['action'] == 'refund') {
                    $val['detail'] = $action[$val['action']] . '-' . ternary($team[$val['detail_id']], '');
                } else if ($val['action'] == 'lottery') {
                    $val['detail'] = $action[$val['action']] . '获取';
                } else {
                    $val['detail'] = $action[$val['action']];
                }
                $val['score'] = '+' . $val['score'];
            } else {
                $val['detail'] = $action[$val['action']] . '消耗';
            }
            unset($val['action']);
            $data[$key] = $val;
        }

        $this->assign('list', $data);
        $this->assign('pages', $page->show());
        $res['html'] = $this->fetch('creditTpl');
        $this->ajaxReturn($res);
    }

    /**
     * 积分兑换
     */
    public function cardList() {
        $this->_getWebTitle(array('title' => '用户中心-积分兑换'));
        $this->display();
    }

    /**
     * 余额信息
     */
    public function balance() {
        $where = array(
            'user_id' => $this->uid,
            'action' => array('IN', 'daysign,buy,charge,refund,store,withdraw')
        );
        $model = D('Flow');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = $model->getList($where, 'id DESC', $limit, 'id,detail_id,action,direction,money,create_time');
        if ($data === false) {
            //获取失败
            $this->_writeDBErrorLog($data, D('Flow'), 'api');
            $this->outPut('', 1005);
        }
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
                $teamId[] = $row['detail_id'];
            }
        }
        if (!empty($teamId)) {
            $map = array(
                'id' => array('IN', array_unique($teamId))
            );
            $team = M('Team')->where($map)->getField('id,product', true);
            $this->_writeDBErrorLog($team, M('Team'), 'home');
            //数据组织
            foreach ($data as $key => $val) {
                if ($val['action'] == 'buy' || $val['action'] == 'paycharge') {
                    $val['detail'] = $arr[$val['action']] . ternary($team[$val['detail_id']], '');
                    $val['state'] = 'Y';
                } else {
                    $val['detail'] = $arr[$val['action']];
                }
                unset($val['action']);
                $data[$key] = $val;
            }
        }
        $this->_getWebTitle(array('title' => '用户中心-用户余额'));
        $this->assign('list', $data);
        $this->assign('pages', $page->show());
        $this->display();
    }

    /**
     * 提现记录
     */
    public function withdrawList() {
        $where = array(
            'user_id' => $this->uid,
            'action' => 'withdraw',
        );
        $model = D('Flow');
        $total = $model->getTotal($where);
        $this->_writeDBErrorLog($total, $model, 'home');
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $data = $model->getList($where, 'id DESC', $limit, 'id,money,create_time');
        $this->_writeDBErrorLog($data, $model, 'home');

        $this->_getWebTitle(array('title' => '用户中心-提现记录'));
        $this->assign('list', $data);
        $this->assign('pages', $page->show());
        $this->display();
    }

    /**
     * 修改密码
     */
    public function updatePwd() {
        if (IS_POST) {
            $this->_checkblank(array('oldpwd', 'newpwd', 'renewpwd'));
            $model = D('User');
            $oldPwd = trim(I('post.oldpwd'));
            $newPwd = trim(I('post.newpwd'));
            $reNewPwd = trim(I('post.renewpwd'));
            if (!$model->checkPwd($this->uid, $oldPwd)) {
                $this->error('原始密码错误');
            }
            if ($newPwd != $reNewPwd) {
                $this->error('两次密码输入不相等');
            }
            $user = $model->info($this->uid, 'id,password');
            if ($user['password'] === encryptPwd($newPwd)) {
                $this->error('新密码与旧密码不能相同');
            }
            if ($rs = $model->where('id=' . $this->uid)->setField('password', encryptPwd($newPwd))) {
                $this->success('修改成功', U('Member/updatePwd'));
                exit();
            }
            $this->_writeDBErrorLog($rs, $model, 'home');
            $this->error('修改失败');
        } else {
            $this->_getWebTitle(array('title' => '用户中心-修改密码'));
            $this->display();
        }
    }

    /**
     * 绑定qq
     */
    public function bindQQ() {
        $act = I('get.act', '', 'strval');
        if ($act == 'del') {
            $model = D('User');
            $user = $model->info($this->uid, 'id,sns');

            if (empty($user['sns'])) {
                $this->error('您的账号未绑定QQ');
            }
            $res = $model->unBindAccount($this->uid, 'sns');
            if ($res) {
                $this->success('QQ解除绑定成功');
            } else {
                $this->error('QQ解除绑定失败');
            }
            exit();
        }
        $this->_getWebTitle(array('title' => '用户中心-绑定QQ'));
        $this->display();
    }

    /**
     * 绑定微信
     */
    public function bindWeixin() {
        $act = I('get.act', '', 'strval');
        if ($act == 'del') {
            $model = D('User');
            $user = $model->info($this->uid);
            if (empty($user['unid'])) {
                $this->error('您的账号未绑定微信，无法解除绑定');
            }
            $res = $model->unBindAccount($this->uid, 'unid');
            if ($res) {
                $this->success('微信解除绑定成功');
            } else {
                $this->error('微信解除绑定失败');
            }
            exit();
        }
        $this->_getWebTitle(array('title' => '用户中心-绑定微信'));
        $this->display();
    }

    /**
     * 绑定/修改绑定
     */
    public function changeMobile() {
        if (IS_POST) {
            $this->_checkblank(array('mobile', 'captcha', 'act'));
            $mobile = I('post.mobile');
            $captcha = I('post.captcha');
            $act = I('post.act', '', 'strval');
            if (!in_array($act, array('bind', 'change'))) {
                $this->error('非法操作');
            }
            if (!checkMobile($mobile)) {
                $this->error('手机号码格式不正确');
            }
            if ($act == 'bind') {
                $act = 'pcbindmobile';
            } else {
                $act = 'pcchangemobile';
            }
            $model = D('User');

            if (!$model->isActionType($act)) {
                $this->error('非法获取验证码');
            }

            $userRes = $model->isRegister(array('mobile' => trim($mobile)));
            if ($userRes) {
                $this->error('该手机号已经绑定！不能重复绑定！');
            }
            $res = $model->checkMobileVcode($captcha, $mobile, $act);
            if (!$res) {
                $this->error('验证码错误');
            }
            if ($rs = M('User')->where('id=' . $this->uid)->setField('mobile', $mobile)) {
                //修改成功
                $this->success('手机号修改成功');
            } else {
                $this->error('手机号修改失败');
            }
        } else {
            $this->_getWebTitle(array('title' => '用户中心-绑定手机号'));
            $this->display();
        }
    }

    /**
     * 评论处理
     */
    public function review() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $order = D('Order')->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->error('订单不存在');
        }
        $model = D('Comment');
        if (($res = $model->checkIsComment($order)) !== true) {
            cookie('error', base64_encode($res['error']), 24 * 3600, '/');
            redirect(U('Member/index', array('state' => 'reviewed')));
            exit();
        }

        $team = D('Team')->info($order['team_id'], 'id,product,image');
        $team['image'] = getImagePath($team['image']);
        $team['cateone'] = '口味';
        $team['catetwo'] = '服务';
        $team['catethree'] = '环境';
        $this->_getWebTitle(array('title' => '用户中心-发表评论'));
        $this->assign('team', $team);
        $this->assign('id', $id);
        $this->display();
    }

    /**
     * 发布评论
     */
    public function doReview() {
        $this->_checkblank(array('sumscore', 'cateone', 'catetwo', 'catethree', 'id', 'content'));
        $id = I('post.id', 0, 'intval');
        $order = D('Order')->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->error('订单不存在');
        }
        $model = D('Comment');
        if (($res = $model->checkIsComment($order)) !== true) {
            $this->error($res['error']);
        }
        $data['content'] = I('post.content');
        $data['comment_num'] = I('post.sumscore');
        $data['comment_detail'] = serialize(array(
            'cateone' => I('post.cateone', 0, 'intval'),
            'catetwo' => I('post.catetwo', 0, 'intval'),
            'catethree' => I('post.catethree', 0, 'intval')
        ));
        $image = I('post.image', '', 'trim');
        if ($image) {
            $imageArr = explode(',', $image);
            foreach ($imageArr as &$row) {
                if (strpos(trim($row), 'http://') !== 0) {
                    unset($row);
                }
            }
            unset($row);
            if (count($imageArr)) {
                $data['image'] = $imageArr;
            }
        }
        $res = $model->addComment($id, $this->uid, $data);
        if ($res) {
            $load_href = U('Member/index', array('state' => 'unreview'));
            if (isset($order['team_type']) && trim($order['team_type']) == 'cloud_shopping') {
                $load_href = U('Member/cloud_shopping_order');
            }
            $this->success('评论成功并获得' . ceil($order['origin']) . '积分', $load_href);
        } else {
            $this->_writeDBErrorLog($res, $model, 'home');
            $this->error('亲！您的评论提交失败');
        }
    }

    /**
     * 退款
     */
    public function refund() {
        $this->_checkblank('id');
        $model = D('Order');
        $id = I('param.id');
        $order = $model->isExistOrder($id, $this->uid);
        if (empty($order)) {
            if (IS_AJAX) {
                $this->error('订单不存在');
            } else {
                cookie('error', base64_encode('订单不存在'), 24 * 3600, '/');
                redirect(U('Member/index'));
                exit();
            }
        }
        $res = $model->checkIsRefund($order);
        if (isset($res['error'])) {
            if (IS_AJAX) {
                $this->error($res['error']);
            } else {
                cookie('error', base64_encode($res['error']), 24 * 3600, '/');
                redirect(U('Member/index'));
                exit();
            }
        }
        if (IS_POST) {
            // OTA订单释放
            $ota = D('Ota');
            $parkcode = $ota->tmCheck($order['team_id']);
            if ($parkcode && !$ota->orderRefund($order['id'])) {
                $this->error('订单释放失败');
            }

            $reason = I('post.reason');
            $type = I('post.type');
            if (!in_array($type, array(1, 2))) {
                $this->error('参数错误');
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
                $this->success('退款申请成功', U('Member/index'));
            } else {
                $this->_writeDBErrorLog($rs, M('Order'), 'api');
                $this->error('退款申请失败');
            }
        } else {
            $team = D('Team')->info($order['team_id'], 'product,team_price');
            $coupon = M('Coupon')->field('id,consume,expire_time')->where('order_id=' . $id)->select();
            $num = 0;
            foreach ($coupon as &$row) {
                if ($row['consume'] == 'N') {
                    $num += 1;
                }
                if ($row['consume'] == 'N') {
                    if ($row['expire_time'] < strtotime(date('Y-m-d'))) {
                        $row['info'] = '已过期';
                    } else {
                        $row['info'] = '未使用';
                    }
                } else {
                    $row['info'] = '已使用';
                }
            }
            $price = sprintf("%.2f", $num * $order['price']);
            if (isset($order['express']) && trim($order['express']) == 'Y') {
                $price = sprintf("%.2f", $order['origin'] - $order['fare']);
            }
            $this->_getWebTitle(array('title' => '用户中心-申请退款'));
            $this->assign('id', $id);
            $this->assign('team', $team);
            $this->assign('order', $order);
            $this->assign('price', sprintf('%.2f', $price));
            $this->assign('couponList', $coupon);
            $this->display();
        }
    }

    /**
     * 退款中
     */
    public function refunding() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Order');
        $order = $model->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->error('订单不存在');
        }
        if ($order['state'] == 'pay' && $order['rstate'] == 'askrefund') {
            $order['title'] = M('Team')->where('id=' . $order['team_id'])->getField('product');
            $this->assign('order', $order);
            $this->display();
        } else {
            cookie('error', base64_encode('订单状态不符合'), 24 * 3600, '/');
            redirect(U('Member/index'));
            exit();
        }
    }

    /**
     * 取消退款
     */
    public function cancelRefund() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $model = D('Order');
        $order = $model->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->error('订单不存在');
        }
        if ($order['state'] == 'pay' && $order['rstate'] == 'askrefund') {
            $data['rstate'] = 'normal';
            $res = $model->where('id=' . $id)->save($data);
            if ($res) {
                $this->success('退款取消成功', U('Member/index'));
            } else {
                $this->error('退款取消失败');
            }
        } else {
            $this->error('订单状态不符合');
        }
    }

    /**
     * 积分抽奖
     */
    public function lottery() {
        //TODO 考虑优化
        $userinfo = D('User')->info($this->uid);
        if ($userinfo['score'] < 10) {
            $this->error('用户积分不足');
        } else {
            M('user')->where('id=' . $this->uid)->setDec('score', 10);
            $scoredata = array(
                'create_time' => time(),
                'user_id' => $this->uid,
                'score' => -10,
                'action' => 'lottery',
                'sumscore' => $userinfo['score'] - 10
            );
            M('credit')->add($scoredata);
            $prize_arr = array(
                '0' => array('id' => 1, 'prize' => '9999', 'v' => 0),
                '1' => array('id' => 2, 'prize' => '4999', 'v' => 0),
                '2' => array('id' => 3, 'prize' => '499', 'v' => 3),
                '3' => array('id' => 4, 'prize' => '199', 'v' => 5),
                '4' => array('id' => 5, 'prize' => '99', 'v' => 20),
                '5' => array('id' => 6, 'prize' => '49', 'v' => 50),
                '6' => array('id' => 7, 'prize' => '19', 'v' => 100),
                '7' => array('id' => 8, 'prize' => '10', 'v' => 200),
                '8' => array('id' => 9, 'prize' => '5', 'v' => 3000),
                '9' => array('id' => 10, 'prize' => '0', 'v' => 5000)
            );
            foreach ($prize_arr as $key => $val) {
                $arr[$val['id']] = $val['v'];
            }
            $rid = getLottery($arr); //根据概率获取奖项id
            if ($rid != 10) {
                $scoredata = array(
                    'create_time' => time(),
                    'user_id' => $this->uid,
                    'score' => $prize_arr[$rid - 1]['prize'],
                    'action' => 'lottery',
                    'rname' => $userinfo['username'],
                    'sumscore' => $userinfo['score'] - 10 + $prize_arr[$rid - 1]['prize']
                );
                M('user')->where('id=' . $this->uid)->setInc('score', $prize_arr[$rid - 1]['prize']);
                M('credit')->add($scoredata);
            }
            $data['prize'] = $prize_arr[$rid - 1]['prize'];
            $resArr = array(
                1 => 37,
                2 => 34,
                3 => 32,
                4 => 39,
                5 => 38,
                6 => 36,
                7 => 35,
                8 => 33,
                9 => 31,
                10 => 30
            );

            if (isset($resArr[$rid])) {
                $num = $resArr[$rid];
            } else {
                $num = 30;
            }
            $data['msg'] = $num;
            $data['score'] = M('User')->where('id=' . $this->uid)->getField('score');
            $this->ajaxReturn($data);
        }
    }

    /**
     * 发送券号短信
     */
    public function sendSms() {
        $this->_checkblank(array('id', 'mobile'));
        $id = I('post.id', 0, 'intval');
        $mobile = I('post.mobile', '', 'strval');
        if (!checkMobile($mobile)) {
            $this->error('手机号码格式不正确');
        }

        $model = D('Order');
        $order = $model->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->_writeDBErrorLog($order, $model, 'home');
            $this->error('订单不存在');
        }

        if ($order['state'] == 'pay') {
            if ($order['rstate'] == 'askrefund') {
                $this->error('订单已申请退款，无法发送短信');
            }
        } else {
            if ($order['rstate'] == 'normal') {
                $this->error('订单未支付，请支付');
            } else if ($order['rstate'] == 'berefund') {
                $this->error('订单已退款，无法发送短信');
            }
        }

        // 查询券号
        $coupon = M('Coupon')->where('order_id=' . $id)->select();
        if (empty($coupon)) {
            $this->error('此订单无团购券');
        }
        $isUse = false;
        foreach ($coupon as $row) {
            if ($row['consume'] == 'Y' || $row['expire_time'] < strtotime(date('Y-m-d'))) {
                $isUse = true;
            }
        }
        if ($isUse) {
            $this->error('此订单已消费或券号已过期');
        }
        $order['mobile'] = $mobile;
        $teamModel = D('Team');
        $team = $teamModel->info($order['team_id']);
        $res = $teamModel->paySuccessSendSms($order, $team);
        if ($res['status'] == 1) {
            $this->success('短信发送成功');
        } else {
            $this->error('短信发送失败');
        }
    }

    /**
     * 站内信
     */
    public function stationMails() {
        $map = array(
            'user_id' => $this->uid
        );

        $model = D('StationMail');
        $total = $model->getTotal($map);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getList($map, 'id DESC', $limit);
        $this->_getWebTitle(array('title' => '用户中心-站内信'));
        $this->assign('list', $list);
        $this->assign('pages', $page->show());
        $this->display();
    }

    /**
     * 积分商城
     */
    public function scoreGoods() {
        $Model = D('PointsTeam');
        $city = $this->_getCityInfo();
        $where = array(
            'begin_time' => array('lt', time()),
            'end_time' => array('gt', time()),
            'is_display' => 'display',
            'city_id'   =>$city['id']
        );
        $total = $Model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $page->setConfig('theme', "%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
        $limit = $page->firstRow . ',' . $page->listRows;

        $list = $Model->getList($where, 'sorts desc,id DESC', $limit);
        $this->_getWebTitle(array('title' => '用户中心-积分商城'));
        $this->assign('list', $list);
        $this->assign('pages', $page->show());
        $this->display();
    }

    /**
     * 积分详情
     */
    public function scoreDetail() {
        $id = I('get.id', 0, 'intval');
        $Model = D('PointsTeam');
        if ($id) {
            $scoreInfo = $Model->getDetail($id);
            $this->_getWebTitle(array('title' => '用户中心-积分商品详情'));
            $this->assign('scoreInfo', $scoreInfo);
        } else {
            redirect(U('Member/scoreGoods', array('error' => base64_encode('非法请求'))));
        }
        $this->display();
    }

    /**
     * 生成积分兑换码
     */
    public function createScoreCoupon() {
        $id = I('get.id', 0, 'intval');
        $num = I('get.num', 1, 'intval');
        $Model = D('PointsOrder');
        $goods_info = M('points_team')->find($id);
        $user = $this->_getUserInfo();
        if (($goods_info['score'] * $num) > $user['score']) {
            $this->ajaxReturn(getPromptMessage('您的积分不足' . $goods_info['score'] . '分，无法兑换'));
        }
        if ($goods_info['limit_num'] > 0 && intval($num + $goods_info['consume_num']) > intval($goods_info['limit_num'])) {
            $this->ajaxReturn(getPromptMessage('商品数量不足，请关注其他产品'));
        }
        $count = $Model->getTotal(array('team_id' => $id, 'user_id' => $this->uid));
        if ($goods_info['convert_num'] > 0 && intval($count + $num) > intval($goods_info['convert_num'])) {
            $this->ajaxReturn(getPromptMessage('该商品每人限兑' . $goods_info['convert_num'] . '份'));
        }
        $model = M();
        $model->startTrans();
        $n_id = mt_rand(0, 9999999999);
        $code = sprintf("%010d", $n_id);
        $is_order = $Model->getTotal(array('code' => $code));
        while ($is_order) {
            $n_id = mt_rand(0, 9999999999);
            $code = sprintf("%010d", $n_id);
            $is_order = $Model->getTotal(array('code' => $code));
            if ($is_order) {
                continue;
            }
        }
        $addData = array(
            'team_id' => $goods_info['id'],
            'user_id' => $this->uid,
            'city_id' => $goods_info['city_id'],
            'partner_id' => $goods_info['partner_id'],
            'num' => $num,
            'score' => $goods_info['score'],
            'total_score' => $goods_info['score'] * $num,
            'code' => $code,
            'consume' => 'N',
            'add_time' => time(),
            'expire_time' => $goods_info['expire_time'],
        );
        $score_order_id = $Model->add($addData);
        if ($score_order_id) {
            $upData = array('id' => $goods_info['id'], 'consume_num' => $goods_info['consume_num'] + $num);
            $res = M('points_team')->save($upData);
            $flow_res = $this->_addScoreFlow('-' . $addData['total_score'], 'score_goods', $user['score'], $id);
            $user_res = M('user')->save(array('id' => $this->uid, 'score' => $user['score'] - $addData['total_score']));
            if ($res && $flow_res && $user_res) {
                $model->commit();
                $this->ajaxReturn(array('status' => 1, 'code' => $addData['code']));
            } else {
                $model->rollback();
                $this->ajaxReturn(getPromptMessage('兑换失败'));
            }
        } else {
            $model->rollback();
            $this->ajaxReturn(getPromptMessage('兑换失败'));
        }
    }

    /**
     * 积分兑换列表
     */
    public function scoreList() {
        $Model = D('PointsOrder');
        $total = $Model->getTotal(array('user_id' => $this->uid));
        $map = array(
            'po.user_id' => $this->uid
        );
        $page = $this->pages($total, $this->reqnum);
        $page->setConfig('theme', "%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
        $limit = $page->firstRow . ',' . $page->listRows;
        $field = 'po.*,pt.name';
        $list = $Model->getList($map, 'po.id DESC', $limit, $field);
        if ($list) {
            foreach ($list as &$val) {
                if ($val['consume'] == 'Y') {
                    $val['state'] = 'Y';
                } else if ($val['expire_time'] < strtotime(date('Y-m-d'))) {
                    $val['state'] = 'E';
                } else {
                    $val['state'] = 'N';
                }
            }
        }
        $this->_getWebTitle(array('title' => '用户中心-积分兑换列表'));
        $this->assign('list', $list);
        $this->assign('pages', $page->show());
        $this->display();
    }

    /**
     * @param $score
     * @param $action
     * @param $user_score
     * @param int $good_id
     *
     * @return mixed
     */
    public function _addScoreFlow($score, $action, $user_score, $good_id = 0) {
        $data = array(
            'create_time' => time(),
            'detail_id' => $good_id,
            'user_id' => $this->uid,
            'score' => $score,
            'action' => $action,
            'sumscore' => $user_score + $score
        );
        return M('credit')->add($data);
    }

    /**
     * 确认收货
     */
    public function makesureOrder() {
        $this->_checkblank('id');
        $id = I('post.id', 0, 'intval');
        $uid = $this->_getUserId();
        $order = D('Order');
        $res = $order->orderConfirmReceipt($id, $uid);
        if (isset($res['error']) && trim($res['error'])) {
            $this->error($res['error']);
        }
        $this->success('确认收货成功');
    }

    /**
     * 查看物流
     */
    public function viewTransport() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $map = array(
            'user_id' => $this->_getUserId(),
            'id' => $id
        );
        $order_res = M('order')->where($map)->find();

        if (!$order_res) {
            $this->error('订单不存在');
        }

        if (!isset($order_res['mail_order_pay_state']) || intval($order_res['mail_order_pay_state']) < 1) {
            $this->error('该订单未发货，不能查看物流！');
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

        if ($order_res['mail_order_pay_state'] == 1) {
            $r_data['info'] = '已发货';
        } else {
            $r_data['info'] = '已收货';
        }

        $this->assign('data', $r_data);
        $this->display();
    }

    /**
     *  云购单
     */
    public function cloud_shopping_order() {
        $type = I('get.type', 1, 'intval');

        $uid = $this->_getUserId();

        $where = array(
            'order.user_id' => $uid,
            'order.state' => 'pay',
            'order.rstate' => 'normal',
            'order.team_type' => 'cloud_shopping',
        );
        if ($type == 2) {
            $where['_string'] = "csr.status=0 or csr.status is null";
        }
        if ($type == 3) {
            $where['csr.status'] = array('gt', 0);
        }
        
        $order = D('Order');
        $count = $order->where($where)
                        ->join('left join cloud_shoping_result as csr on csr.team_id=order.team_id and order.now_periods_number=csr.periods_number')
                        ->field('order.team_id,order.now_periods_number')
                        ->group('order.team_id,order.now_periods_number')->select();
        $count = $count ? count($count) : 0;
        $page = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $order_list = $order->cloud_shopping_order($where,'order.pay_time desc',$limit);
        $data = array(
            'count' => $count,
            'pages' => $page->show(),
            'list' => $order_list,
            'type' => $type,
        );
        $this->assign($data);
        $this->_getWebTitle(array('title' => '用户中心-云购订单'));
        $this->display();
    }

    /**
     * 领奖手机号码验证
     */
    public function receive_prize_moblie() {

        if (IS_AJAX) {
            $order_id = I('post.order_id', '', 'trim');
            $vcode = I('post.vCode', '', 'trim');

            if (!$order_id) {
                $this->ajaxReturn(array('code' => -1, 'error' => '无法验证要获取的手机号。请联系客服！'));
            }
            if (!$vcode) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请输入验证码！'));
            }

            $order_info = M('order')->where(array('id' => $order_id))->field('id,mobile')->find();

            if (!isset($order_info['mobile']) || !trim($order_info['mobile'])) {
                $this->ajaxReturn(array('code' => -1, 'error' => '手机号码错误，无法验证'));
            }
            $res = D('User')->checkMobileVcode($vcode, $order_info['mobile'], 'receive_prize');
            if (!$res) {
                $this->ajaxReturn(array('code' => -1, 'error' => '验证码错误！'));
            }
            $this->ajaxReturn(array('code' => 0, 'data' => array('load_href' => U('Member/receive_prize_index', array('order_id' => $order_id, 'vcode' => $vcode)))));
        }
        $order_id = I('get.order_id', '', 'trim');
        $order_info = array();
        if ($order_id) {
            $order_info = M('order')->where(array('id' => $order_id))->field('id,mobile')->find();
        }
        $data = array(
            'order' => $order_info
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 领奖发送验证码
     */
    public function receive_prize_send_sms() {
        $order_id = I('post.order_id', '', 'trim');
        if ($order_id) {
            $order_info = M('order')->where(array('id' => $order_id))->field('id,mobile')->find();
        }
        if (!isset($order_info['mobile']) || !trim($order_info['mobile'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '手机号码错误，无法验证'));
        }

        $res = $this->_sendSms($order_info['mobile'], '', 'pc', 'receive_prize');
        if (isset($res['status']) && intval($res['status']) == -1) {
            $this->ajaxReturn(array('code' => -1, 'error' => '验证码发送失败.' . ternary($res['error'], '')));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 领奖页面
     */
    public function receive_prize_index() {
        $uid = $this->_getUserId();

        if (IS_AJAX) {
            $order_id = I('post.order_id', '', 'trim');
            $address_id = I('post.address_id', '', 'trim');
            $d_time = I('post.d_time', '', 'trim');
            $address = I('post.address', array(), '');

            // 非法数据判断
            if (!$order_id) {
                $this->ajaxReturn(array('code' => -1, 'error' => '非法领取奖品！'));
            }
            if (!$address_id) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请选择地址！'));
            }
            if ($address_id == 'newaddress' && !$address) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请填写新地址！'));
            }
            if (!$d_time) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请选择送货时间！'));
            }

            // 点击确认领奖
            $res = D('Order')->confirm_receive_prize($uid, $order_id, $address_id, $d_time, $address);
            if (isset($res['error']) && trim($res['error'])) {
                $this->ajaxReturn(array('code' => -1, 'error' => trim($res['error'])));
            }
            $this->ajaxReturn(array('code' => 0, 'data' => array('load_href' => U('Member/cloud_shopping_order'))));
        }

        $order_id = I('get.order_id', '', 'trim');
        $vcode = I('get.vcode', '', 'trim');
        if (!$order_id) {
            redirect(U('Member/cloud_shopping_order', array('error' => base64_encode('非法参数请求'))));
        }
        if (!$vcode) {
            redirect(U('Member/cloud_shopping_order', array('error' => base64_encode('未通过手机验证，不能直接访问领奖页面'))));
        }

        $order_info = M('order')->where(array('id' => $order_id))->field('id,mobile')->find();

        if (!isset($order_info['mobile']) || !trim($order_info['mobile'])) {
            redirect(U('Member/cloud_shopping_order', array('error' => base64_encode('非法手机号码！'))));
        }
        $res = D('User')->checkMobileVcode($vcode, $order_info['mobile'], 'receive_prize');
        if (!$res) {
            redirect(U('Member/cloud_shopping_order', array('error' => base64_encode('验证码失效，请重新领奖验证手机号！'))));
        }

        $delivery_time = $default_address = array();
        $mail_team_delivery_time = C('MAIL_TEAM_DELIVERY_TIME');
        if ($mail_team_delivery_time) {
            foreach ($mail_team_delivery_time as $k => $v) {
                $delivery_time[$k] = array('id' => $k, 'name' => $v);
            }
        }
        if ($uid) {
            $where = array(
                'user_id' => $uid,
            );
            $default_address = M('address')->where($where)->select();
        }

        $data = array(
            'order_id' => $order_id,
            'address_list' => $default_address ? $default_address : array(),
            'delivery_time' => $delivery_time ? array_values($delivery_time) : array(),
        );
        $this->_getWebTitle(array('title' => '奖品领取'));
        $this->assign($data);
        $this->display();
    }

    /**
     * 一元云购晒单
     */
    public function cloud_shopping_review() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $order = D('Order')->isExistOrder($id, $this->uid);
        if (empty($order)) {
            $this->error('订单不存在');
        }
        $model = D('Comment');
        if (($res = $model->checkIsComment($order)) !== true) {
            cookie('error', base64_encode($res['error']), 24 * 3600, '/');
            redirect(U('Member/index', array('state' => 'reviewed')));
            exit();
        }

        $team = D('Team')->info($order['team_id'], 'id,product,title,image');
        $team['image'] = getImagePath($team['image']);
        $team['cateone'] = '口味';
        $team['catetwo'] = '服务';
        $team['catethree'] = '环境';
        $this->_getWebTitle(array('title' => '用户中心-云购奖品晒单'));
        $this->assign('team', $team);
        $this->assign('order', $order);
        $this->assign('id', $id);
        $this->display();
    }
    
    /**
        *  查看我的云购码
        */
    public function view_cloud_shopping_code() {
        
        $tid = I('get.tid','','trim');
        $pn = I('get.pn','','trim');
        $uid = $this->_getUserId();
        
        $where = array(
            'team_id' => $tid,
            'periods_number' => $pn,
            'user_id' => $uid
        );
        $cloud_shoping_code = M('cloud_shoping_code');
        $pay_code_res = $cloud_shoping_code->where($where)->field('cloud_code,create_time')->select();
      
        // 获取中奖云购码
        $where = array(
            'team_id'=>$tid,
            'periods_number'=>$pn,
            'winning_user_id'=>$uid,
        );
        $cloud_shoping_result = M('cloud_shoping_result')->where($where)->field('winning_cloud_code')->find();
        $winning_cloud_code = '';
        if(isset($cloud_shoping_result['winning_cloud_code']) && trim($cloud_shoping_result['winning_cloud_code'])){
            $winning_cloud_code = trim($cloud_shoping_result['winning_cloud_code']);
        }
        
        $cloud_shoping_code_data = array();
        $cloud_shoping_code_count = count($pay_code_res);
        if ($pay_code_res) {
            $pay_code_data = array();
            foreach ($pay_code_res as &$v) {
                $v['is_winning'] = 0;
                if (trim($winning_cloud_code) && isset($v['cloud_code']) && trim($v['cloud_code']) == trim($winning_cloud_code)) {
                    $v['is_winning'] = 1;
                }
                $key = date('Y-m-d H:i:s', $v['create_time']);
                if (!isset($pay_code_data[$key]) || !$pay_code_data[$key]) {
                    $pay_code_data[$key] = array();
                }
                $pay_code_data[$key][] = $v;
            }
            $cloud_shoping_code_data = $pay_code_data;
            unset($v);
        }
        
        $data = array(
            'cloud_shoping_code_count'=>$cloud_shoping_code_count,
            'cloud_shoping_code_data'=>$cloud_shoping_code_data,
        );
        $this->assign($data);
        $this->display();
    }
}
