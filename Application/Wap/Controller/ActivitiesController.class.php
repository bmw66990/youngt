<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/18
 * Time: 13:48
 */

namespace Wap\Controller;

/**
 * 用户操作控制器
 * Class UserController
 * @package Wap\Controller
 */
class ActivitiesController extends CommonController {

    protected $checkCity = true;
    protected $city_id = 0;

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
        $this->city_id = $this->_getCityId();
    }

    /**
     * 活动展示
     */
    public function index() {
        $activities_id = I('get.activities_id', '', 'trim');
        $plat = I('get.plat', '', 'trim');
        $city_id = I('get.city_id', '', 'trim');
        $group_id = I('get.group_id', 'all', 'trim');
        $team = D('Team');

        // 获取查询条件
        
        $list = array();
        if (trim($activities_id) && $this->city_id) {
            list($where, $sort) = $team->getMysqlWhere(array());
            unset($where['_string']);
            $where['city_id'] = array('in', array($this->city_id, 957));
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
            'city_id'=>$city_id,
            'group_id'=>$group_id,
            'list'=>$list,
        );
        $this->assign($data);
        $this->display();
    }
    public function ces() {
        $this->city_id=1;
        $activities_id = I('get.activities', '', 'trim');
        $plat = I('get.plat', '', 'trim');
        $group_id = I('get.group_id', 'all', 'trim');
        $team = D('Team');

        // 获取查询条件

        $list = array();
        if (trim($activities_id) && $this->city_id) {
            list($where, $sort) = $team->getMysqlWhere(array());
            unset($where['_string']);
            $where['city_id'] = array('in', array($this->city_id, 957));
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
            'group_id'=>$group_id,
            'list'=>$list,
        );
        $this->assign($data);
        $this->display('index');
    }

}
