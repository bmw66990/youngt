<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-20
 * Time: 13:41
 */

namespace MerchantApi\Controller;

/**
 * 商家券号验证接口
 * Class CouponController
 * @package MerchantApi\Controller
 */
class CouponController extends CommonController {

    /**
     * 获取券号信息
     */
    public function getCoupons() {
        $this->_checkblank('id');
        $id = str_replace(' ', '',I('post.id','','trim'));
        $var = I('post.var','','trim');
        $where = array(
            'id' => $id,
        );
        $partner_ids_where = $this->_getPartnerByid($this->uid);
        if ($partner_ids_where) {
            $where['partner_id'] = $partner_ids_where;
        }

        $coupon = D('Coupon');
        $coupon_info = $coupon->where($where)->find();

        if (!$coupon_info) {
            $this->outPut('', -1, '', '券号不存在或券号不属于该商家');
        }

        if (!isset($coupon_info['team_id']) || !trim($coupon_info['team_id'])) {
            $this->outPut('', -1, '', '该券号所属团单信息异常');
        }

        if (!isset($coupon_info['partner_id']) || !trim($coupon_info['partner_id'])) {
            $this->outPut('', -1, '', '该券号所属商家信息异常');
        }

        if (!isset($coupon_info['order_id']) || !trim($coupon_info['order_id'])) {
            $this->outPut('', -1, '', '该券号所属订单信息异常');
        }

        $team_info = M('team')->where(array('id' => $coupon_info['team_id']))->field('product,team_price,market_price,begin_time')->find();
        if (!$team_info) {
            $this->outPut('', -1, '', '该券号所属团单信息异常');
        }

        $partner_info = M('partner')->where(array('id' => $coupon_info['partner_id']))->field('title,mobile')->find();
        if (!$partner_info) {
            $this->outPut('', -1, '', '该券号所属商家信息异常');
        }

        $data = array(
            'id' => ternary($coupon_info['id'], ''),
            'title' => ternary($team_info['product'], ''),
            'price' => ternary($team_info['team_price'], ''),
            'expire_time' => ternary($coupon_info['expire_time'], ''),
            'begin_time' => ternary($team_info['begin_time'], ''),//2016.07.05增加上线时间
            'create_time' => ternary($coupon_info['create_time'], ''),//2016.07.12增加购买时间
            'partner' => ternary($team_info['product'], ''),//2016.5.9安卓取值错误ternary($partner_info['title'], ''),
            'mobile' => ternary($partner_info['mobile'], ''),
            'status' => 1, // 状态 1-已消费  2-已过期 3-验证成功 4-有多个券号
        );

        if (isset($coupon_info['expire_time']) && $coupon_info['expire_time'] < strtotime(date('Y-m-d'))) {
            $data['status'] = 2;
            $this->outPut($data, 0);
        }

        $where = array(
            'order_id' => $coupon_info['order_id'],
            'consume' => 'N'
        );
        
        if($var){
            $list = $coupon->where($where)->select();
        }else{
            $list = $coupon->where($where)->getField('id,consume,team_type', true);
        }

        if ($list && is_array($list)) {
            $list_count = count($list);
            //$list_count == 1 2016.4.9加
            if (false) {
                // 直接验证
                if (isset($coupon_info['consume']) && $coupon_info['consume'] == 'Y') {
                    $data['status'] = 1;
                } else {
                    // 单挑未验证的直接验证
                    $res = $this->_verify_coupon(array(ternary($coupon_info['id'], '')), $coupon_info['order_id']);
                    if (isset($res['error']) && $res['error']) {
                        $this->outPut('', -1, '', $res['error']);
                    }
                    $data['status'] = 3;
                    $data['list'] = $id;
                }
            } else {
                $data['status'] = 4;
                if($var){
                    $li = array();                
                    foreach ($list as $k=> &$v) {
                        $li[$k]['id'] = $v['id'];
                        $twhere = array(
                            'id'=>$v['team_id'],
                        );
                        $teamdata = M('team')->where($twhere)->field('id,activities_id')->find();
                        $awhere =array(
                            'id'=>$teamdata['activities_id'],
                        );
                        $admadata = M('admanage')->where($awhere)->field('id,textarr')->find();
                        if($admadata['textarr'] == '新客立减'){
                            $li[$k]['team_type'] = '减';
                        }elseif($admadata['textarr'] == '新用户专享'){
                            $li[$k]['team_type'] = '新';
                        }else{
                            $li[$k]['team_type'] = '';
                        }                        
                    }
                    $data['list'] = $li;
                }else{
                    $data['list'] = implode(',', array_keys($list));    
                }
            }
        }

        $this->outPut($data, 0);
    }

