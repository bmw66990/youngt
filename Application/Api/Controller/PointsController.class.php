<?php

namespace Api\Controller;

class PointsController extends CommonController {

    protected $checkUser = true;

    /**
     * 兑换记录
     */
    public function exchangeRecord() {
        $last_id = I('get.lastid', 0, 'intval');
        $Model = D('PointsOrder');
        $map = array(
            'po.user_id' => $this->uid
        );
        if ($last_id) {
            $where['id'] = array('lt', $last_id);
        }
        $field = 'po.expire_time,po.id,po.code,po.consume,po.consume_time,po.total_score,pt.name,pt.image';
        $limit = $this->reqnum;
        $data = $Model->getList($map, 'po.id DESC', $limit + 1, $field);
        $this->setHasNext(false, $data, $this->reqnum);
        if ($data) {
            foreach ($data as &$val) {
                if ($val['consume'] == 'Y') {
                    $val['status'] = 'Y';
                } else if ($val['expire_time'] < strtotime(date('Y-m-d'))) {
                    $val['status'] = 'E';
                } else {
                    $val['status'] = 'N';
                }
                unset($val['consume']);
                $val['consume_time'] = date('Y-m-d', $val['consume_time']);
                $val['expire_time'] = date('Y-m-d', $val['expire_time']);
                $val['image'] = getImagePath($val['image']);
            }
            unset($val);
        }
        $this->outPut($data, 0);
    }

    /**
     * 积分说明
     */
    public function pointsDescription() {

        $this->display();
    }

    /**
     * 抽奖大转盘
     */
    public function pointsLuckDraw() {
        $token = I('get.token', '', 'trim');
        $score = M('user')->getFieldById($this->uid, 'score');
        $this->assign(array('token' => $token, 'score' => $score));
        $this->display();
    }

    /**
     * 兑换商品列表
     */
    public function pointsTeamList() {
        $this->_checkblank(array('city_id'));
        $city_id = I('get.city_id', '', 'trim');
        $lastid = I('get.lastid', '', 'trim');

        $now_time = time();
        $where = array(
            'city_id' => $city_id,
         //   'is_display' => 'display',
            'begin_time' => array('elt', $now_time),
            'end_time' => array('egt', $now_time),
         //   'expire_time' => array('egt', $now_time),
        );

        if ($lastid) {
            $where['id'] = array('lt', $lastid);
        }

        $field = array(
            'id' => 'id',
            'name' => 'name',
            'image' => 'image',
            'score' => 'score',
        );
        $list = M('points_team')->where($where)->field($field)->limit($this->reqnum + 1)->order(array('id' => 'desc'))->select();
        $this->setHasNext(false, $list, $this->reqnum);
        if ($list) {
            foreach ($list as &$v) {
                $v['image'] = getImagePath($v['image']);
            }
            unset($v);
        }
        $this->outPut($list, 0);
    }

    /**
     * 兑换商品列表
     */
    public function pointsTeamDetail() {
        $this->_checkblank(array('city_id', 'points_team_id'));
        $city_id = I('get.city_id', '', 'trim');
        $points_team_id = I('get.points_team_id', '', 'trim');

        if (!$points_team_id) {
            $this->outPut(null, -1, null, '积分商品id不能为空！');
        }

        $points_team_info = M('points_team')->where(array('id' => $points_team_id))->find();

        // 数据整理
        unset($points_team_info['is_display']);
        // 是否过期  state=1 正常，2：过期，3:未开始
        $now_time = time();
        $points_team_info['state'] = '1';
        $points_team_info['now_time'] = strval($now_time);
        if ($now_time > $points_team_info['end_time'] || $now_time > $points_team_info['expire_time']) {
            $points_team_info['state'] = '2';
        }

        if ($now_time < $points_team_info['begin_time']) {
            $points_team_info['state'] = '3';
        }
        $points_team_info['image'] = getImagePath($points_team_info['image']);
        // 标签显示
        $points_team_info['show_label'] = array(
            "每人限购{$points_team_info['convert_num']}份",
            '不支持退款'
        );
        if ($points_team_info['convert_num'] <= 0) {
            array_shift($points_team_info['show_label']);
        }
        $this->outPut($points_team_info, 0);
    }

    /**
     * 点击兑换
     */
    public function pointsTeamExchange() {
        $this->_checkblank(array('points_team_id', 'num', 'plat'));
        $points_team_id = I('post.points_team_id', '', 'trim');
        $num = I('post.num', '1', 'trim');
        $plat = I('post.plat', '', 'trim');

        $points_order = D('PointsOrder');
        $res = $points_order->pointsTeamExchange($points_team_id, $num, $this->uid, $plat);
        if (isset($res['error']) && trim($res['error'])) {
            $this->outPut(null, -1, null, $res['error']);
        }
        $this->outPut($res, 0);
    }

}
