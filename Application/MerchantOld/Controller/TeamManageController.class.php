<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class TeamManageController extends CommonController {

    public function index() {

        // 接收参数
        $type = I('get.type', 1, 'intval');

        // 查询条件
        $nowTime = time();
        $where = array(
            'partner_id' => $this->partner_id,
            'old_team_id' => 0,
            'end_time' => array('lt',$nowTime)
        );
        if ($type == 1) {
            $where['begin_time'] = array('elt', $nowTime);
            $where['end_time'] = array('egt', $nowTime);
        }
        if ($type == 3){
            $where = array(
                'partner_id' => $this->partner_id,
                'old_team_id' => array('neq',0),
                'activities_id' => array('neq',0),
            );
        }
        // 查询数据
        $team = M('team');
        $count = $team->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array('title', 'begin_time', 'end_time', 'id','activities_id');
        $list = $team->where($where)->field($field)->order('id desc')->limit($page->firstRow, $page->listRows)->select();
        
        if($list && $type == 3){
            $activities_ids = array();
            foreach($list as &$v){
                if(isset($v['activities_id']) && trim($v['activities_id'])){
                    $activities_ids[$v['activities_id']]=$v['activities_id'];
                }
            }
            unset($v);
            $activities_info = array();
            if($activities_ids){
                $activities_where = array(
                    'id' => array('in', array_keys($activities_ids)),
                    'type' => 'activities',
                );
                $activities_info = M('admanage')->where($activities_where)->getField('id,textarr as title',true);
            }
            $now_time = time();
            foreach($list as &$v){
                // 活动名称
                $v['activities_name']=  ternary($activities_info[$v['activities_id']], '');
                
                // 团单状态
                $v['status'] = 1;
                $v['status_name'] = '未开始';
                if($v['end_time'] && $v['begin_time'] && $now_time<=$v['end_time'] && $now_time>=$v['begin_time']){
                     $v['status'] = 2;
                     $v['status_name'] = '进行中';
                     continue;
                }
                if($v['end_time'] && $now_time>$v['end_time']){
                     $v['status'] = 3;
                     $v['status_name'] = '已结束';
                     continue;
                }
            }
            unset($v);
            
            
        }

        // 获取业务员信息
        $dbUser = D('Partner')->getDBUser($this->partner_id);
              
        $data = array(
            'count' => $count,
            'page' => $page->show(),
            'type' => $type,
            'list' => $list,
            'dbUser' => $dbUser,
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 自动延期
     */
    public function delay() {

        $id = I('get.id', 0, 'intval');
        $team = M('team');
        if ($id) {
            // 弹出延期信息页面
            $where = array(
                'id' => $id,
                'partner_id' => $this->partner_id,
            );
            $field = 'id,title,begin_time,end_time,expire_time';
            $data = $team->field($field)->where($where)->find();
            $this->assign('team', $data);
            $this->display();
            exit;
        }

        // 修改延期的单子
        $id = I('post.id', 0, 'intval');
        $end_time = I('post.end_time', '', 'strval');
        $expire_time = I('post.expire_time', '', 'strval');
        if (!trim($id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '延期的团单的id不能为空'));
        }
        if (!trim($end_time) && !trim($expire_time)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请填写自动延期时间'));
        }

        $where = array(
            'id' => $id,
            'partner_id' => $this->partner_id,
        );
        $field = 'id,title,begin_time,end_time,expire_time';
        $teamRes = $team->field($field)->where($where)->find();
        if (!$teamRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '延期的团单不存在'));
        }
        if (!isset($teamRes['end_time']) || $teamRes['end_time'] < strtotime(date('Y-m-d'))) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该产品已经下线，无法自动延单'));
        }

        $data = array();
        if (trim($end_time)) {
            $end_time = strtotime($end_time);
            if ($end_time < $teamRes['end_time']) {
                $this->ajaxReturn(array('code' => -1, 'error' => '结束时间必须大于' . date('Y-m-d', $teamRes['end_time'])));
            }
            $data['end_time'] = $end_time;
        }
        if (trim($expire_time)) {
            $expire_time = strtotime($expire_time);
            if ($expire_time < $teamRes['expire_time']) {
                $this->ajaxReturn(array('code' => -1, 'error' => '结束时间必须大于' . date('Y-m-d', $teamRes['expire_time'])));
            }
            if (!$end_time) {
                $end_time = $teamRes['end_time'];
            }
            if ($expire_time < $end_time) {
                $this->ajaxReturn(array('code' => -1, 'error' => '结束时间必须大于' . date('Y-m-d', $end_time)));
            }
            $data['expire_time'] = $expire_time;
        }

        // 修改数据库
        $res = $team->where($where)->save($data);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '延期失败'));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 特殊设置
     */
    public function specialSet() {
        $id = I('get.id', 0, 'intval');
        $team = M('team');
        if ($id) {
            // 弹出延期信息页面
            $where = array(
                'id' => $id,
                'partner_id' => $this->partner_id,
            );
            $field = 'id,end_time,seo_description';
            $data = $team->field($field)->where($where)->find();
            $this->assign('team', $data);
            $this->display();
            exit;
        }

        // 特殊设置修改
        $id = I('post.id', 0, 'intval');
        $remark = I('post.remark', '', 'strip_tags');
        if (!trim($id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '设置的团单的id不能为空'));
        }
        if (!trim($remark)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '备注不能为空！'));
        }

        $where = array(
            'id' => $id,
            'partner_id' => $this->partner_id,
        );
        $field = 'id,end_time,seo_description';
        $teamRes = $team->field($field)->where($where)->find();
        if (!$teamRes) {
            $this->ajaxReturn(array('code' => -1, 'error' => '修改的团单不存在'));
        }
        
        if (trim($teamRes['seo_description']) == trim($remark)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '备注不能和原来备注相同'));
        }
        if (!isset($teamRes['end_time']) || $teamRes['end_time'] < strtotime(date('Y-m-d'))) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该产品已经下线，无法修改备注'));
        }
        $res = $team->where($where)->save(array('seo_description'=>$remark));
        if(!$res){
             $this->ajaxReturn(array('code' => -1, 'error' => '修改失败！'));
        }
        $this->ajaxReturn(array('code' => 0));
    }
    
    /**
     *  参加活动操作
     */
    public function participate_activities(){
        $tid = I('get.tid', 0, 'intval');
        $team = M('team');
        if ($tid) {
            // 弹出延期信息页面
            $where = array(
                'id' => $tid,
                'partner_id' => $this->partner_id,
            );
            $field = 'id,activities_id,lottery_price,team_price,ucaii_price';
            $team_info = $team->field($field)->where($where)->find();
            
            // 获取活动
            $where = array(
                'is_voluntary_in'=>'Y',
                'end_time' => array('gt', time()),
                'type'=>'activities',
                'cityid'=>array('in',array(957,  ternary($this->partner['city_id'], '')))
            );
            $activities_res =D('Admanage')->getActivitiesListByWhere($where);
            $data = array(
                'activities_res'=>$activities_res,
                'team_info'=>$team_info,
            );
            $this->assign($data);
            $this->display();
            exit;
        }
        
        // 接受参数
        $tid = I('post.tid','','trim');
        $activities_id = I('post.activities_id','','trim');
        $discount_price = I('post.discount_price','','trim');
        $max_number = I('post.max_number','','trim');
        
        if(!$tid){
            $this->ajaxReturn(array('code' => -1, 'error' => '参加活动的团单id不能为空！'));
        }
        if(!$activities_id){
            $this->ajaxReturn(array('code' => -1, 'error' => '活动不能为空！'));
        }
        if(!$discount_price || floatval($discount_price) <= 0){
            $this->ajaxReturn(array('code' => -1, 'error' => '优惠价不能为空且必须大于0！'));
        }
        if(!$max_number){
            $max_number = 0;
        }
        
        //$activities_res =D('Admanage')->isExistActivities(array('id'=>$activities_id));
        $activities_res =D('Admanage')->where(array('id'=>$activities_id,'type'=>'activities'))->field('id,begin_time,end_time,textarr as title')->find();
        if(!$activities_res){
            $this->ajaxReturn(array('code' => -1, 'error' => '你所选的活动不存在！'));
        }
        
        // 判断该团单是否已经参加过该活动
        $where = array(
            'old_team_id' => $tid,
            'partner_id' => $this->partner_id,
            'activities_id' => $activities_id,
        );
        $is_exist_team_count = M('team')->where($where)->count();
        if($is_exist_team_count || $is_exist_team_count>0){
            $this->ajaxReturn(array('code' => -1, 'error' => "该项目已经参加过[{$activities_res['title']}]活动，不能重复参加"));
        }
        
        $team_info = M('team')->where(array('id'=>$tid))->find();
        if(!$team_info){
            $this->ajaxReturn(array('code' => -1, 'error' => '你所修改的团单不存在'));
        }
        if(isset($team_info['ucaii_price']) && $team_info['ucaii_price']<=$discount_price){
            $this->ajaxReturn(array('code' => -1, 'error' => '优惠价必须小于:'.$team_info['ucaii_price']));
        }
        
        
        // 整理需要参加活动的团单的数据
        $team_data = array_filter($team_info,function($v){return $v!==null;});
        if(isset($activities_res['begin_time']) && trim($activities_res['begin_time'])){
            $team_data['begin_time'] = ternary($activities_res['begin_time'], $team_info['begin_time']);
        }
        if(isset($activities_res['end_time']) && trim($activities_res['end_time'])){
            $team_data['end_time'] = ternary($activities_res['end_time'], $team_info['end_time']);
        }
        $team_data['activities_id'] = $activities_id;
        $team_data['max_number'] = $max_number;
        $team_data['view_count_day'] = $team_data['view_count'] = $team_data['pre_number'] = $team_data['now_number'] = 0;
        $team_data['old_team_id'] = $team_data['id'];
        $team_data['ucaii_price'] = sprintf("%.2f", $team_data['ucaii_price']-$discount_price);
        $team_data['team_price'] = sprintf("%.2f", $team_data['team_price']-$discount_price);
        unset($team_data['id']);
        
        //  添加新团单
        $add_team_id = $team->add($team_data);
        if(!$add_team_id){
            $this->ajaxReturn(array('code' => -1, 'error' => '参加活动失败！'));
        }
        $this->ajaxReturn(array('code' => 0));
        
    }
    
    /**
        * 编辑活动单子
        */
    public function edit_activities_team(){
        $tid = I('get.tid', 0, 'intval');
        $team = M('team');
        if ($tid) {
            // 弹出延期信息页面
            $where = array(
                'id' => $tid,
                'partner_id' => $this->partner_id,
            );
            $field = 'id,activities_id,max_number,team_price,ucaii_price,begin_time,end_time,old_team_id';
            $team_info = $team->field($field)->where($where)->find();
            
            if(isset($team_info['activities_id']) && trim($team_info['activities_id'])){
                $where = array(
                    'type'=>'activities',
                    'id'=>$team_info['activities_id']
                );
                $team_info['activities_name'] = D('Admanage')->where($where)->getField('textarr');
            }
            if(isset($team_info['old_team_id']) && trim($team_info['old_team_id'])){
                $old_team_data = $team->where(array('id'=>intval($team_info['old_team_id'])))->field('ucaii_price as old_ucaii_price,team_price as old_team_price')->find(); 
                $team_info['discount_price'] =   sprintf("%.2f", $old_team_data['old_ucaii_price']-$team_info['ucaii_price']);
                $team_info = array_merge($team_info,$old_team_data);
            }

            $data = array(
                'team_info'=>$team_info,
            );
            $this->assign($data);
            $this->display();
            exit;
        }
        
        // 接受参数
        $tid = I('post.tid','','trim');
        $discount_price = I('post.discount_price','','trim');
        $max_number = I('post.max_number','','trim');
        $begin_time = I('post.begin_time','','trim');
        $end_time = I('post.end_time','','trim');
        
        if(!$tid){
            $this->ajaxReturn(array('code' => -1, 'error' => '参加活动的团单id不能为空！'));
        }
 
        if(!$discount_price || floatval($discount_price) <= 0){
            $this->ajaxReturn(array('code' => -1, 'error' => '优惠价不能为空且必须大于0！'));
        }
        if(!$max_number){
            $max_number = 0;
        }
        
        $team_info = M('team')->where(array('id'=>$tid))->field('old_team_id,activities_id')->find();
        if(!$team_info){
            $this->ajaxReturn(array('code' => -1, 'error' => '你所修改的团单不存在'));
        }
        
        $old_team_data = M('team')->where(array('id'=>$team_info['old_team_id']))->field('ucaii_price,team_price,begin_time,end_time')->find();
        if(!$old_team_data){
            $this->ajaxReturn(array('code' => -1, 'error' => '你所修改的活动团单原团单不存在'));
        }
        
        if(isset($old_team_data['ucaii_price']) && $old_team_data['ucaii_price']<=$discount_price){
            $this->ajaxReturn(array('code' => -1, 'error' => '优惠价必须小于:'.$team_info['ucaii_price']));
        }
        $activities_res = D('Admanage')->where(array('id'=>$team_info['activities_id']))->field('begin_time')->find();
        if(isset($activities_res['begin_time']) && $activities_res['begin_time']<=time()){
            $this->ajaxReturn(array('code' => -1, 'error' => '该活动团单不在允许修改的时间内'));
        }
        
        $team_data = array(
            'max_number'=>$max_number,
        );
        $begin_time = strtotime($begin_time);
        if($begin_time){
            $team_data['begin_time'] = $begin_time;
        }
        $end_time = strtotime($end_time);
        if($end_time){
            $team_data['end_time'] = $end_time;
        }
        
        $team_data['ucaii_price'] = sprintf("%.2f", $old_team_data['ucaii_price']-$discount_price);
        $team_data['team_price'] = sprintf("%.2f", $old_team_data['team_price']-$discount_price);
        
        //  添加新团单
        $res = $team->where(array('id'=>$tid))->save($team_data);
        if($res===false){
            $this->ajaxReturn(array('code' => -1, 'error' => '活动团单修改失败！'));
        }
        $this->ajaxReturn(array('code' => 0));
    }
    
    /**
     * 删除活动团单
     */
    public function delete_activities_team(){
        $tid = I('get.tid', 0, 'intval');
        if(!$tid){
            $this->ajaxReturn(array('code' => -1, 'error' => '删除团单id不能为空！'));
        }
        
        $team_data = M('team')->where(array('id'=>$tid))->field('begin_time')->find();
        if(!$team_data){
            $this->ajaxReturn(array('code' => -1, 'error' => '你所删除的团单不存在'));
        }
        
        if(isset($team_data['begin_time']) && $team_data['begin_time']<=time()){
            $this->ajaxReturn(array('code' => -1, 'error' => '该活动团单不在允许删除的时间内'));
        }
        
        $res = M('team')->where(array('id'=>$tid))->delete();
        if(!$res){
            $this->ajaxReturn(array('code' => -1, 'error' => '活动团单删除失败！'));
        }
        $this->ajaxReturn(array('code' => 0));
    }

}
