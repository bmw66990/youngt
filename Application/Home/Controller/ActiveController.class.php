<?php

/**
 * Created by PhpStorm.
 * User: miertao
 * Date: 2015/10/31
 * Time: 15:00
 */

namespace Home\Controller;

/**
 * Class ActiveController
 * @package Home\Controller
 */
class ActiveController extends CommonController {

    protected $city_info = null;

    public function __construct() {
        parent::__construct();
        $this->city_info = $this->_getCityInfo();
    }

    public function index() {
        $activities_id = I('get.activities_id', '', 'intval');

        $list = array();
        $where = array();
        $team = D('Team');
        list($where, $sort) = $team->getMysqlWhere(array());
        unset($where['_string']);
        $where['team_type'] = array('in', array('normal', 'goods'));
        $where['city_id'] = array('in', array(intval($this->city_info['id']), 957));

        $double_11_time = strtotime('2016-01-16');
        $now_time = time();
        $res = D('Admanage')->info(intval($activities_id));
        $active_name = '活动主会场';
        if(isset($res['textarr']) && trim($res['textarr'])){
            $active_name = $res['textarr'].'主会场';
        }
        $this->assign('title', $active_name);
        if (isset($res['begin_time']) && trim($res['begin_time'])) {
            $double_11_time = $res['begin_time'];
        }
        if ($double_11_time > $now_time) {
            $this->assign('activities_begin_time', $double_11_time);
            $this->assign('activities_now_time', $now_time);
            $this->display();
            exit;
        }

        $data = array(
            'meishi' => array(
                'id' => '255',
                'name' => '美食',
                'list' => array(),
            ),
            'yule' => array(
                'id' => '12',
                'name' => '娱乐',
                'list' => array(),
            ),
            'other' => array(
                'id' => 'other',
                'name' => '其他',
                'list' => array(),
            ),
        );

        if (trim($activities_id)) {

            // 获取查询条件
            $where['activities_id'] = intval($activities_id);
            // 查询团单
            $list = $team->where($where)->field($team->getTeamField())->select();
            $list = $team->dealTeamData($list,false,false);
            if ($list) {
                foreach ($list as $v) {
                    if (isset($v['group_id']) && trim($v['group_id']) == '255') {
                        $data['meishi']['list'][] = $v;
                        continue;
                    }
                    if (isset($v['group_id']) && trim($v['group_id']) == '12') {
                        $data['yule']['list'][] = $v;
                        continue;
                    }
                    
                    $data['other']['list'][] = $v;
                }

                foreach ($data as $k => $v) {
                    if (!isset($v['list']) || !$v['list']) {
                        unset($data[$k]);
                    }
                }
            }
        }
        if (!$list) {
            $where['team_type'] = 'goods';
            if (isset($where['activities_id'])) {
                unset($where['activities_id']);
            }
            $list = $team->where($where)->field($team->getTeamField())->select();
            $list = $team->dealTeamData($list,false,false);
            $data = array(
                'other' => array(
                    'id' => 'other',
                    'name' => '其他',
                    'list' => $list,
                )
            );
        }
        
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 获取活动
     */
    public function getActivitiesList() {
        if (!isset($this->city_info['id']) || !trim($this->city_info['id'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '城市id不能为空！'));
        }

        $admanage = D('Admanage');
        $data = $admanage->getActivitiesList($this->city_info['id'], false);
        if ($data) {
            $team = D('Team');
            list($where, $sort) = $team->getMysqlWhere(array());
            unset($where['_string']);
            $where['city_id'] = array('in', array($this->city_info['id'], 957));
            $where['team_type'] = array('in', array('normal', 'goods'));
            foreach ($data as $k => &$v) {
                
                // 如果不是普通活动 直接返回
                if (!isset($v['href']) || !trim($v['href']) || strpos(strtolower($v['href']), '/activities/') === false) {
                    unset($data[$k]);
                    continue;
                }
                
                $where['activities_id'] = intval($v['id']);
                $team_count = $team->where($where)->count();
                $v['is_show'] = 'Y';
                if (!$team_count || intval($team_count) <= 0) {
                    $v['is_show'] = 'N';
                }
            }
            unset($v);
        }

        if ($data) {
            $data = array_pop(array_reverse($data));
        }
        if (!$data) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该城市没有活动！'));
        }
        if (isset($data['begin_time']) && trim($data['begin_time']) && intval($data['begin_time']) > time()) {
            $data['is_show'] = 'N';
        }
        $this->ajaxReturn(array('code' => 0, 'data' => $data));
    }
    //招商加盟
    public function merchants()
    {
        $this->display();
    }
    //活动
    public function huodong(){

        $activities_id = I('get.activities_id', '', 'trim');
        $plat = I('get.plat', '', 'trim');
        $city_id = I('get.city_id', '', 'trim');
        $group_id = I('get.group_id', 'all', 'trim');
        $team = D('Team');

        // 获取查询条件

        $list = array();
        if (trim($activities_id) && $this->city_info['id']) {
            list($where, $sort) = $team->getMysqlWhere(array());
            unset($where['_string']);
            $where['city_id'] = array('in', array($this->city_info['id'], 957));
            $where['activities_id'] = intval($activities_id);

            if($group_id=='other'){
                $where['group_id'] = array('not in',array(255,12));
            }elseif($group_id !='all' && intval($group_id) > 0){
                $where['group_id'] = intval($group_id);
            }

            // 查询团单
            $list = $team->where($where)->field($team->getTeamField())->order('sort_order desc')->select();
            $list = $team->dealTeamData($list,false,false);
        }

        $data = array(
            'activities_id'=>$activities_id,
            'plat'=>$plat,
            'city_id'=>$this->city_info['id'],
            'group_id'=>$group_id,
            'list'=>$list,
        );
        $this->assign($data);
        $this->display();
    }

}