    /**
     * 验证券号
     */
    public function verify() {
        $this->_checkblank('id');
        $coupon_ids = str_replace(' ', '',I('post.id','','trim'));
        if (!$coupon_ids) {
            $this->outPut('', -1, '', '要验证的券号不能为空！');
        }
        if (is_string($coupon_ids)) {
            $coupon_ids = explode(',', $coupon_ids);
        }

        $coupon_info = $this->_verify_coupon($coupon_ids);
        if (isset($coupon_info['error']) && $coupon_info['error']) {
            $this->outPut('', -1, '', $coupon_info['error']);
        }

        $verify_count = count($coupon_ids);
        if (!$coupon_info) {
            $this->outPut('', -1, '', '券号不存在或券号不属于该商家');
        }

        if (!isset($coupon_info['team_id']) || !trim($coupon_info['team_id'])) {
            $this->outPut('', -1, '', '该券号所属团单信息异常');
        }

        if (!isset($coupon_info['partner_id']) || !trim($coupon_info['partner_id'])) {
            $this->outPut('', -1, '', '该券号所属商家信息异常');
        }

        if (!isset($coupon_info['order_id']) || !trim($coupon_info['order_id'])) {
            $this->outPut('', -1, '', '该券号所属订单信息异常');
        }

        $team_info = M('team')->where(array('id' => $coupon_info['team_id']))->field('product,team_price,market_price')->find();
        if (!$team_info) {
            $this->outPut('', -1, '', '该券号所属团单信息异常');
        }

        $partner_info = M('partner')->where(array('id' => $coupon_info['partner_id']))->field('title,mobile')->find();
        if (!$partner_info) {
            $this->outPut('', -1, '', '该券号所属商家信息异常');
        }

        $data = array(
            'title' => ternary($team_info['product'], ''),
            'price' => ternary($team_info['team_price'], ''),
            'expire_time' => ternary($coupon_info['expire_time'], ''),
            'partner' => ternary($partner_info['title'], ''),
            'mobile' => ternary($partner_info['mobile'], ''),
            'status' => 3,
            'consume_time' => time(),//消费时间
            'verify_count' => $verify_count,
            'list' => implode(',', $coupon_ids)
        );
        $this->outPut($data, 0);
    }

    /**
     * 券号验证
     * @param type $coupon_ids
     * @param type $order_id
     * @return boolean
     */
    private function _verify_coupon($coupon_ids = array(), $order_id = '') {
        if (!$coupon_ids || !trim(implode('', $coupon_ids))) {
            return array('error' => '要验证的券号不能为空！');
        }

        $where = array(
            'id' => array('in', $coupon_ids),
        );
        $partner_ids_where = $this->_getPartnerByid($this->uid);
        if ($partner_ids_where) {
            $where['partner_id'] = $partner_ids_where;
        }
        $coupon_list = M('coupon')->where($where)->select();
        if (!$coupon_list) {
            return array('error' => '要验证的券号不存在或不是本店券号！');
        }
        $error_arr = array();
        foreach ($coupon_list as $row) {
            if ($row['consume'] == 'Y') {
                $error_arr[] = $row['id'] . '已消费 ';
                continue;
            }

            if ($row['expire_time'] < strtotime(date('Y-m-d'))) {
                $error_arr[] = $row['id'] . '已过期 ';
                continue;
            }

            if (!$order_id) {
                $order_id = $row['order_id'];
            }
        }
        if ($error_arr) {
            return array('error' => implode(';', $error_arr));
        }
        $data = array(
            'id' => $coupon_ids,
            'list' => $coupon_list,
            'order_id' => $order_id,
        );
        $res = D('Coupon')->consumeCoupon($data, $this->uid, '商户端pc后台');
        if (!$res) {
            return array('error' => '券号验证失败！');
        }
        return array_pop($coupon_list);
    }
    
