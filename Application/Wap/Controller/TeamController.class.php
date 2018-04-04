<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-06-18
 * Time: 09:34
 */

namespace Wap\Controller;

class TeamController extends CommonController {

    protected $checkCity = false;

    /**
     * 团单详情
     */
    public function detail() {
        $this->_checkblank('id');
        $tid = I('get.id', 0, 'intval');
        // 获取团单详情
        $data = $this->__getTeamDetail($tid);
        $this->_recordViewCount($tid);
        $this->_getTitle($data['team']['product']);
        $this->_getWxShareData($tid);
        $this->_setOpenId();
        $this->assign($data);
        $this->display();
    }

    /**
     * 获取用户分享所需数据
     */
    protected function _getWxShareData($team_id){
        //检测用户当前状态
        $user_id = $this->_getUserId();
        $WxShare = new \Common\Org\wxShare();
        $data = $WxShare->getSignPackage();
        $is_user = true;
        $is_focus = true;;
        $auth_url = '';
        if($user_id){
            //检测用户是否关注青团公众账号
            $openid = M('weixin_sy')->where(array('user_id'=>$user_id))->getField('openid');
            if($openid){
                $data['wx_url'] = "http://m.youngt.com/Team/detail/id/{$team_id}/openid/{$openid}";
            }else{
                $is_focus = false;
                $app_id = $WxShare->appId;
                $redirect_uri = urlencode('http://'.$_SERVER['HTTP_HOST'].'/Public/getWxAuth/tid/'.$team_id);
                $auth_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$app_id}&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
            }
        }else{
            $is_user = false;
        }
        $assign = array('is_user'=>$is_user,'is_focus'=>$is_focus,'data'=>$data,'auth_url'=>$auth_url);
        $this->assign($assign);
    }

    /**
     * 更多详情
     */
    public function moreDetail() {
        $this->_checkblank('id');
        $tid = I('get.id', 0, 'intval');
        $team = D('Team');
        // 查询订单详情
        $teamRes = $this->_getRowDataToOTS('team', array('id' => $tid));
        if (!$teamRes || isset($teamRes['error'])) {
            $teamRes = $team->info($tid, 'id,partner_id,end_time,notice,summary,detail');
        }
        if (!$teamRes) {
            redirect(U('Index/index'));
        }
        $teamRes['address'] = M('Partner')->where('id=' . $teamRes['partner_id'])->getField('address');
        $teamRes['remain'] = $teamRes['end_time'] - time();
        $this->assign('team', $teamRes);
        $this->display();
    }

    /**
     * 获取团单详情
     * @param  $teamId
     * @return array
     */
    private function __getTeamDetail($teamId) {
        $team = D('team');
        // 查询订单详情
        $teamRes = $this->_getRowDataToOTS('team', array('id' => $teamId));
        if (!$teamRes || isset($teamRes['error'])) {
            $teamRes = $team->where(array('id' => $teamId))->find();
        }
        cookie(C('SAVE_CITY_KEY'), $teamRes['city_id'], 30 * 86400, '/');

        // 整理团单数据
        /* if (isset($teamRes['detail']) && trim($teamRes['detail'])) {
          $teamRes['detail'] = preg_replace('/src="\/static/', 'src="' . C('IMG_PREFIX'), $teamRes['detail']);
          }
          $city_res = $this->_getCategoryList('city');
          $group_res = $this->_getCategoryList('group');
          if (isset($teamRes['city_id']) && trim($teamRes['city_id'])) {
          $teamRes['city'] = ternary($city_res[$teamRes['city_id']]['name'], '');
          }
          if (isset($teamRes['group_id']) && trim($teamRes['group_id'])) {
          $teamRes['group'] = ternary($group_res[$teamRes['group_id']]['name'], '');
          }
          if (isset($teamRes['sub_id']) && trim($teamRes['sub_id'])) {
          $teamRes['sub'] = ternary($group_res[$teamRes['sub_id']]['name'], '');
          } */
        if (isset($teamRes['image'])) {
            $teamRes['image'] = getImagePath($teamRes['image']);
        }
        if (isset($teamRes['notice']) && trim($teamRes['notice'])) {
            $teamRes['notice'] = str_replace(array('是否提供发票：否', '不提供发票：', '不提供发票'), '消费后评价获得积分', $teamRes['notice']);
        }

        $teamRes['remain'] = $teamRes['end_time'] - time();

        //获取评论信息
        $comment = D('Comment');
        $where = array('team_id' => $teamId, 'is_comment' => 'Y');
        $teamRes['count'] = $comment->where($where)->count();
        $teamRes['num'] = sprintf('%.1f', $comment->where($where)->avg('comment_num'));
        $teamRes['bnum'] = $teamRes['num'] / 5 * 100;

        if (strpos($teamRes['team_price'], '.') !== false) {
            $teamRes['team_price'] = $teamRes['team_price'] > 0 ? rtrim(rtrim($teamRes['team_price'], '0'), '.') : '0';
        }
        if (strpos($teamRes['market_price'], '.') !== false) {
            $teamRes['market_price'] = $teamRes['market_price'] > 0 ? rtrim(rtrim($teamRes['market_price'], '0'), '.') : '0';
        }

//         // 活动期间按照活动价格下单
//        if(isset($teamRes['activities_id']) && trim($teamRes['activities_id'])){
//            $nowTime = time();
//            $activities_where = array(
//                'id' => $teamRes['activities_id'],
//                'type' => 'activities',
//                'begin_time'=>array('lt',$nowTime),
//                'end_time' => array('gt', $nowTime)
//            );
//            $is_exist_activies =D('Admanage')->isExistActivities($activities_where);
//            if($is_exist_activies && $is_exist_activies>0){
//                if(isset($teamRes['lottery_price']) && $teamRes['lottery_price']>0){
//                     $teamRes['team_price'] = $teamRes['lottery_price'];
//                }
//            }
//
//        }
//


        // 查询其他团购信息
        $nowTime = time();
        $where = array(
            'begin_time' => array('lt', $nowTime),
            'end_time' => array('gt', $nowTime),
            'id' => array('neq', $teamId),
            'partner_id' => ternary($teamRes['partner_id'], '')
        );
        $field = 'id,now_number,team_price,market_price,product';
        $otherTeamRes = $team->field($field)->where($where)->limit(10)->select();
        if (!$otherTeamRes) {
            $where = array(
                'begin_time' => array('lt', $nowTime),
                'end_time' => array('gt', $nowTime),
                'id' => array('neq', $teamId),
                'city_id' => ternary($teamRes['city_id'], ''),
                'group_id' => ternary($teamRes['group_id'], ''),
                'sub_id' => ternary($teamRes['sub_id'], ''),
            );
            $otherTeamRes = $team->field($field)->where($where)->limit(10)->select();
        }

        // 获取商户信息
        $partner = M('partner');
        $field = 'id,`long`,lat,title,phone,location,address';
        $partnerRes = $partner->field($field)->where(array('id' => $teamRes['partner_id']))->find();

        $data = array(
            'team' => $teamRes,
            'otherTeam' => $otherTeamRes,
            'partner' => $partnerRes,
            'tid' => $teamId
        );
        return $data;
    }

    /**
     * 团单评论
     */
    public function review() {
        $this->_checkblank('id');
        $id = I('get.id', 0, 'intval');
        $map = array(
            'is_comment' => 'Y',
            'team_id' => $id,
            'comment_display' => 'Y',
            'content' => array('NEQ', '')
        );
        $total = D('Comment')->getTotal($map);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = D('Comment')->getList($map, 'create_time DESC', $limit, 'create_time,content,comment_num,user_id');

        foreach ($list as $row) {
            $userId[] = $row['user_id'];
        }
        if ($userId) {
            $where = array(
                'id' => array('IN', array_unique($userId))
            );
            $userList = M('User')->where($where)->getField('id,username', true);
            foreach ($list as &$row) {
                $row['username'] = ternary($userList[$row['user_id']], '');
            }
            unset($row);
        }

        $this->assign('list', $list);
        $this->assign('isMore', count($list) >= $this->reqnum ? 1 : 0);
        $this->assign('id', $id);
        $this->assign('page', I('get.p', 1, 'intval') + 1);
        $this->display();
    }

    /**
     * 购买
     */
    public function buy() {
        $this->_checkUser();
        $tid = I('get.tid', 0, 'intval');
        $quantity = 1;
        if (!$tid) {
            // 处理个人中心传的订单id情况
            $oid = I('get.oid', 0, 'intval');
            $order = D('Order');
            $orderInfo = $order->isExistOrder($oid, $this->_getUserId());

            if (!$oid || !$orderInfo) {
                redirect(U('Index/index'));
            }
            $tid = $orderInfo['team_id'];
            $mobile = $orderInfo['mobile'];
            if (!trim($mobile)) {
                $mobile = $this->_getUserInfo();
                $mobile = ternary($mobile['mobile'], '');
            }
            $quantity = ternary($orderInfo['quantity'], 1);
        }
        $team = D('Team');
        // 查询订单详情
        $teamRes = $this->_getRowDataToOTS('team', array('id' => $tid));
        if (!$teamRes || isset($teamRes['error'])) {
            $teamRes = $team->info($tid, 'id,team_price,product,team_type,is_optional_model,fare,max_number,activities_id,lottery_price');
        }
        if (!$teamRes) {
            redirect(U('Index/index'));
        }
        if (!trim($mobile)) {
            $mobile = $this->_getUserInfo();
            $mobile = ternary($mobile['mobile'], '');
        }

        $uid = $this->_getUserId();

        // 默认地址          // 获取送货时间
        $default_address = array();
        $delivery_time = array();
        $team_attribute = array();
        if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'goods') {
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
            if (isset($teamRes['is_optional_model']) && trim($teamRes['is_optional_model']) == 'Y') {
                $where = array(
                    'team_id' => $tid,
                );
                $team_attribute = M('team_attribute')->where($where)->field('id,name,now_num,max_num')->select();
                if ($team_attribute) {
                    foreach ($team_attribute as $k => &$v) {
                        $v['surplus_num'] = '0';
                        if (isset($v['max_num']) && intval($v['max_num']) > 0) {
                            $v['surplus_num'] = strval(ternary($v['max_num'], 0) - ternary($v['now_num'], 0));
                            if (intval($v['surplus_num']) <= 0) {
                                $v['surplus_num'] = 0;
                            }
                            continue;
                        }

                        // 如果不限购 且max_num 为0的  则过滤
                        if (isset($v['max_num']) && intval($v['max_num']) <= 0 && isset($teamRes['max_number']) && intval($teamRes['max_number']) > 0) {
                            unset($team_attribute[$k]);
                            continue;
                        }
                    }
                    unset($v);
                    $quantity = 0;
                }
            }
        }
