<?php

namespace Home\Controller;

/**
 * 团单数据
 * Class TeamController
 * @package Home\Controller
 */
class TeamController extends CommonController {

    private $pc_buy_team_type = array(
        'normal' => '普通团购',
        'goods' => '普通团购',
        'cloud_shopping' => '云购单子',
    );

    public function detail() {
        $tid = I('get.tid', 0, 'intval');
        if (!trim($tid)) {
            $this->redirect(__APP__ . '/');
        }

        // 获取团单详情
        $data = $this->__getTeamDetail($tid);

        $this->_getWebTitle(array('title' => $data['team']['product']));
        $this->assign($data);

        $this->display();
    }

    /**
     *       一元云购详情
     */
    public function could_shoping_detail() {
        $tid = I('get.tid', 0, 'intval'); // 团单id
        $pn = I('get.pn', 0, 'intval'); // 期数

        if (!trim($tid)) {
            $this->redirect(__APP__ . '/');
        }

        $where = array(
            'team_type' => 'cloud_shopping',
            'id' => $tid,
        );
        $field = array(
            'id',
            'product',
            'title',
            'image',
            'now_number',
            'max_number',
            'now_periods_number',
            'detail',
        );
        $teamRes = M('team')->where($where)->field($field)->find();
        if (!$teamRes) {
            $this->redirect(__APP__ . '/');
        }
        $teamRes['status'] = 0;
        $where = array(
            'team_id' => $tid,
            'periods_number' => $pn
        );
        $cloud_shoping_result = M('cloud_shoping_result')->where($where)->find();
        if ($cloud_shoping_result) {
            unset($cloud_shoping_result['id']);
            $teamRes = array_merge($teamRes, $cloud_shoping_result);
        }
        // 处理参数
        $teamRes['pn'] = $pn;
        if (isset($teamRes['image'])) {
            $teamRes['image'] = getImagePath($teamRes['image']);
        }
        if (isset($teamRes['detail']) && trim($teamRes['detail'])) {
            $teamRes['detail'] = preg_replace('/src="\/static/', 'src="' . C('IMG_PREFIX'), $teamRes['detail']);
        }

        // 用户名
        $teamRes['username_hide'] = '';
        $teamRes['pay_count'] = 0;
        $teamRes['cloud_shoping_code_8'] = array();
        $teamRes['cloud_shoping_code_data'] = array();
        if (isset($teamRes['winning_user_id']) && trim($teamRes['winning_user_id'])) {
            $teamRes['username'] = M('user')->where(array('id' => $teamRes['winning_user_id']))->getField('username');
            if (checkMobile($teamRes['username'])) {
                $teamRes['username_hide'] = substr($teamRes['username'], 0, 4) . '****' . substr($teamRes['username'], -4, 4);
            } else {
                $teamRes['username_hide'] = cutStr($teamRes['username'], 1, 0, 0) . '**';
            }

            $where = array(
                'team_id' => $tid,
                'periods_number' => $pn,
                'user_id' => $teamRes['winning_user_id']
            );
            $cloud_shoping_code = M('cloud_shoping_code');
            $pay_code_res = $cloud_shoping_code->where($where)->field('cloud_code,create_time')->select();
            $teamRes['pay_count'] = count($pay_code_res);
            if ($pay_code_res) {
                $pay_code_data = array();
                foreach ($pay_code_res as &$v) {
                    $v['is_winning'] = 0;
                    if (isset($v['cloud_code']) && trim($v['cloud_code']) == trim($teamRes['winning_cloud_code'])) {
                        $v['is_winning'] = 1;
                    }

                    $key = date('Y-m-d H:i:s', $v['create_time']);
                    if (!isset($pay_code_data[$key]) || !$pay_code_data[$key]) {
                        $pay_code_data[$key] = array();
                    }
                    $pay_code_data[$key][] = $v;
                }
                $teamRes['cloud_shoping_code_8'] = array_slice($pay_code_res, 0, 8);
                $teamRes['cloud_shoping_code_data'] = $pay_code_data;
                unset($v);
            }
        }

        // 获取中奖者 城市名称和ip
        if (isset($teamRes['winning_order_id']) && trim($teamRes['winning_order_id'])) {

            $order_res = M('order')->where(array('id' => $teamRes['winning_order_id']))->field('user_buy_ip,user_buy_city_name')->find();
            $teamRes['user_city_name'] = '未知城市';
            if (isset($order_res['user_buy_city_name']) && trim($order_res['user_buy_city_name'])) {
                $teamRes['user_city_name'] = $order_res['user_buy_city_name'];
            }

            $teamRes['user_buy_ip'] = '';
            if (isset($order_res['user_buy_ip']) && trim($order_res['user_buy_ip'])) {
                $teamRes['user_buy_ip'] = $order_res['user_buy_ip'];
            }
        }


        // 获取购买记录
        $teamRes['order_record'] = array();
        $where = array(
            'state' => 'pay',
            'rstate'=>'normal',
            'team_id' => $tid,
            'now_periods_number' => $pn,
            'pay_time' => array('gt', 0),
            
        );
        $field = array('id', 'user_id', 'quantity', 'pay_time', 'user_buy_ip', 'user_buy_city_name','microtime');
        $order_record = M('order')->where($where)->field($field)->order('pay_time desc')->limit(50)->select();
        if ($order_record) {
            $user_ids = array();
            foreach ($order_record as &$v) {
                $user_ids[$v['user_id']] = $v['user_id'];
            }
            unset($v);
            $user_info_res = array();
            if ($user_ids) {
                $user_info_res = M('user')->where(array('id' => array('in', array_keys($user_ids))))->field('id,username')->index('id')->select();
            }
            $order_record_data = array();
            foreach ($order_record as &$v) {
                if (!isset($v['user_buy_city_name']) || !trim($v['user_buy_city_name'])) {
                    $v['user_buy_city_name'] = '未知城市';
                }
                $v['user_username'] = ternary($user_info_res[$v['user_id']]['username'], '');
                $v['user_username_hide'] = '';
                if (checkMobile($v['user_username'])) {
                    $v['user_username_hide'] = substr($v['user_username'], 0, 4) . '****' . substr($v['user_username'], -4, 4);
                } else {
                    $v['user_username_hide'] = cutStr($v['user_username'], 1, 0, 0) . '**';
                }
                
                $v['time'] = date('H:i:s', $v['pay_time']);
                if(isset($v['microtime']) && $v['microtime']>0){
                    $v['time'] = microtime_type($v['microtime'],'H:i:s.');
                }
                $key = date('Y-m-d', $v['pay_time']);
                if (!isset($order_record_data[$key]) || !$order_record_data[$key]) {
                    $order_record_data[$key] = array();
                }
                $order_record_data[$key][] = $v;
            }
            unset($v);
            $teamRes['order_record'] = $order_record_data;
        }
        $this->_getWebTitle(array('title' => $teamRes['title']));

        $data = array(
            'team' => $teamRes
        );

        $this->assign($data);

        $this->display();
    }

