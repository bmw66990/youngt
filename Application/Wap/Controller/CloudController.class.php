<?php

/**
 * Created by PhpStorm.
 * User: 
 * Date: 
 * Time:
 */

namespace Wap\Controller;

/**
 * 用户操作控制器
 * Class CloudController
 * @package Wap\Controller
 */
class CloudController extends CommonController {

    /**
     * 正在进行中
     */
    public function index() {

        $now_time = time();
        $where = array(
            'team_type' => 'cloud_shopping',
            'begin_time' => array('lt', $now_time),
            '_string' => "now_periods_number<=max_periods_number and now_number<max_number",
        );
        $count = M('team')->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $page->setConfig('theme', "%FIRST% %UP_PAGE% %DOWN_PAGE%");
        $limit = $page->firstRow . ',' . $page->listRows;
        $page_html = $page->show();
        $field = array(
            'id',
            'image',
            'product',
            'title',
            'now_periods_number',
            'max_periods_number',
            'now_number',
            'max_number',
        );
        $res = M('team')->where($where)->limit($limit)->field($field)->order('id desc')->select();
        if ($res) {
            $user_ids = array();
            foreach ($res as &$v) {
                if (isset($v['image']) && trim($v['image'])) {
                    $v['image'] = getImagePath($v['image']);
                }
                $v['progress'] = 0;
                if (isset($v['now_number']) && trim($v['now_number']) && isset($v['max_number']) && $v['max_number'] > 0) {
                    $v['progress'] = ($v['now_number'] / $v['max_number']) * 100;
                }
            }
            unset($v);
        }

        $data = array(
            'count' => $count,
            'page' => $page_html,
            'list' => $res,
            'winning_announce' => $this->_winning_announce(),
        );
        $this->assign($data);
        $this->display();
    }