//         // 活动期间按照活动价格下单
//        if(isset($teamRes['activities_id']) && trim($teamRes['activities_id'])){
//            $nowTime = time();
//            $activities_where = array(
//                'id' => $teamRes['activities_id'],
//                'type' => 'activities',
//                'begin_time'=>array('lt',$nowTime),
//                'end_time' => array('gt', $nowTime)
//            );
//            $is_exist_activies =D('Admanage')->isExistActivities($activities_where);
//            if($is_exist_activies && $is_exist_activies>0){
//                if(isset($teamRes['lottery_price']) && $teamRes['lottery_price']>0){
//                     $teamRes['team_price'] = $teamRes['lottery_price'];
//                }
//            }
//
//        }

        $data = array(
            'address_list' => $default_address,
            'delivery_time' => $delivery_time,
            'team_attribute' => $team_attribute,
            'team' => $teamRes,
            'mobile' => $mobile,
            'quantity' => ternary($quantity, 1),
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 生成订单
     */
    public function order() {
        $this->_checkUser();
        $tid = I('post.tid', 0, 'intval'); // 商品id
        $num = I('post.quantity', 1, 'intval'); // 购买商品的数量
        $mobile = I('post.mobile', '', 'trim'); // 电话
        $team_attribute = I('post.team_attribute', array(), ''); // 选择属性
        $address_id = I('post.address_id', '', 'trim'); // 收货地址id
        $dt_time = I('post.dt_time', '', 'trim'); // 送货时间
        $address = I('post.address', array(), ''); // 收货地址

        if (!trim($tid)) {
            $this->ajaxReturn(array('code' => -1, 'error' => base64_encode('购买的商品id不能为空')));
        }
        if (!trim($num) || intval($num) <= 0 || intval($num) > 500) {
            redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode('非法购买数量,数量在0-500之间'))));
        }
        if (!trim($mobile) || !checkMobile($mobile)) {
            redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode('非法手机号码'))));
        }
        $uid = $this->_getUserId();
        if (!$uid) {
            redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode('请登录后再购买'))));
        }
        $team = D('Team');
        $teamRes = $team->info($tid, 'id,team_price,product,team_type,is_optional_model,fare,bonus');
        if(!$teamRes){
             redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode('你购买的团单不存在'))));
        }
        if(isset($teamRes['team_type']) && trim($teamRes['team_type'])=='goods'){
            if (!$address_id || $address_id == 'newaddress') {
                // 添加地址
                $address_id = D('Address')->addUserAddress($uid, $address);
                if (isset($address_id['error']) && trim($address_id['error'])) {
                     redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode($address_id['error']))));
                }
            }
            if(!trim($dt_time)){
                redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode('请选择收货时间'))));
            }
            $optional_model=array();
            if(isset($teamRes['is_optional_model']) && trim($teamRes['is_optional_model'])=='Y'){
                if(!$team_attribute){
                    redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode('请选择商品类型及数量'))));
                }
                $team_attr_list = M('team_attribute')->where(array('team_id'=>$tid))->index('id')->select();
                $attr_num = 0;

                foreach ($team_attribute as $k => $v) {
                    $attr_num = $attr_num + $v;
                    $max_num = ternary($team_attr_list[$k]['max_num'], 0);
                    $now_num = ternary($team_attr_list[$k]['now_num'], 0);
                    if (!$v || intval($v) <= 0) {
                        unset($team_attribute[$k]);
                        continue;
                    }
                    if (intval($max_num)>0 && $now_num + $v > $max_num) {
                        redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode($team_attr_list[$k]['name'] . '库存不足！'))));
                    }
                    $optional_model[]=array('id'=>$k,'name'=>$team_attr_list[$k]['name'],'num'=>$v);

                }
                if(!$optional_model){
                    redirect(U('Team/buy', array('tid' => $tid, 'error' => base64_encode('你选择的商品类型已卖完！'))));
                }
            }
            $optional_model = @json_encode($optional_model);
        }

        $res = $team->teamBuy($uid, $tid, $num, $mobile, 'wap');

        // 结果处理
        if (!$res || isset($res['error'])) {
            redirect(U('Team/buy', array('tid' => $tid, 'num' => $num, 'error' => base64_encode(ternary($res['error'], '')))));
        }
        // 邮购属性更新
        if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'goods') {
            $buy_res = $team->mailTeamBuyUpdateOrder($uid, $res['order_id'], $address_id, $dt_time, $optional_model);

            if (isset($buy_res['error']) && trim($buy_res['error'])) {
                redirect(U('Team/buy', array('tid' => $tid, 'num' => $num, 'error' => base64_encode(ternary($buy_res['error'], '')))));
            }
        }
        $orderId = ternary($res['order_id'], '');

        // 添加分销信息
        if (isset($teamRes['bonus']) && $teamRes['bonus'] > 0) {
            $openid = session('wx_share_openid');
            if ($openid) {
                $res = M('order')->where(array('id'=>$orderId))->setField('openid',$openid);
                if ($res !== false) {
                    session('wx_share_openid',null);
                }
            }
        }
        redirect(U('Team/confirm', array('orderId' => $orderId)));
    }

    /**
     * 订单确认
     */
    public function confirm() {
        $this->_checkUser();
        $orderId = I('get.orderId', '', 'strval');
        $uid = $this->_getUserId();

        if (trim($orderId)) {
            if (!trim($uid)) {
                // 未登录
                redirect(U('Public/login'));
            }
            $order = D('Order');
            $orderRes = $order->isExistOrder($orderId, $uid);
            if (!$orderRes) {
                redirect(U('Index/index'));
            }
            if (isset($orderRes['state']) && trim($orderRes['state']) == 'pay') {
                // 跳到支付成功页面
                redirect(U('Team/payResult', array('type' => $orderRes['service'], 'oid' => $orderRes['id'])));
            }

            // 数据整理
            if (isset($orderRes['condbuy']) && trim($orderRes['condbuy'])) {
                $orderRes['condbuy'] = str_replace('@', ',', $orderRes['condbuy']);
            }

            $team = D('Team');
            $field = 'id,team_price,product,team_type,fare,activities_id,lottery_price';
            $teamRes = $team->field($field)->where(array('id' => $orderRes['team_id']))->find();
            $teamBuyRes = $team->teamBuy($uid, $orderRes['team_id'], $orderRes['quantity'], $orderRes['mobile'], 'wap');
            if (!$teamBuyRes || isset($teamBuyRes['error'])) {
                // 支付失败
                redirect(U('Team/buy', array('tid' => $orderRes['team_id'])));
            }
             // 邮购属性更新
            if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'goods') {
                $buy_res = $team->mailTeamBuyUpdateOrder($uid, $teamBuyRes['order_id'], $orderRes['address_id'], $orderRes['delivery_time'], $orderRes['optional_model']);
                if (isset($buy_res['error']) && trim($buy_res['error'])) {
                    redirect(U('Team/buy', array('tid' => $orderRes['team_id'],'error' => base64_encode(ternary($buy_res['error'], '')))));
                }
            }
            // 活动期间按照活动价格下单
//            if(isset($teamRes['activities_id']) && trim($teamRes['activities_id'])){
//                $nowTime = time();
//                $activities_where = array(
//                    'id' => $teamRes['activities_id'],
//                    'type' => 'activities',
//                    'begin_time'=>array('lt',$nowTime),
//                    'end_time' => array('gt', $nowTime)
//                );
//                $is_exist_activies =D('Admanage')->isExistActivities($activities_where);
//                if($is_exist_activies && $is_exist_activies>0){
//                    if(isset($teamRes['lottery_price']) && $teamRes['lottery_price']>0){
//                         $teamRes['team_price'] = $teamRes['lottery_price'];
//                    }
//                }
//
//            }
            $data = array(
                'credit' => ternary($teamBuyRes['credit'], 0),
                'money' => ternary($teamBuyRes['money'], 0),
                'order' => $orderRes,
                'team' => $teamRes,
            );
            $this->assign($data);
            $this->assign('uid', $this->_getUserId());
            $this->display();
        }
    }

    /**
     * 支付
     */
    public function pay() {
        $payType = I('param.paytype', '', 'strval');
        if ($payType == 'wapalipay' && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'micromessenger') !== false) {
            $this->display('alipay');
            exit();
        }
        $uid = $this->_getUserId();
        if (!$uid) {
            cookie('jumpurl', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            redirect(U('Public/login'));
        }
        $credit = I('param.credit', 0.0, 'doubleval');
        $money = I('param.money', 0.0, 'doubleval');
        $oid = I('param.oid', 0, 'intval');
        $tid = I('param.team_id', '', 'intval');

        $order = D('Order');
        $orderRes = $order->isExistOrder($oid, $uid);
        if($orderRes){
            if($orderRes['money']!=$money){
                redirect(U('Team/confirm', array('orderId' => $oid, 'error' => base64_encode('金额非法！'))));
            }
        }else{
            redirect(U('Team/confirm', array('orderId' => $oid, 'error' => base64_encode('订单非法！'))));
        }
        if (!$orderRes) {
            redirect(U('Index/index'));
        }
        if (isset($orderRes['state']) && trim($orderRes['state']) == 'pay') {
            // 跳到支付成功页面
            redirect(U('Team/payResult', array('type' => $orderRes['service'], 'oid' => $oid)));
        }

        $user = M('user');
        $userRes = $user->where(array('id' => $uid))->find();

        // 非法支付判断
        if (isset($orderRes['origin']) && $orderRes['origin'] < 0) {
            redirect(U('Team/confirm', array('orderId' => $oid, 'error' => base64_encode('非法支付！'))));
        }
        if (trim($payType) == 'credit' && isset($orderRes['origin']) && isset($userRes['money']) && $userRes['money'] < $orderRes['origin']) {
            redirect(U('Team/confirm', array('orderId' => $oid, 'error' => base64_encode('用户余额不足！'))));
        }

        if ($money <= 0) {
            // 余额支付
            $order->where(array('id' => $oid))->save(array('credit' => $credit));
        } else {
            // 更新pay_id
            $randId = strtr(sprintf("%.2f", $money), array('.' => '-', ',' => '-'));
            $order_num = ternary($orderRes['quantity'], 0);
            $pay_id = "go-{$oid}-{$order_num}-{$randId}";
            $orderData = array(
                'service' => $payType,
                'pay_id' => $pay_id
            );
            $res = $order->where(array('id' => $oid, 'user_id' => $uid))->save($orderData);
            if ($res === false) {
                redirect(U('Team/confirm', array('orderId' => $oid, 'error' => base64_encode('支付标识更新失败！'))));
            }
        }

        $team = D('Team');
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();
        $nowTime = time();
        $pay = new \Common\Org\Pay();
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        $option = array(
            'return_url' => $host . U('Team/payResult', array('type' => $payType, 'oid' => $oid)),
            'merchant_url' => $host . U('Team/payResult', array('type' => $payType, 'oid' => $oid)),
            'return_CodeUrl' => $host . U('Team/confirm', array('orderId' => $oid)),
        );
        // 根据支付类型支付
        switch (trim($payType)) {
            case 'credit':
                $res = $team->teamPay($uid, $oid, 'creditpay', 'wap');
                if (!$res || isset($res['error'])) {
                    redirect(U('Team/confirm', array('orderId' => $oid, 'error' => base64_encode('余额支付失败，' . ternary($res['error'], '')))));
                }
                // 跳转到支付成功页面
                redirect(U('Team/payResult', array('type' => 'credit', 'oid' => $oid)));
                break;
            case 'wapalipay':
                $data = array(
                    'out_trade_no' => $pay_id,
                    'subject' => $teamRes['product'],
                    'total_fee' => $money,
                    'body' => $teamRes['title'],
                    'show_url' => U('Index/index'),
                );

                $pay->wapDoPay('wapalipay', $data, $option);
                break;
            case 'waptenpay':
                $data = array(
                    'out_trade_no' => $pay_id,
                    'product_name' => $teamRes['product'],
                    'trade_mode' => 1,
                    'total_fee' => $money * 100,
                    'desc' => "商品：" . $teamRes['product'],
                );
                $pay->wapDoPay('waptenpay', $data, $option);
                break;
            case 'wapumspay':
                $data = array(
                    'out_trade_no' => $pay_id,
                    'product_name' => $teamRes['product'],
                    'trade_mode' => 1,
                    'total_fee' => $money,
                    'desc' => "商品：" . $teamRes['product'],
                );
                $pay->wapDoPay('wapumspay', $data, $option);
                break;

            case 'wapunionpay':
                $pay_id = str_replace('-', 'U', $pay_id);
                $res = $order->where(array('id' => $oid, 'user_id' => $uid))->save(array('pay_id' => $pay_id));
                if ($res === false) {
                    redirect(U('Team/confirm', array('orderId' => $oid, 'error' => base64_encode('支付标识更新失败！'))));
                }
                $host = 'http://' . $_SERVER['HTTP_HOST'];
                $sync_url = $host . U('Team/payResult', array('type' => $payType, 'oid' => $oid));
                $pay->getWapUnionPayData($pay_id, $teamRes['product'], $teamRes['product'], $money, 'wap', $sync_url);
                break;
            case 'wapwechatpay':
                $data = array(
                    'out_trade_no' => $pay_id,
                    'product_name' => $teamRes['product'],
                    'total_fee' => $money * 100,
                    'desc' => "商品：" . $teamRes['product'],
                );
                $return = $pay->wapDoPay('wapwechatpay', $data, $option);
                if ($return) {
                    //绑定微信
                    if (!$userRes['unid'] && isset($return['wx_data']) && isset($return['wx_data']['unionid'])) {
                        D('User')->bindAccount($uid, 'unid', $return['wx_data']);
                    }
                    $this->assign('data', $return['pay_data']);
                    $this->assign('oid', $oid);
                    $this->display('wxpay');
                }
                break;
            default:
                redirect(U('Team/confirm', array('orderId' => $oid, 'error' => base64_encode('未识别支付方式！'))));
                break;
        }
    }

    /**
     * 支付成功跳转地址
     */
    public function payResult() {
        $this->_checkUser();
        $orderId = I('get.oid', 0, 'intval');
        $type = I('get.type', '', 'strval');

        $uid = $this->_getUserId();
        $type = strtolower(trim($type));

        // 检测改订单如果未支付，则去第三方去查询，如支付成功，则修改库。
        D('Team')->appSynchronousPayCallbackHandle($orderId);

        $order = D('Order')->isExistOrder($orderId, $uid);

        if (!$order) {
            redirect(U('Team/buy', array('oid' => $orderId, 'error' => base64_encode('订单不存在！'))));
        }

        // 未支付跳到支付页面
        if (isset($order['state']) && trim($order['state']) == 'unpay') {
            $this->redirect(U('Team/buy', array('oid' => $orderId)));
        }
        $team = D('Team');
        $teamRes = $team->info($order['team_id'], 'id,product,end_time,team_type');
        $coupons = array();
        $order['pay_detail'] = "总共购买{$order['quantity']}份";
        $order['pay_address'] = '';
        if(isset($teamRes['team_type']) && trim($teamRes['team_type'])=='goods'){
            $order_optional_model = json_decode(ternary($order['optional_model'], ''), true);
            if ($order_optional_model) {
                $oom_str = '';
                foreach ($order_optional_model as $oom) {
                    $oom_str .= "{$oom['name']} * {$oom['num']}份<br/>";
                }
                $order['pay_detail'] = "{$order['pay_detail']}<br/>$oom_str";
            }

            if(isset($order['address']) && trim($order['address'])){
                $address = @json_decode($order['address'],true);
                if($address){
                    $order['pay_address'] = "{$address['province']}{$address['area']}{$address['city']}{$address['street']}<br/>{$address['name']} {$address['mobile']}";
                }
            }
        }else{
            $coupons = M('coupon')->where(array('order_id' => $orderId))->select();
        }



        $this->assign('order', $order);
        $this->assign('coupons', $coupons);
        $this->assign('team', $teamRes);
        $this->display();
    }

}