    /**
     * 获取验证记录 外层
     */
    public function getCouponVerifyRecordOut(){
        $last_id = I('get.last_id', '', 'trim');
        $spay_time = I('get.spay_time', '', 'intval');
        $epay_time = I('get.epay_time', '', 'intval');
        $oneday = 24*60*60;
        $epay_time = $epay_time +$oneday;

         $where = array(
            'consume' => 'Y',
            'partner_id' => $this->uid,
         );
        if($spay_time && $epay_time){            
            $where['consume_time'] =array('between',array($spay_time,$epay_time));
        } 
         if($last_id){
             $where['consume_time'] = array('lt',$last_id);
         }
         
        $field = array(
            'max(consume_time)' => 'consume_time',
            'count(id)' => 'verify_count'
        );
        $record_list = M('coupon')->where($where)->field($field)->order('consume_time desc')->group("FROM_UNIXTIME(consume_time, '%Y-%m-%d')")->limit($this->reqnum + 1)->select();
        $this->setHasNext(false, $record_list, $this->reqnum);
        if($record_list){
            $now_time = time();
            foreach($record_list as &$v){
                $v['now_time']=strval($now_time);
            }
            unset($v);
        }
        
        $this->outPut($record_list, 0);
    }

    /**
     *  获取券号验证记录[里层]
     */
    public function getCouponVerifyRecord() {
        $consume_time = I('get.consume_time', '', 'trim');
        $last_id = I('get.last_id', '', 'trim');
        $end_id = I('get.end_id', '', 'trim');
        
        if(!$consume_time){
            $consume_time = time();
        }
        
        $begin_time = strtotime(date('Y-m-d 00:00:00',$consume_time));
        $end_time = strtotime('+1 day '.date('Y-m-d 00:00:00',$consume_time));
        $where = array(
            'consume' => 'Y',
            'partner_id' => $this->uid,
            'consume_time' => array(array('egt', $begin_time),array('elt', $end_time))
        );

        if ($last_id && $end_id) {
            $order_where = D('Team')->getMysqlSortWhere('consume_time', $last_id, 'order_id', $end_id, '-');
            if ($order_where) {
                if (isset($where['_string']) && trim($where['_string'])) {
                    $order_where = "({$where['_string']}) and $order_where";
                }
                $where['_string'] = $order_where;
            }
        }

        $field = array(
            'order_id' => 'order_id',
            'max(team_id)' => 'team_id',
            'max(consume_time)' => 'consume_time',
            'count(id)' => 'verify_count'
        );

        $record_list = M('coupon')->where($where)->field($field)->order('consume_time desc,order_id desc')->group('order_id')->limit($this->reqnum + 1)->select();
        $this->setHasNext(false, $record_list, $this->reqnum);
        if ($record_list) {
            $team_ids = $order_ids = array();
            foreach ($record_list as &$v) {
                if (isset($v['team_id']) && trim($v['team_id'])) {
                    $team_ids[$v['team_id']] = $v['team_id'];
                }

                if (isset($v['order_id']) && trim($v['order_id'])) {
                    $order_ids[$v['order_id']] = $v['order_id'];
                }
            }
            unset($v);
            $team_info = array();
            if ($team_ids) {
                $team_info = M('team')->where(array('id' => array('in', array_keys($team_ids))))->getField('id,product', true);
                $price = M('team')->where(array('id' => array('in', array_keys($team_ids))))->getField('id,team_price', true);
            }
            $coupon_list = array();
            if ($order_ids) {
                $coupon_where = array(
                    'consume' => 'Y',
                    'partner_id' => $this->uid,
                    'consume_time' => array(array('egt', $begin_time),array('elt', $end_time)),
                    'order_id' => array('in', array_keys($order_ids))
                );
                $coupon_res = M('coupon')->where($coupon_where)->field('order_id,id')->select();
                foreach ($coupon_res as &$_v) {
                    if (!isset($coupon_list[$_v['order_id']]) || !$coupon_list[$_v['order_id']]) {
                        $coupon_list[$_v['order_id']] = array();
                    }
                    $coupon_list[$_v['order_id']][] = $_v['id'];
                }
                unset($_v);
            }
            //var_dump($coupon_list);exit;
            foreach ($record_list as &$v) {

                $v['team_product'] = ternary($team_info[$v['team_id']], '');
                $v['price'] = ternary($price[$v['team_id']], '');
                $v['coupon_list'] = array();
                if (isset($coupon_list[$v['order_id']]) && $coupon_list[$v['order_id']]) {
                    $v['coupon_list'] = array_values($coupon_list[$v['order_id']]);
                }
            }
            unset($v);
        }

        $this->outPut($record_list, 0);
    }