    /**
     *  最新揭晓
     */
    public function announce() {
        $where = array(
            'team.team_type' => 'cloud_shopping',
            'cloud_shoping_result.status' => array('gt', 0),
        );
        $count = M('team')->where($where)->join('inner join cloud_shoping_result on cloud_shoping_result.team_id=team.id')->count();
        $page = $this->pages($count, $this->reqnum);
        $page->setConfig('theme', "%FIRST% %UP_PAGE%  %DOWN_PAGE%");
        $limit = $page->firstRow . ',' . $page->listRows;
        $page_html = $page->show();
        $field = array(
            'team.id' => 'id',
            'team.image' => 'image',
            'team.product' => 'product',
            'cloud_shoping_result.max_number' => 'csr_max_number',
            'cloud_shoping_result.periods_number' => 'csr_periods_number',
            'cloud_shoping_result.winning_cloud_code' => 'csr_winning_cloud_code',
            'cloud_shoping_result.winning_user_id' => 'csr_winning_user_id',
            'cloud_shoping_result.begin_time' => 'csr_begin_time',
        );
        $res = M('team')->where($where)->limit($limit)->order('cloud_shoping_result.begin_time desc')->join('inner join cloud_shoping_result on cloud_shoping_result.team_id=team.id')->field($field)->select();
        if ($res) {
            $team_ids = $pn_s = $user_ids = array();
            foreach ($res as &$v) {
                if (isset($v['image']) && ($v['image'])) {
                    $v['image'] = getImagePath($v['image']);
                }
                if (isset($v['csr_winning_user_id']) && trim($v['csr_winning_user_id'])) {
                    $user_ids[$v['csr_winning_user_id']] = $v['csr_winning_user_id'];
                }
                if (isset($v['id']) && trim($v['id'])) {
                    $team_ids[$v['id']] = $v['id'];
                }
                if (isset($v['csr_periods_number']) && trim($v['csr_periods_number'])) {
                    $pn_s[$v['csr_periods_number']] = $v['csr_periods_number'];
                }
                $v['progress'] = 0;
                if (isset($v['now_number']) && trim($v['now_number']) && isset($v['max_number']) && $v['max_number'] > 0) {
                    $v['progress'] = ($v['now_number'] / $v['max_number']) * 100;
                }
            }

            unset($v);
            $user_info_res = array();
            if ($user_ids) {
                $user_info_res = M('user')->where(array('id' => array('in', array_keys($user_ids))))->getField('id,username', true);
            }

            $pay_count_info = array();
            if ($user_ids && $team_ids && $pn_s) {
                $pay_count_where = array(
                    'team_id' => array('in', array_keys($team_ids)),
                    'periods_number' => array('in', array_keys($pn_s)),
                    'user_id' => array('in', array_keys($user_ids)),
                );
                $cloud_shoping_code = M('cloud_shoping_code');
                $pay_count_res = $cloud_shoping_code->where($pay_count_where)->field('user_id,team_id,periods_number,count(id) as pay_count')->group('user_id,team_id,periods_number')->select();
                if ($pay_count_res) {
                    foreach ($pay_count_res as &$v) {
                        $_key = "{$v['user_id']}_{$v['team_id']}_{$v['periods_number']}";
                        if (!isset($pay_count_info[$_key])) {
                            $pay_count_info[$_key] = 0;
                        }

                        if (isset($v['pay_count']) && trim($v['pay_count'])) {
                            $pay_count_info[$_key] = $v['pay_count'];
                        }
                    }
                    unset($v);
                }
            }

            foreach ($res as &$v) {
                $v['csr_winning_user_username'] = '';
                if (isset($v['csr_winning_user_id']) && trim($v['csr_winning_user_id'])) {
                    $v['csr_winning_user_username'] = ternary($user_info_res[$v['csr_winning_user_id']], '');
                }
                $v['csr_winning_user_username_hide'] = '';
                if (checkMobile($v['csr_winning_user_username'])) {
                    $v['csr_winning_user_username_hide'] = substr($v['csr_winning_user_username'], 0, 4) . '****' . substr($v['csr_winning_user_username'], -4, 4);
                } else {
                    $v['csr_winning_user_username_hide'] = cutStr($v['csr_winning_user_username'], 1, 0, 0) . '**';
                }
                $_key = "{$v['csr_winning_user_id']}_{$v['id']}_{$v['csr_periods_number']}";
                $v['pay_count'] = ternary($pay_count_info[$_key], '0');
            }
            unset($v);
        }
        $data = array(
            'count' => $count,
            'page' => $page_html,
            'list' => $res,
            'winning_announce' => $this->_winning_announce(),
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 云购详情
     */
    public function cloud_view() {

        $tid = I('get.tid', 0, 'intval'); // 团单id
        $pn = I('get.pn', 0, 'intval'); // 期数

        if (!trim($tid)) {
            redirect(U('Cloud/index'));
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
            'summary'
        );
        $teamRes = M('team')->where($where)->field($field)->find();
        if (!$teamRes) {
            redirect(U('Cloud/index'));
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
            $teamRes['pay_count'] = $cloud_shoping_code->where($where)->count();
        }

        // 进度条
        if (isset($teamRes['status']) && $teamRes['status'] == 0) {
            $teamRes['progress'] = 0;
            if (isset($teamRes['now_number']) && trim($teamRes['now_number']) && isset($teamRes['max_number']) && $teamRes['max_number'] > 0) {
                $teamRes['progress'] = ($teamRes['now_number'] / $teamRes['max_number']) * 100;
            }
        }

        // 获取购买记录
        $teamRes['order_record'] = array();
        $where = array(
            'state' => 'pay',
            'rstate' => 'normal',
            'team_id' => $tid,
            'now_periods_number' => $pn,
            'pay_time' => array('gt', 0),
        );
        $field = array('id', 'user_id', 'quantity', 'pay_time', 'user_buy_ip', 'user_buy_city_name', 'microtime');
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
                if (isset($v['microtime']) && $v['microtime'] > 0) {
                    $v['time'] = microtime_type($v['microtime'], 'H:i:s.');
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
        $data = array(
            'team' => $teamRes
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 一元众筹 图文详情
     */
    public function moreDetail() {
        $this->_checkblank('id');
        $tid = I('get.id', 0, 'intval');
        $team = D('Team');
        // 查询订单详情
        $teamRes = $team->info($tid, 'id,partner_id,end_time,notice,summary,detail');

        if (!$teamRes) {
            redirect(U('Cloud/index'));
        }
        $teamRes['address'] = M('Partner')->where('id=' . $teamRes['partner_id'])->getField('address');
        $teamRes['remain'] = $teamRes['end_time'] - time();
        $this->assign('team', $teamRes);
        $this->display();
    }

    /**
     * 中奖公告 获取
     */
    private function _winning_announce() {

        $where = array(
            'status' => array('gt', 0)
        );
        $cloud_shoping_result = M('cloud_shoping_result');
        $cloud_shoping_winning_res = $cloud_shoping_result->where($where)->field('team_id,winning_user_id')->order('begin_time desc')->find();

        if (!$cloud_shoping_winning_res) {
            return array();
        }

        if (isset($cloud_shoping_winning_res['team_id']) && trim($cloud_shoping_winning_res['team_id'])) {
            $cloud_shoping_winning_res['team_title'] = M('team')->where(array('id' => $cloud_shoping_winning_res['team_id']))->getField('title');
        }

        if (isset($cloud_shoping_winning_res['winning_user_id']) && trim($cloud_shoping_winning_res['winning_user_id'])) {
            $cloud_shoping_winning_res['user_hide_username'] = $cloud_shoping_winning_res['user_username'] = M('user')->where(array('id' => $cloud_shoping_winning_res['winning_user_id']))->getField('username');
            if (checkMobile($cloud_shoping_winning_res['user_username'])) {
                $cloud_shoping_winning_res['user_hide_username'] = substr($cloud_shoping_winning_res['user_username'], 0, 4) . '****' . substr($cloud_shoping_winning_res['user_username'], -4, 4);
            }
        }
        
        return $cloud_shoping_winning_res;
    }

}