    /**
     * 获取某个团单的中奖结果
     */
    public function getCloudShopingWinResult() {
        $tid = I('get.tid', '', 'trim');
        if (!$tid) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团单id不能为空！'));
        }

        $team_res = M('team')->where(array('id' => $tid))->field('product,title')->find();
        $where = array(
            'team_id' => $tid,
            'status' => array('gt', 0),
        );
        $field = array(
            'id',
            'team_id',
            'max_number',
            'periods_number',
            'winning_cloud_code',
            'winning_user_id',
            'begin_time',
        );
        $cloud_shoping_result = M('cloud_shoping_result')->field($field)->where($where)->order('periods_number desc')->select();
        if (!$cloud_shoping_result) {
            $this->ajaxReturn(array('code' => -1, 'error' => '没有往期中奖！'));
        }
        $start = array();
        $cloud_shoping_data = array();
        if ($cloud_shoping_result) {
            $user_ids = $team_ids = array();
            foreach ($cloud_shoping_result as $k => &$v) {
                $v['next'] = $v['prev'] = 0;
                if (isset($cloud_shoping_result[$k - 1]) && $cloud_shoping_result[$k - 1]) {
                    $v['prev'] = ternary($cloud_shoping_result[$k - 1]['id'], 0);
                }
                if (isset($cloud_shoping_result[$k + 1]) && $cloud_shoping_result[$k + 1]) {
                    $v['next'] = ternary($cloud_shoping_result[$k + 1]['id'], 0);
                }
                $user_ids[$v['winning_user_id']] = $v['winning_user_id'];
            }
            unset($v);
            $user_info_res = array();
            if ($user_ids) {
                $user_info_res = M('user')->where(array('id' => array('in', array_keys($user_ids))))->field('id,username')->index('id')->select();
            }
            foreach ($cloud_shoping_result as &$v) {
                $v['winning_user_username'] = ternary($user_info_res[$v['winning_user_id']]['username'], '');
                $v['winning_user_username_hide'] = '';
                if (checkMobile($v['winning_user_username'])) {
                    $v['winning_user_username_hide'] = substr($v['winning_user_username'], 0, 4) . '****' . substr($v['winning_user_username'], -4, 4);
                } else {
                    $v['winning_user_username_hide'] = cutStr($v['winning_user_username'], 1, 0, 0) . '**';
                }
                $v['team_product'] = ternary($team_res[$v['team_id']]['title'], '');
                $v['time'] = date('Y-m-d H:i:s', $v['begin_time']);
                $v['view_href'] = U('Team/could_shoping_detail', array('tid' => $tid, 'pn' => $v['periods_number']));
                if (!$start) {
                    $start = $v;
                }
                $cloud_shoping_data[$v['id']] = $v;
            }
            unset($v);
        }
        if (!$cloud_shoping_data) {
            $this->ajaxReturn(array('code' => -1, 'error' => '没有往期开奖'));
        }
        $this->ajaxReturn(array('code' => 0, 'data' => array('start_data' => $start ? $start : (object) array(), 'list' => $cloud_shoping_data)));
    }

    /**
     * 异步获取团单其他参数
     */
    public function getTeamOtherParam() {
        $tid = I('post.tid', 0, 'intval');
        if (!trim($tid)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '团单id不能为空！'));
        }

        $team = D('Team');
        // 查询订单详情
        $teamRes = $this->_getRowDataToOTS('team', array('id' => $tid));
        if (!$teamRes || isset($teamRes['error'])) {
            $field = 'id,team_price,market_price,lottery_price,activities_id,now_number,max_number,allowrefund,team_type,begin_time,end_time,expire_time,is_optional_model,fare';
            $teamRes = $team->field($field)->where(array('id' => $tid))->find();
        }

        // 活动期间按照活动价格下单
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
        // 关于邮购信息
        if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'goods' && isset($teamRes['is_optional_model']) && $teamRes['is_optional_model'] == 'Y') {
            $where = array(
                'team_id' => $teamRes['id'],
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
            }
            $teamRes['team_attribute'] = $team_attribute;
        }

        // 获取评论相关信息
        $res = M('comment')->where(array('team_id' => $tid, 'is_comment' => 'Y'))->where("comment_num IS NOT NULL")->field(array('COUNT(id)' => 'user_count', 'AVG(comment_num)' => 'avg_num'))->find();

        // 获取该团单是否已经被该用户收藏
        $is_collect = 0;
        $uid = $this->_getUserId();
        if (trim($uid)) {
            $collectRes = M('collect')->where(array('user_id' => $uid, 'team_id' => $tid))->count('id');
            if ($collectRes && intval($collectRes) > 0) {
                $is_collect = 1;
            }
        }

        // 整理团单数据
        $teamRes = array(
            'id' => ternary($teamRes['id'], ''),
            'team_price' => ternary($teamRes['team_price'], '0.00'),
            'market_price' => ternary($teamRes['market_price'], '0.00'),
            'now_number' => ternary($teamRes['now_number'], 0),
            'max_number' => ternary($teamRes['max_number'], 0),
            'allowrefund' => ternary($teamRes['allowrefund'], ''),
            'team_type' => ternary($teamRes['team_type'], ''),
            'begin_time' => ternary($teamRes['begin_time'], ''),
            'expire_time' => ternary($teamRes['expire_time'], ''),
            'fare' => ternary($teamRes['fare'], '0'),
            'is_optional_model' => ternary($teamRes['is_optional_model'], ''),
            'team_attribute' => ternary($teamRes['team_attribute'], array()),
            'end_time' => ternary($teamRes['end_time'], ''),
            'discount' => round(($teamRes['team_price'] / $teamRes['market_price']) * 10, 1),
            'comment_num_avg' => isset($res['avg_num']) && trim($res['avg_num']) ? number_format($res['avg_num'], 1) : '0.0',
            'comment_count' => ternary($res['user_count'], 0),
            'is_collect' => $is_collect,
        );

        // 获取团单状态
        $this->__getTeamState($teamRes);
        $teamRes['begin_time'] = isset($teamRes['begin_time']) ? date('Y-m-d', $teamRes['begin_time']) : '';
        $teamRes['end_time'] = isset($teamRes['end_time']) ? date('Y-m-d', $teamRes['end_time']) : '';
        $teamRes['expire_time'] = isset($teamRes['expire_time']) ? date('Y-m-d', $teamRes['expire_time']) : '';

        // 是否OTA团单
        if (D('Ota')->tmCheck($tid)) {
            $teamRes['team_type'] = 'ota';
        }

        $this->ajaxReturn(array('code' => 0, 'data' => $teamRes));
    }

    /**
     * 异步设置浏览记录和 点击数
     */
    public function setTeamOtherParam() {
        $tid = I('get.tid', 0, 'intval');

        // 获取团单详情
        $data = $this->__getTeamDetail($tid);

        // 记录浏览记录
        $this->__setHistory($data['team']);
        // 记录点击数
        $this->_recordViewCount($tid);

        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 获取团单详情
     * @param type $teamId
     */
    private function __getTeamDetail($teamId) {

        $datakey = 'team_' . $teamId;
        $data = S($datakey);
        if ($data) {
            return $data;
        }

        $team = D('team');
        // 查询订单详情
        $teamRes = $this->_getRowDataToOTS('team', array('id' => $teamId));
        if (!$teamRes || isset($teamRes['error'])) {
            $teamRes = $team->where(array('id' => $teamId))->find();
        }

        // 整理团单数据
        if (isset($teamRes['detail']) && trim($teamRes['detail'])) {
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
        }
        if (isset($teamRes['image'])) {
            $teamRes['image'] = getImagePath($teamRes['image']);
        }
        if (isset($teamRes['notice']) && trim($teamRes['notice'])) {
            $teamRes['notice'] = str_replace(array('是否提供发票：否', '不提供发票：', '不提供发票'), '消费后评价获得积分', $teamRes['notice']);
        }
        $teamRes['muslim'] = 0;
		 $promotion = unserialize($teamRes['promotion']);
		if($promotion && in_array('Q',$promotion)){
			$teamRes['muslim'] = 1;
		}

        // 查询其他团购信息
        $nowTime = time();
        $where = array(
            'begin_time' => array('lt', $nowTime),
            'end_time' => array('gt', $nowTime),
            'id' => array('neq', $teamId),
            'partner_id' => ternary($teamRes['partner_id'], ''),
            'team_type' => 'normal',
        );
        $field = 'id,now_number,team_price,market_price,title';
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

        // 查询感兴趣的团单
        $total_count = 5;
        $where = array(
            'sub_id' => ternary($teamRes['sub_id'], ''),
            'group_id' => ternary($teamRes['group_id'], ''),
            'city_id' => ternary($teamRes['city_id'], ''),
            'end_time' => array('gt', $nowTime),
            'team_type' => 'normal',
            'id' => array('neq', $teamId)
        );
        $field = 'id,now_number,image,team_price,title,market_price,product';
        $__possibleTeamRes = $team->field($field)->where($where)->limit($total_count)->index('id')->select();
        $possibleTeamRes = array();
        array_walk($__possibleTeamRes, function($v, $k) use (&$possibleTeamRes) {
            $key = "team_id_{$k}";
            $possibleTeamRes[$key] = $v;
        });
        $count = count($possibleTeamRes);
        if ($count < $total_count) {
            foreach ($where as $key => $v) {
                unset($where[$key]);
                $__possibleTeamRes = $team->field($field)->where($where)->limit($total_count - $count)->index('id')->select();
                
                if (empty($__possibleTeamRes)) {
                    continue;
                }
                array_walk($__possibleTeamRes, function($v, $k) use (&$possibleTeamRes) {
                    $key = "team_id_{$k}";
                    $possibleTeamRes[$key] = $v;
                });
                
                $count = count($possibleTeamRes);
                if ($count >= $total_count) {
                    break;
                }
            }
        }
        foreach ($possibleTeamRes as &$v) {
            if (isset($v['image']) && trim($v['image'])) {
                $v['image'] = getImagePath($v['image']);
            }
        }

        // 广告表
        $admanageMarket = M('admanage_market');
        $where = array(
            'cat_id' => 16,
            'position_id' => 4,
            'available' => 1,
            'width' => array('LT', 400),
            'start_time' => array('LT', $nowTime),
            'end_time' => array('GT', $nowTime),
        );
        $field = 'link,pic,title';
        $admanageMarketRes = $admanageMarket->where($where)->field($field)->order('id DESC')->limit(4)->select();

        // 获取商户信息
        $partner = M('partner');
        $field = 'id,`long`,lat,title,phone,location,address,zone_id,station_id,city_id';
        /*
        $partnerRes = $partner->field($field)->where(array('id' => $teamRes['partner_id']))->find();
        $district_res = $station_res = array();
        if (isset($partnerRes['city_id']) && trim($partnerRes['city_id'])) {
            $district_res = $this->_getCategoryList('district', array('fid' => $partnerRes['city_id']));
        }
        if (isset($partnerRes['zone_id']) && trim($partnerRes['zone_id'])) {
            $partnerRes['zone'] = ternary($district_res[$partnerRes['zone_id']]['name'], '');
            $station_res = $this->_getCategoryList('station', array('fid' => $partnerRes['zone_id']));
        }
        if (isset($partnerRes['station_id']) && trim($partnerRes['station_id'])) {
            $partnerRes['station'] = ternary($station_res[$partnerRes['station_id']]['name'], '');
        }

        */
        //2016.4.23'id='.$teamRes['partner_id'].' OR fid='.$teamRes['partner_id'].''
        $partnerRes = $partner->field($field)->where('id='.$teamRes['partner_id'].' OR fid='.$teamRes['partner_id'].'')->find();
        $district_res = $station_res = array();
        foreach ($partnerRes as $v) {
            if (isset($partnerRes['city_id']) && trim($partnerRes['city_id'])) {
                $district_res = $this->_getCategoryList('district', array('fid' => $partnerRes['city_id']));
            }
            if (isset($partnerRes['zone_id']) && trim($partnerRes['zone_id'])) {
                $partnerRes['zone'] = ternary($district_res[$partnerRes['zone_id']]['name'], '');
                $station_res = $this->_getCategoryList('station', array('fid' => $partnerRes['zone_id']));
            }
            if (isset($partnerRes['station_id']) && trim($partnerRes['station_id'])) {
                $v['station'] = ternary($station_res[$partnerRes['station_id']]['name'], '');
            }
        }

        $data = array(
            'team' => $teamRes,
            'other' => $otherTeamRes,
            'possible' => $possibleTeamRes,
            'side' => $admanageMarketRes,
            'partner' => $partnerRes,
            'city' => $teamRes['city'],
            'tid' => $teamId
        );
        // 更新缓存
        S($datakey, $data, 3600);
        return $data;
    }

    /**
     * 获取团单当前状态
     */
    private function __getTeamState(&$teamRes) {
        $nowTime = time();
        if (!isset($teamRes['team_type']) || !trim($teamRes['team_type'])) {
            return false;
        }
        $teamType = strtolower(trim($teamRes['team_type']));

        $teamRes['now_state'] = 1;
        if ($teamType != 'timelimit' && isset($teamRes['begin_time']) && $teamRes['begin_time'] > $nowTime) {
            $teamRes['now_state'] = 3;
            return false;
        }
        if ($teamType != 'timelimit' && isset($teamRes['end_time']) && $teamRes['end_time'] < $nowTime) {
            $teamRes['now_state'] = 4;
            return false;
        }

        switch ($teamType) {
            case 'normal':
                if (isset($teamRes['max_number']) && trim($teamRes['max_number']) && $teamRes['now_number'] >= $teamRes['max_number']) {
                    $teamRes['now_state'] = 5;
                    return false;
                } else {
                    $teamRes['remain'] = $teamRes['end_time'] - $nowTime;
                    $teamRes['now_state'] = 1;
                    $teamRes['num'] = $teamRes['max_number'] - $teamRes['now_number'];
                    //2015.4.8 superegoliu  加
                    if ($teamRes['num'] < 0) {
                        $teamRes['num'] = 'l';
                    }
                }
                break;
            case 'limited':
                $teamRes['remain'] = $teamRes['end_time'] - $nowTime;
                $teamRes['now_state'] = 1;
                $teamRes['num'] = $teamRes['max_number'] - $teamRes['now_number'];
                //2015.4.8 superegoliu  加
                if ($teamRes['num'] < 0) {
                    $teamRes['num'] = 'l';
                }

                break;
            case 'timelimit':
                if (isset($teamRes['flv']) && strtolower(trim($teamRes['flv'])) == 'y') {
                    $teamRes['begin_time'] = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $teamRes['begin_time']));
                    $teamRes['end_time'] = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $teamRes['end_time']));
                }
                if (isset($teamRes['begin_time']) && $teamRes['begin_time'] > $nowTime) {
                    $teamRes['now_state'] = 3;
                    return false;
                }
                if (isset($teamRes['end_time']) && $teamRes['end_time'] < $nowTime) {
                    $teamRes['now_state'] = 4;
                    return false;
                }
                $teamRes['remain'] = $teamRes['end_time'] - $nowTime;
                $teamRes['now_state'] = 1;
                $teamRes['num'] = $teamRes['max_number'] - $teamRes['now_number'];
                //2015.4.8 superegoliu  加
                if ($teamRes['num'] < 0) {
                    $teamRes['num'] = 'l';
                }

                break;
            case 'unaudited':
                $teamRes['now_state'] = 2;
                break;
        }
        return true;
    }

    /**
     * 设置历史浏览
     * @param type $teamRes
     */
    private function __setHistory($teamRes) {
        $data = array(
            'id' => ternary($teamRes['id'], ''),
            'image' => ternary($teamRes['image'], ''),
            'product' => ternary($teamRes['product'], ''),
            'team_price' => ternary($teamRes['team_price'], ''),
            'market_price' => ternary($teamRes['market_price'], '')
        );
        $history = cookie('history');
        if (get_magic_quotes_gpc()) {
            $history = stripslashes($history);
        }
        $_data = array();
        $history = unserialize($history);
        if ($history) {
            $_data = $history;
            foreach ($_data as $key => $val) {
                if ($val['id'] == $teamRes['id']) {
                    unset($_data[$key]);
                }
            }
        }
        array_unshift($_data, $data);
        while (count($_data) > 4) {
            array_pop($_data);
        }
        cookie('history', serialize($_data), 3600 * 24);
    }

    /**
     * 点击收藏
     */
    public function addCollect() {

        $tid = I('get.tid', 0, 'intval');
        $uid = $this->_getUserId();
        if (!trim($tid)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '收藏的团单id不能为空！'));
        }
        if (!trim($uid)) {
            $this->ajaxReturn(array('code' => 1, 'error' => '用户未登录！'));
        }

        $teamRes = $this->_getRowDataToOTS('team', array('id' => $tid));
        if (!$teamRes || isset($teamRes['error'])) {
            $teamRes = D('Team')->where(array('id' => $tid))->count();
        }
        if (!$teamRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你收藏的团单不存在！'));
        }

        $collect = M('collect');
        $collectRes = $collect->where(array('user_id' => $uid, 'team_id' => $tid))->count('id');
        if (!$collectRes || intval($collectRes) <= 0) {
            $data = array(
                'user_id' => $uid,
                'team_id' => $tid,
                'create_time' => time(),
            );
            $res = $collect->add($data);
            if (!$res) {
                $this->ajaxReturn(array('code' => -1, 'error' => '收藏团单失败！'));
            }
        }
        $this->ajaxReturn(array('code' => 0, 'data' => array('is_collect' => 1, 'id' => $tid)));
    }

    /**
     * 取消收藏
     */
    public function delCollect() {
        $tid = I('get.tid', 0, 'intval');
        $uid = $this->_getUserId();
        if (!trim($tid)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '取消收藏的团单id不能为空！'));
        }
        if (!trim($uid)) {
            $this->ajaxReturn(array('code' => 1, 'error' => '用户未登录！'));
        }

        $teamRes = $this->_getRowDataToOTS('team', array('id' => $tid));
        if (!$teamRes || isset($teamRes['error'])) {
            $teamRes = D('Team')->where(array('id' => $tid))->count();
        }
        if (!$teamRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '取消收藏的团单不存在！'));
        }

        $collect = M('collect');
        $where = array('user_id' => $uid, 'team_id' => $tid);
        $collectRes = $collect->where($where)->count('id');
        if ($collectRes && intval($collectRes) > 0) {
            $res = $collect->where($where)->delete();
            if (!$res) {
                $this->ajaxReturn(array('code' => -1, 'error' => '取消收藏团单失败！'));
            }
        }
        $this->ajaxReturn(array('code' => 0, 'data' => array('is_collect' => 0, 'id' => $tid)));
    }

    /**
     * 异步获取评论
     */
    public function getComments() {

        $teamId = I('get.tid');
        $show_page = I('get.show_page', 'detail_comment_list', 'trim');
        if (!$show_page || !in_array($show_page,array('detail_comment_list','could_shoping_detail_comment_list'))) {
            $show_page = 'detail_comment_list';
        }
        $show_page = strtr($show_page, array('/' => ''));
        $comment = D('Comment');
        $where = array('comment.team_id' => $teamId, 'comment.is_comment' => 'Y');
        $count = $comment->where($where)->count();
        $page = $this->pages($count, 6);
        $page->setConfig('theme', "%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
        $commentRes = $comment->getCommentList($where, '', $page->firstRow . ',' . $page->listRows);
        $commentRes['page'] = $page->show();
        $this->assign('comments', $commentRes);
        $content = $this->fetch($show_page);
        die($content);
    }

    /**
     * 查看地图
     */
    public function map() {
        $partner_id = I('get.pid');
        $act = I('get.act', '', 'strval');
        $field = 'long,lat,title,address,phone,city_id';
        $partner = M('Partner')->where(array('id' => $partner_id))->field($field)->find();
        $this->assign('partner', $partner);
        if (strtolower(trim($act)) == 'goto') {
            $city_res = $this->_getCategoryList('city');
            $this->assign('city', ternary($city_res[$partner['city_id']], array()));
            $this->display('detail_goto');
            exit;
        }
        $this->display('detail_map');
    }

    /**
     * 团单购买
     */
    public function buy() {
        $teamId = I('get.id', 0, 'intval');
        $num = I('get.num', 1, 'intval');
        $team_attr_id = I('get.team_attr_id', '', 'trim');
        $pc_buy_other_team_config = C('PC_BUY_OTHER_TEAM');
        $team = M('team');
        $field = 'id,team_price,product,title,farefree,condbuy,team_type,is_optional_model,fare,activities_id,lottery_price,now_periods_number';
        $teamRes = $team->field($field)->where(array('id' => $teamId))->find();
        if (!trim($teamId) || !$teamRes) {
            $this->assign('error', '你购买的团单不存在！');
        }
        if (!isset($pc_buy_other_team_config[$teamId]) && !isset($this->pc_buy_team_type[$teamRes['team_type']])) {
            $this->assign('error', '该团单不支持电脑购买，请在APP上购买');
        }
        $team_attr = array();
        if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'goods' && isset($teamRes['is_optional_model']) && $teamRes['is_optional_model'] == 'Y') {
            if (!$team_attr_id) {
                $this->assign('error', '请返回商品详情页选择购买类型');
            } else {
                $where = array('id' => $team_attr_id, 'team_id' => $teamId);
                $team_attr = M('team_attribute')->where($where)->find();
                if (!$team_attr) {
                    $this->assign('error', '你所选的商品类型不存在');
                } else {
                    $max_num = intval($team_attr['max_num']);
                    $now_num = intval($team_attr['now_num']);
                    if ($max_num > 0 && $now_num + $num > $max_num) {
                        $s_num = $max_num - $now_num;
                        if ($s_num > 0) {
                            $num = $s_num;
                        }
                        $this->assign('error', "{$team_attr['name']}类型库存不足，你最多还可以购买{$s_num}件");
                    }
                }
            }
        } else if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'cloud_shopping') {
            $teamRes['team_price'] = 1.00;
        }