    /**
     * 获取积分兑换码信息
     */
    public function getPointsDetail() {
        $this->_checkblank('points_code');
        $points_code = str_replace(' ', '',I('post.points_code','','trim'));
        if (!$points_code) {
            $this->outPut('', -1, '', '积分兑换码不能为空！');
        }
        $where = array(
            'code' => $points_code,
            'partner_id' => $this->uid,
        );
        $points_order_info = M('points_order')->where($where)->find();
        if (!$points_order_info) {
            $this->outPut('', -1, '', '兑换券错误或该兑换券对应商品不是本店商品！');
        }

        if (!isset($points_order_info['team_id']) || !trim($points_order_info['team_id'])) {
            $this->outPut('', -1, '', '该兑换券所属商品信息异常');
        }

        if (!isset($points_order_info['partner_id']) || !trim($points_order_info['partner_id'])) {
            $this->outPut('', -1, '', '该兑换券所属商家信息异常');
        }

        $points_team_info = M('points_team')->where(array('id' => $points_order_info['team_id']))->field('name,begin_time')->find();
        if (!$points_team_info) {
            $this->outPut('', -1, '', '该兑换券所属商品信息异常');
        }

        $partner_info = M('partner')->where(array('id' => $points_order_info['partner_id']))->field('title,mobile')->find();
        if (!$partner_info) {
            $this->outPut('', -1, '', '该兑换券所属商家信息异常');
        }

        $data = array(
            'title' => ternary($points_team_info['name'], ''),
            'begin_time' => ternary($points_team_info['begin_time'], ''),//2016.07.05上线时间
            'score' => ternary($points_order_info['score'], ''),
            'num' => intval(ternary($points_order_info['num'], '')),
            'total_score' => ternary($points_order_info['total_score'], ''),
            'points_code' => ternary($points_order_info['code'], ''),
            'expire_time' => ternary($points_order_info['expire_time'], ''),
            'partner' => ternary($partner_info['title'], ''),
            'mobile' => ternary($partner_info['mobile'], ''),
            'status' => 4, // 状态 1-已消费  2-已过期 3-验证成功 4-未验证
        );

        if (isset($points_order_info['expire_time']) && $points_order_info['expire_time'] < strtotime(date('Y-m-d'))) {
            $data['status'] = 2;
        }

        if (isset($points_order_info['consume']) && trim($points_order_info['consume']) == 'Y') {
            $data['status'] = 1;
        }

        $this->outPut($data, 0);
    }

    /**
     *   积分兑换
     */
    public function pointsVerify() {
        $this->_checkblank('points_code');
        $points_code = str_replace(' ', '',I('post.points_code','','trim'));
        if (!$points_code) {
            $this->outPut('', -1, '', '积分兑换码不能为空！');
        }
        
        if(strlen($points_code) != 10){
            $this->outPut('', -1, '', '积分兑换码长度为10！');
        }

        $where = array(
            'code' => $points_code,
            'partner_id' => $this->uid,
        );
        $points_order_info = M('points_order')->where($where)->find();
        if (!$points_order_info) {
            $this->outPut('', -1, '', '兑换券错误或该兑换券对应商品不是本店商品！');
        }

        if (!isset($points_order_info['team_id']) || !trim($points_order_info['team_id'])) {
            $this->outPut('', -1, '', '该兑换券所属商品信息异常');
        }

        if (!isset($points_order_info['partner_id']) || !trim($points_order_info['partner_id'])) {
            $this->outPut('', -1, '', '该兑换券所属商家信息异常');
        }

        $points_team_info = M('points_team')->where(array('id' => $points_order_info['team_id']))->field('name')->find();
        if (!$points_team_info) {
            $this->outPut('', -1, '', '该兑换券所属商品信息异常');
        }

        $partner_info = M('partner')->where(array('id' => $points_order_info['partner_id']))->field('title,mobile')->find();
        if (!$partner_info) {
            $this->outPut('', -1, '', '该兑换券所属商家信息异常');
        }

        $data = array(
            'title' => ternary($points_team_info['name'], ''),
            'score' => ternary($points_order_info['score'], ''),
            'num' => intval(ternary($points_order_info['num'], '')),
            'total_score' => ternary($points_order_info['total_score'], ''),
            'points_code' => ternary($points_order_info['code'], ''),
            'expire_time' => ternary($points_order_info['expire_time'], ''),
            'partner' => ternary($partner_info['title'], ''),
            'consume_time' => time(),//消费时间
            'mobile' => ternary($partner_info['mobile'], ''),
            'status' => 4, // 状态 1-已消费  2-已过期 3-验证成功 4-未验证
        );

        if (isset($points_order_info['expire_time']) && $points_order_info['expire_time'] < strtotime(date('Y-m-d'))) {
            $data['status'] = 2;
            $this->outPut($data, 0);
        }

        if (isset($points_order_info['consume']) && trim($points_order_info['consume']) == 'Y') {
            $data['status'] = 1;
            $this->outPut($data, 0);
        }

        $up_data = array(
            'consume' => 'Y',
            'consume_time' => time()
        );
        $res = M('points_order')->where(array('id'=>$points_order_info['id']))->save($up_data);
        if(!$res){
            $this->outPut('', -1, '', '兑换券兑换失败！');
        }
        $data['status'] = 3;
        $this->outPut($data, 0);
    }
    
    /**
     * 获取 验证记录
     */
    public function getPointsVerifyRecord(){
        $last_id = I('get.last_id', '', 'trim');
        $end_id = I('get.end_id', '', 'trim');

        $where = array(
            'consume' => 'Y',
            'partner_id' => $this->uid,
            'consume_time' => array('gt', 0)
        );

        if ($last_id && $end_id) {
            $order_where = D('Team')->getMysqlSortWhere('consume_time', $last_id, 'id', $end_id, '-');
            if ($order_where) {
                if (isset($where['_string']) && trim($where['_string'])) {
                    $order_where = "({$where['_string']}) and $order_where";
                }
                $where['_string'] = $order_where;
            }
        }

        $field = array(
            'id' => 'order_id',
            'team_id' => 'team_id',
            'consume_time' => 'consume_time',
            'num' => 'verify_count',
            'code' => 'code',
            'score'=>'socre',
            'total_score'=>'total_score',
        );

        $record_list = M('points_order')->where($where)->field($field)->order('consume_time desc,id desc')->limit($this->reqnum + 1)->select();
        $this->setHasNext(false, $record_list, $this->reqnum);
        if ($record_list) {
            $team_ids = array();
            foreach ($record_list as &$v) {
                if (isset($v['team_id']) && trim($v['team_id'])) {
                    $team_ids[$v['team_id']] = $v['team_id'];
                }
            }
            unset($v);
            $team_info = array();
            if ($team_ids) {
                $team_info = M('points_team')->where(array('id' => array('in', array_keys($team_ids))))->getField('id,name', true);
            }
            foreach ($record_list as &$v) {
                $v['team_product'] = ternary($team_info[$v['team_id']], '');
            }
            unset($v);
        }

        $this->outPut($record_list, 0);
    }

}