//        // 活动期间按照活动价格下单
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

        if (isset($teamRes['condbuy']) && trim($teamRes['condbuy'])) {
            $teamRes['condbuy'] = @explode('@', $teamRes['condbuy']);
            foreach ($teamRes['condbuy'] as &$v) {
                if (preg_match_all('/({|｛)(.+)(｝|})/U', $v, $m)) {
                    $v = $m[2];
                    continue;
                }
                $v = array();
            }
        }

        $uid = $this->_getUserId();



        // 默认地址          // 获取送货时间
        $default_address = array();
        $delivery_time = array();
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
        }


        $data = array(
            'order_num' => $num,
            'team_attr' => $team_attr,
            'team' => $teamRes,
            'address_list' => $default_address ? $default_address : array(),
            'delivery_time' => $delivery_time ? array_values($delivery_time) : array(),
        );
        $this->_getWebTitle(array('title' => '商品购买'));

        $data['use_date'] = I('post.use_date','');

        $this->assign($data);

        // 是否旅游门票
        if (D('Ota')->tmCheck($teamId)) {
            $this->display('ota_team_buy');
        } else {
            $this->display('team_buy');
        }
        
    }

    /**
     * 检测该手机号是否已经绑定
     */
    public function checkMobile() {
        // 参数接收
        $mobile = I('post.mobile', '');
        $user = D('User');
        if (!trim($mobile) || !checkMobile($mobile)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '非法手机号码'));
        }

        $userRes = $user->isRegister(array('mobile' => trim($mobile)));
        if ($userRes) {
            $this->ajaxReturn(array('code' => 1, 'error' => '手机号码已经绑定其他账号，继续操作将解除原先绑定'));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 获取手机验证码
     */
    public function mobileVerify() {
        // 参数接收
        $mobile = I('post.mobile', '');
        $action = I('post.action', '');

        // 非法参数判断
        $user = D('User');
        $action = strtolower($action);
        if (!$user->isActionType($action)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '非法获取验证码'));
        }
        if (!trim($mobile) || !checkMobile($mobile)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '非法手机号码'));
        }

        // 验证是否正常获取
        $sms = M('sms');
        $where = array('mobile' => trim($mobile), 'date' => date('Y-m-d'), 'action' => $action);
        $vCodeRes = $sms->where($where)->find();
        $sms_day_count = C('SMS_DAY_COUNT');
        $sms_time_out = C('SMS_TIME_OUT');
        $sms_minute_time_out = C('SMS_MINUTE_TIME_OUT');
        $sms_hours_time_out = C('SMS_HOURS_TIME_OUT');
        $sms_hours_count = C('SMS_HOURS_COUNT');

        if (isset($vCodeRes['num']) && intval($vCodeRes['num']) >= $sms_day_count) {
            $this->ajaxReturn(array('code' => -1, 'error' => '获取验证码次数过多'));
        }

        if (isset($vCodeRes['create_time']) && intval(time() - $vCodeRes['create_time']) < $sms_minute_time_out) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请一分钟之后再获取验证码'));
        }

        $client_ip = get_client_ip();
        $client_ip = str_replace('.', '_', $client_ip);
        $sms_key = "sms_{$mobile}_{$action}_{$client_ip}";
        $sms_time_out_data = S($sms_key);
        if (isset($sms_time_out_data['expire_time']) && intval(time() - $sms_time_out_data['expire_time']) < $sms_hours_time_out) {
            if (isset($sms_time_out_data['num']) && $sms_time_out_data['num'] >= $sms_hours_count) {
                $this->ajaxReturn(array('code' => -1, 'error' => '每小时只能获取两次验证码'));
            }
        }

        if (!isset($sms_time_out_data['num']) || !isset($sms_time_out_data['expire_time']) || intval(time() - $sms_time_out_data['expire_time']) >= $sms_hours_time_out) {
            $sms_time_out_data = array(
                'num' => 1,
                'expire_time' => time(),
            );
        } else {
            $sms_time_out_data['num'] = $sms_time_out_data['num'] + 1;
        }
        S($sms_key, $sms_time_out_data, $sms_hours_time_out);


        // 重新发送验证码
        $code = '';
        if ($vCodeRes) {
            $createTime = $vCodeRes['create_time'];
            $code = $vCodeRes['code'];
            $updateData = array('num' => $vCodeRes['num'] + 1);
            if (time() > $createTime + $sms_time_out) {
                $code = $updateData['code'] = $user->getCode();
                $updateData['create_time'] = time();
            }
            $sms->where(array('id' => $vCodeRes['id']))->save($updateData);
        } else {
            // 新加手机验证码
            $code = $user->getCode();
            $data = array(
                'mobile' => trim($mobile),
                'code' => $code,
                'create_time' => time(),
                'action' => $action,
                'date' => date('Y-m-d'),
                'num' => 1,
            );
            $sms->add($data);
        }

        // 服务器发送验证码
        $msg = $user->getSendSmsMsg($action);
        $msg = str_replace('MSMCode', $code, $msg);
        $res = $this->_sms(trim($mobile), $msg);
        if (isset($res['status']) && intval($res['status']) == 0) {
            $this->ajaxReturn(array('code' => 0));
        }

        $this->ajaxReturn(array('code' => -1, 'error' => '获取验证码失败！'));
    }

    /**
     * 校验验证码
     */
    public function checkMobileCode() {
        // 参数接收
        $mobile = I('post.mobile', '');
        $action = I('post.action', '');
        $vCode = I('post.vCode', '');

        $user = D('User');
        $action = strtolower($action);
        if (!trim($vCode)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '验证码不能为空！'));
        }
        if (!$user->isActionType($action)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '非法获取验证码'));
        }
        if (!trim($mobile) || !checkMobile($mobile)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '非法手机号码'));
        }

        $res = $user->checkMobileVcode($vCode, $mobile, $action);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '校验码错误'));
        }

        $uid = $this->_getUserId();
        if (!$uid) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请登录后在绑定手机号！'));
        }

        // 绑定手机号
        $model = M();
        $model->startTrans();
        $userRes = $user->isRegister(array('mobile' => trim($mobile)));
        if ($userRes) {
            $res = $user->where(array('id' => $userRes['id']))->save(array('mobile' => ''));
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '手机号码绑定失败'));
            }
        }

        $res = $user->where(array('id' => $uid))->save(array('mobile' => $mobile));
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '手机号码绑定失败'));
        }
        $model->commit();
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 团单购买
     */
    public function teamBuy() {

        // 接收参数
        $orderId = I('get.orderId', '', 'strval');
        $uid = $this->_getUserId();
        $pc_buy_other_team_config = C('PC_BUY_OTHER_TEAM');
        if (trim($orderId)) {

            if (!trim($uid)) {
                // 未登录
                redirect(U('Public/login'));
            }

            $order = M('order');
            $field = 'id,state,condbuy,team_id,user_id,origin,quantity,mobile,price,fare,address_id,optional_model,delivery_time';
            $orderRes = $order->field($field)->where(array('id' => $orderId))->find();
            if (!$orderRes) {
                redirect(U('Team/buy', array('id' => $orderRes['team_id'], 'num' => $orderRes['quantity'], 'error' => base64_encode('下单失败！'))));
            }
            if (isset($orderRes['state']) && trim($orderRes['state']) == 'pay') {
                // 跳到支付成功页面
                redirect(U('Team/teamPayResult', array('type' => $orderRes['service'], 'oid' => $orderRes['id'])));
            }

            // 数据整理
            if (isset($orderRes['condbuy']) && trim($orderRes['condbuy'])) {
                $orderRes['condbuy'] = str_replace('@', ',', $orderRes['condbuy']);
            }

            $team = D('Team');
            $field = 'id,product,team_type,is_optional_model,now_periods_number,title';
            $teamRes = $team->field($field)->where(array('id' => $orderRes['team_id']))->find();
            $team_attr_id = 0;
            if (!isset($pc_buy_other_team_config[$orderRes['team_id']]) && !isset($this->pc_buy_team_type[$teamRes['team_type']])) {
                redirect(U('Team/buy', array('id' => $orderRes['team_id'], 'num' => $orderRes['quantity'], 'error' => base64_encode('该团单不支持电脑购买，请在APP上购买！'))));
            }
            if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'goods' && isset($teamRes['is_optional_model']) && $teamRes['is_optional_model'] == 'Y') {
                $team_attr_id = @json_decode($orderRes['optional_model'], true);
                if (!$team_attr_id) {
                    $team_attr_id = array();
                }
                $team_attr_id = @array_pop($team_attr_id);
                $team_attr_id = ternary($team_attr_id['id'], '0');
            }

            $teamBuyRes = $team->teamBuy($uid, $orderRes['team_id'], $orderRes['quantity'], $orderRes['mobile'], 'pc');
            if (!$teamBuyRes || isset($teamBuyRes['error'])) {
                redirect(U('Team/buy', array('id' => $orderRes['team_id'], 'num' => $orderRes['quantity'], 'team_attr_id' => $team_attr_id, 'error' => base64_encode(ternary($teamBuyRes['error'], '')))));
            }
            if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'goods') {
                $buy_res = $team->mailTeamBuyUpdateOrder($uid, $orderId, $orderRes['address_id'], $orderRes['delivery_time'], $orderRes['optional_model']);
                if (isset($buy_res['error']) && trim($buy_res['error'])) {
                    redirect(U('Team/buy', array('id' => $orderRes['team_id'], 'num' => $orderRes['quantity'], 'team_attr_id' => $team_attr_id, 'error' => base64_encode(ternary($buy_res['error'], '')))));
                }
            }
            $data = array(
                'credit' => ternary($teamBuyRes['credit'], 0),
                'money' => ternary($teamBuyRes['money'], 0),
                'order' => $orderRes,
                'team' => $teamRes,
                'team_attr_id' => $team_attr_id,
            );
            $this->_getWebTitle(array('title' => '下单支付'));
            $this->assign($data);
            $this->display('team_pay');
            exit;
        }


        // 下单
        $tid = I('post.tid', 0, 'intval'); // 商品id
        $num = I('post.quantity', 1, 'intval'); // 购买商品的数量
        $mobile = I('post.mobile', '', 'strval'); // 电话
        $action = I('get.action', '', 'trim');
        $address_id = I('post.address_id', '', 'trim');
        $address = I('post.address', array(), '');
        $dt_time = I('post.d_time', '', 'trim');
        $team_attr_id = I('post.team_attr_id', '', 'trim');
        $remark = I('post.remark', '', 'trim');

        if (!trim($tid)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '购买的商品id不能为空'));
        }
        if (!trim($num) || intval($num) <= 0 || intval($num) > 500) {
            $this->ajaxReturn(array('code' => -1, 'error' => '非法购买数量,数量在0-500之间'));
        }
        if (!trim($mobile) || !checkMobile($mobile)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '非法手机号码'));
        }
        if (!$uid) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请登录后再购买！'));
        }

        $ota = D('Ota');

        if ($ota->tmCheck($tid)) {
            // 验证ota参数
            $realname = I('post.realname', '', 'trim');
            $use_date = I('post.use_date', '', 'trim');
            $link_cno = I('post.link_cno', '', 'trim');
            if (strlen($realname) == 0) {
                $this->ajaxReturn(array('code' => -1, 'error' => '取票人姓名不能为空！'));
            }
            if (strlen($use_date) == 0) {
                $this->ajaxReturn(array('code' => -1, 'error' => '使用日期不能为空！'));
            }
            if ($use_date < date('Y-m-d', NOW_TIME)) {
                $this->ajaxReturn(array('code' => -1, 'error' => '使用日期无效！'));
            }
            // if (!\Common\Org\IDCard::isCard($link_cno)) {
            //     $this->ajaxReturn(array('code' => -1, 'error' => '身份证号码错！'));
            // }
            // 避免修改现有下单接口teamBuy，将数据缓存sesssion中
            $ota->saveTmpinfo(array(
                'link_name' => $realname,
                'use_date'  => $use_date,
                'link_cno'  => $link_cno
            ));
        }

        $team = D('Team');
        $field = 'id,team_price,product,farefree,condbuy,team_type,is_optional_model';
        $teamRes = $team->field($field)->where(array('id' => $tid))->find();
        if (!$teamRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '你购买的订单不存在！'));
        }
        if (!isset($pc_buy_other_team_config[$tid]) && !isset($this->pc_buy_team_type[$teamRes['team_type']])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该团单不支持电脑购买，请在APP上购买！'));
        }

        $optional_model = json_encode(array());
        if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'goods') {
            if (isset($teamRes['is_optional_model']) && trim($teamRes['is_optional_model']) == 'Y') {
                if (!$team_attr_id) {
                    $this->ajaxReturn(array('code' => -1, 'error' => '请返回商品详情选择商品类型！'));
                }

                $team_atter = M('team_attribute')->where(array('id' => $team_attr_id, 'team_id' => $tid))->field('id,name,now_num,max_num')->find();
                if (!$team_atter) {
                    $this->ajaxReturn(array('code' => -1, 'error' => '你所选择的类型不存在，请返回商品详情重新选择！'));
                }
                $max_num = intval($team_atter['max_num']);
                $now_num = intval($team_atter['now_num']);
                if ($max_num > 0 && $now_num + $num > $max_num) {
                    $this->ajaxReturn(array('code' => -1, 'error' => "你所选择的类型{$team_atter['name']}库存不足！"));
                }
                $optional_model = @json_encode(array(array('id' => $team_attr_id, 'name' => $team_atter['name'], 'num' => $num)));
            }
            if (!$action) {
                if (!$address_id || $address_id == 'newaddress') {
                    // 添加地址
                    $address_id = D('Address')->addUserAddress($uid, $address);
                    if (isset($address_id['error']) && trim($address_id['error'])) {
                        redirect(U('Team/buy', array('id' => $tid, 'num' => $num, 'team_attr_id' => $team_attr_id, 'error' => base64_encode(ternary($address_id['error'], '')))));
                    }
                }
            }

            if (!$dt_time) {
                $this->ajaxReturn(array('code' => -1, 'error' => '请选择送货时间！'));
            }
        } elseif (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'cloud_shopping') {
            $teamRes['team_price'] = 1.00;
        }

        if (trim($action)) {
            // 异步校验的结果
            $this->ajaxReturn(array('code' => 0, 'data' => array('address_id' => $address_id)));
            exit;
        }


        $res = $team->teamBuy($uid, $tid, $num, $mobile, 'pc');

        // 结果处理
        if (!$res || isset($res['error'])) {
            redirect(U('Team/buy', array('id' => $tid, 'num' => $num, 'team_attr_id' => $team_attr_id, 'error' => base64_encode(ternary($res['error'], '')))));
        }

        // 邮购属性更新
        if (isset($teamRes['team_type']) && trim($teamRes['team_type']) == 'goods') {
            $buy_res = $team->mailTeamBuyUpdateOrder($uid, $res['order_id'], $address_id, $dt_time, $optional_model);
            if (isset($buy_res['error']) && trim($buy_res['error'])) {
                redirect(U('Team/buy', array('id' => $tid, 'num' => $num, 'team_attr_id' => $team_attr_id, 'error' => base64_encode(ternary($buy_res['error'], '')))));
            }
            if (trim($remark)) {
                M('order')->where(array('id' => $res['order_id']))->save(array('remark' => htmlspecialchars($remark)));
            }
        }

        // 下单成功，清理缓存数据
        $ota->tmCheck($tid) ? $ota->clearTmpinfo() : null;

        $orderId = ternary($res['order_id'], '');
        redirect(U('Team/teamBuy', array('orderId' => $orderId)));
    }

    /**
     * 团单支付
     */
    public function teamPay() {

        // 接收参数
        $payType = I('post.paytype', '', 'strval');
        $bankType = I('post.bank_type', '', 'strval');
        $bankTypeValue = I('post.bank_type_value', '', 'strval');
        $credit = I('post.credit', 0.0, 'doubleval');
        $money = I('post.money', 0.0, 'doubleval');
        $oid = I('post.oid', 0, 'intval');
        $creditType = I('post.credittype', '', 'strval');
        $uid = $this->_getUserId();

        // 参数判断
        if (!trim($oid) || !trim($uid)) {
            redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('订单号不能为空！'))));
        }
        $order = M('order');
        $orderRes = $order->where(array('id' => $oid))->find();
        //判断该订单是否存在
        if (!$orderRes) {
            redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('订单不存在！'))));
        }
        if (isset($orderRes['state']) && trim($orderRes['state']) == 'pay') {
            // 跳到支付成功页面
            redirect(U('Team/teamPayResult', array('type' => $orderRes['service'], 'oid' => $oid)));
        }

        $user = M('user');
        $userRes = $user->where(array('id' => $uid))->find();

        // 非法支付判断
        if (trim($payType) == 'freepay' && isset($orderRes['origin']) && $orderRes['origin'] > 0) {
            redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('非法支付！'))));
        }
        if (trim($payType) == 'credit' && isset($orderRes['origin']) && isset($userRes['money']) && $userRes['money'] < $orderRes['origin']) {
            redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('非法支付！'))));
        }

        // 根据paytype 参数调整
        switch (trim($payType)) {
            case 'thirdparty':
                $payType = 'tenpay';
                if (trim($bankType) == 'alipay' || trim($bankType) == 'alipaycode' || trim($bankType) == 'pcwxcode') {
                    $payType = trim($bankType);
                }

                $money = $orderRes['origin'];
                $credit = 0;
                if ($creditType) {
                    $money = $orderRes['origin'] - $userRes['money'];
                    $credit = $userRes['money'];
                }
                break;
            case 'credit':
                $credit = $orderRes['origin'];
                break;
            case 'freepay':
                $credit = 0;
                break;
            default:
                break;
        }

        // 余额支付
        $order->where(array('id' => $oid))->save(array('credit' => $credit));

        // 更新pay_id
        $randId = strtr(sprintf("%.2f", $money), array('.' => '-', ',' => '-'));
        $order_num = ternary($orderRes['quantity'], 0);
        $pay_id = "go-{$oid}-{$order_num}-{$randId}";
        $res = $order->where(array('id' => $oid))->save(array('pay_id' => $pay_id));
        if ($res === false) {
            redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('支付标识更新失败！'))));
        }

        $team = D('Team');
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();
        $nowTime = time();
        $pay = new \Common\Org\Pay();
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        $option = array(
            'return_url' => $host . U('Team/teamPayResult', array('type' => $payType, 'oid' => $oid)),
            'merchant_url' => $host . U('Team/teamPayResult', array('type' => $payType, 'oid' => $oid)),
            'return_CodeUrl' => $host . U('Team/teamBuy', array('orderId' => $oid)),
        );
        // 根据支付类型支付
        switch (trim($payType)) {
            case 'freepay':
                $model = M();
                $model->startTrans();
                $data = array(
                    'service' => 'credit',
                    'state' => 'pay',
                    'rstate' => 'normal',
                    'money' => $money,
                    'credit' => $credit,
                );
                $res = $order->where(array('id' => $oid))->save($data);
                if (!$res) {
                    $model->rollback();
                    redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('订单状态更新失败！'))));
                }
                $res = $order->where(array('id' => $oid))->save(array('pay_time' => $nowTime));

                // 更新团单已买数量
                $nowNumber = $orderRes['quantity'] + $teamRes['now_number'];
                $this->_updateRowDataToOTS('team', array('id' => $orderRes['team_id']), array('now_number' => $nowNumber));
                // 数据库更新
                $res = $team->where(array('id' => $orderRes['team_id']))->setInc('now_number', $orderRes['quantity']);
                if (!$res) {
                    $model->rollback();
                    redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('团单购买数量更新错误！'))));
                }

                if (isset($teamRes['delivery']) && trim($teamRes['delivery']) == 'coupon') {
                    $res = $team->addCoupon($orderRes, $teamRes);
                    if (!$res) {
                        $model->rollback();
                        redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('券号生成错误！'))));
                    }
                }

                // 添加评论
                $res = $team->addComment($orderRes, $teamRes);
                if (!$res) {
                    $model->rollback();
                    redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('评论添加失败！'))));
                }
                $model->commit();
                // 购买成功后发送短信
                $team->paySuccessSendSms($orderRes, $teamRes, true);

                // 跳转到支付成功页面
                redirect(U('Team/teamPayResult', array('type' => 'credit', 'oid' => $oid)));
                break;
            case 'credit':
                $res = $team->teamPay($uid, $oid, 'creditpay', 'pc');
                if (!$res || isset($res['error'])) {
                    redirect(U('Team/teamBuy', array('orderId' => $oid, 'error' => base64_encode('余额支付失败，' . ternary($res['error'], '')))));
                }
                // 跳转到支付成功页面
                redirect(U('Team/teamPayResult', array('type' => 'credit', 'oid' => $oid)));
                break;
            case 'alipay':
                $data = array(
                    'out_trade_no' => $pay_id,
                    'subject' => $teamRes['product'],
                    'total_fee' => $money,
                    'body' => $teamRes['title'],
                    'show_url' => __APP__ . '/',
                );
                M('order')->where(array('id' => $oid))->save(array('service' => 'pcalipay'));
                $pay->pcDoPay('pcAlipay', $data, $option);
                break;
            case 'tenpay':
                $data = array(
                    'bank_type' => $bankTypeValue,
                    'out_trade_no' => $pay_id,
                    'product_name' => $teamRes['product'],
                    'trade_mode' => 1,
                    'total_fee' => $money * 100,
                    'desc' => "商品：" . $teamRes['product'],
                );
                M('order')->where(array('id' => $oid))->save(array('service' => 'pctenpay'));
                $pay->pcDoPay('pcTenpay', $data, $option);
                break;
            case 'alipaycode':
                $option = array(
                    'return_url' => $host . U('Team/teamPayResult', array('type' => $payType, 'oid' => $oid, 'code' => 'success')),
                    'merchant_url' => $host . U('Team/teamPayResult', array('type' => $payType, 'oid' => $oid, 'code' => 'success')),
                    'return_CodeUrl' => $host . U('Team/teamPayResult', array('type' => $payType, 'oid' => $oid, 'code' => 'fail')),
                );
                $data = array(
                    'out_trade_no' => $pay_id,
                    'subject' => $teamRes['product'],
                    'total_fee' => $money,
                    'body' => $teamRes['title'],
                    'show_url' => __APP__ . '/',
                );
                M('order')->where(array('id' => $oid))->save(array('service' => 'pcalipay'));
                $html = $pay->pcDoPay('pcAlipay', $data, $option, 'code');
                $data = array(
                    'html' => $html,
                    'order_id' => $oid,
                    'money' => $money,
                );
                $this->assign($data);
                $this->display('team_alipay_code');
                exit;
                break;
            case 'pcwxcode':
                M('order')->where(array('id' => $oid))->save(array('service' => 'pcwxpaycode'));
                $code_url = $pay->getPCWXpayData($pay_id, $teamRes['title'], $teamRes['product'], $money * 100, 'pc');
                $data = array(
                    'code_url' => $code_url,
                    'success_url' => U('Team/teamPayResult', array('oid' => $oid)),
                    'order_id' => $oid,
                    'money' => $money,
                );
                $this->assign($data);
                $this->display('team_pcwx_code');
                exit;
                break;
            default:
                break;
        }
    }

    /**
     * 获取微信扫码支付结果
     */
    public function getOrderPayState() {
        $oid = I('get.oid', '', 'trim');
        if (!$oid) {
            $this->ajaxReturn(array('code' => -1, 'error' => '订单单id不能为空！'));
        }
        if (is_string($oid)) {
            $oid = explode('_', $oid);
        }
        $where = array(
            'id' => array('in', $oid),
            'state' => 'pay'
        );
        $oidCount = count($oid);
        $orderCount = M('order')->where($where)->count();
        if (!$orderCount || $orderCount != $oidCount) {
            $this->ajaxReturn(array('code' => -1, 'error' => '未支付！'));
        }
        $this->ajaxReturn(array('code' => 1));
    }

    /**
     * 支付成功跳转地址
     */
    public function teamPayResult() {
        $orderId = I('get.oid', 0, 'intval');
        $type = I('get.type', '', 'strval');
        $code = I('get.code', '', 'strval');

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
            $parent_url = $host . U('Team/teamPayResult', array('type' => $type, 'oid' => $orderId));
        } elseif (trim($code) && strtolower(trim($code)) == 'fail') {
            $parent_url = $host . U('Team/teamBuy', array('orderId' => $orderId));
        }
        if (trim($parent_url)) {
            $str = "<script>window.parent.location.href='$parent_url';</script>";
            die($str);
        }

        if (!trim($orderId)) {
            redirect(U('Team/teamBuy', array('orderId' => $orderId, 'error' => base64_encode('订单号不存在！'))));
        }

        $order = M('order');
        $orderRes = $order->where(array('id' => $orderId))->find();
        if (!$orderRes) {
            redirect(U('Team/teamBuy', array('orderId' => $orderId, 'error' => base64_encode('订单不存在！'))));
        }

        if (!$uid || !isset($orderRes['user_id']) || $uid != trim($orderRes['user_id'])) {
            redirect(U('Team/teamBuy', array('orderId' => $orderId, 'error' => base64_encode('登录用户与下单用户不一致！'))));
        }
        // 未支付跳到支付页面
        if (isset($orderRes['state']) && trim($orderRes['state']) == 'unpay') {
            $this->redirect(U('Team/teamBuy', array('orderId' => $orderId)));
        }



        $team = M('team');
        $teamRes = $team->where(array('id' => $orderRes['team_id']))->find();
        $coupons = M('coupon')->where(array('order_id' => $orderId))->select();
        $this->assign('coupons', $coupons);
        $this->assign('team', $teamRes);

        $orderRes['pay_detail'] = '';
        if (isset($orderRes['optional_model']) && trim($orderRes['optional_model'])) {
            $optional_model = @json_decode($orderRes['optional_model'], true);
            if ($optional_model) {
                foreach ($optional_model as $v) {
                    $orderRes['pay_detail'].="{$v['name']} * {$v['num']}</br>";
                }
            }
        }

        $orderRes['pay_address'] = '';
        if (isset($orderRes['address']) && trim($orderRes['address'])) {
            $address = @json_decode($orderRes['address'], true);
            if ($address) {
                $orderRes['pay_address'] = "{$address['province']}{$address['area']}{$address['city']}{$address['street']}<br/>{$address['name']} {$address['mobile']}";
            }
        }

        // 获取微信二维码
        $imageUrl = $this->_getQRImageUrl($orderId);
        $data = array(
            'order' => $orderRes,
            'imgurl' => $imageUrl,
        );
        $this->_getWebTitle(array('title' => '支付结果'));
        $this->assign($data);
        $this->display('team_pay_result');
    }

}
