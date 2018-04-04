<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/21
 * Time: 15:58
 */

namespace MerchantApi\Controller;

use MerchantApi\Controller\CommonController;

/**
 * 消费统计接口
 * Class TeamController
 * @package MerchantApi\Controller
 */
class TeamController extends CommonController {

    /**
     * @var bool 是否验证uid
     */
    protected $checkUser = true;

    /**
     * 获取消费统计
     */
    public function teamPayCount() {
        $Model = D('Team');
        $last_id = I('get.last_id','','trim');
        $nowTime = time();
        $type = I('get.type', 0, 'intval');
        if($type){
            return $this->teamPayCountNew($type,$last_id);
        }
        $where = array(
            'coupon.partner_id'=>$this->uid,
            'team.team_type'=>array('neq','goods'),
            'team.city_id' => M('partner')->where(array('id'=>$this->uid))->getField('city_id')
        );
        //1是在线项目
        if ($type == 1) {
            $where['team.begin_time'] = array('elt', $nowTime);
            $where['team.end_time'] = array('egt', $nowTime);
        }
        //1是下线项目
        if ($type == 2) {
            $where['team.end_time'] = array('lt', $nowTime);
        }
        if($last_id){
            $where['team.id'] = array('lt',$last_id);
        }
        
        $data = $Model->getPayCount($where, 'team.now_number,team.id desc ', 20);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'merchantapi');
            $this->outPut(null, 1002);
        }
        $this->outPut($data, 0);
    }

    /***
     * 2016.7.9新版用
     */
    public function teamPayCountNew($type,$last_id) {
        $Model = D('Team');
        $last_id = $last_id;
        $nowTime = time();
        $type =$type;
        $where = array(
            'partner_id'=>$this->uid,
            'team.team_type'=>array('neq','goods'),
            'team.city_id' => M('partner')->where(array('id'=>$this->uid))->getField('city_id')
        );
        //1是在线项目
        if ($type == 1) {
            $where['team.begin_time'] = array('elt', $nowTime);
            $where['team.end_time'] = array('egt', $nowTime);
        }
        //1是下线项目
        if ($type == 2) {
            $where['team.end_time'] = array('lt', $nowTime);
        }
        if($last_id){
            $where['team.id'] = array('lt',$last_id);
        }

        $data = $Model->getPayCountNew($where, 'team.now_number,team.id desc ', 20);
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'merchantapi');
            $this->outPut(null, 1002);
        }
        $this->outPut($data, 0);
    }
    /**
     * 每日消费统计
     */
    public function dayPayCount() {
        $team_id = I('get.team_id', 0, 'intval');
        $last_id = I('get.last_id','','trim');
        $spay_time = I('get.spay_time', '', 'intval');
        $epay_time = I('get.epay_time', '', 'intval');
        if (!$team_id){
            $this->outPut(null, 1001);
        }

        $where = $this->setPage('consume_time');
        $limit = $this->reqnum;
        if($spay_time && $epay_time){            
            $where = array(                
                'consume_time' => array('between',array($spay_time,$epay_time)),                     
            );            
        } 
        $where['partner_id'] = $this->uid;
        $where['team_id'] = $team_id;
        $where['consume'] = 'Y';
        if($last_id){
            $where['id'] = array('lt',$last_id);
        }
        $field="id,FROM_UNIXTIME(consume_time,'%Y-%m-%d') as consume_date,max(consume_time) as consume_time,count(id) as num";
        $data=M('coupon')->field($field)->where($where)->order('consume_time desc')->group('consume_date')->limit($limit)->select();
        if($data){
            foreach ($data as &$v){
                $v['consume_time']=strtotime($v['consume_date']);
            }
        }
        if ($data === false) {
            $this->outPut(null, 1002);
        }
        $this->outPut($data, 0);
    }

    /**
     * 每日消费青团券明细
     */
    public function dayPayDetail() {
        $team_id = I('get.team_id', 0, 'intval');
        $time = I('get.time', '', 'strval');
        $last_id = I('get.lastid','','trim');
        if (!$time || !$team_id){
            $this->outPut(null, 1001);
        }
        $begin_time = strtotime($time);
        $end_time = strtotime('+1 day '.$time);
        $Model = D('Coupon');
        $where = array(
            'team_id'=>$team_id,
            'consume'=>'Y',
            'partner_id'=>$this->uid,
            'consume_time'=>array(array('egt',$begin_time),array('lt',$end_time)),
        ); 
        $limit = 50;
        
        if($last_id){
            $where['_string'] = "consume_time < {$last_id}";
        }
        
        $field="id,user_id,consume_time";
        $data=M('coupon')->field($field)->where($where)->order('consume_time desc')->limit($limit)->select();
        if($data){
            $user_ids = array();
            foreach($data as &$v){
                if(isset($v['user_id']) && trim($v['user_id'])){
                    $user_ids[$v['user_id']] = $v['user_id'];
                }
                $v['consume_date'] = date('H:i:s',$v['consume_time']);
            }
            unset($v);
            $user_info = array();
            if($user_ids){
                $user_info = M('User')->where(array('id'=>array('in',array_keys($user_ids))))->getField('id,username',true);
            }
            foreach($data as &$v){
                $v['username'] = ternary($user_info[$v['user_id']],'');
                if (checkMobile($v['username'])) {
                    $v['username'] = substr($v['username'], 0, 3) . '*****' . substr($v['username'], -3, 4);
                } 
            }
            unset($v);
        }
        
        
        if ($data === false) {
            $this->_writeDBErrorLog($data, $Model, 'merchantapi');
            $this->outPut(null, 1002);
        }
        $this->outPut($data, 0);
    }

   /**
     * 团单详情
     */
    public function teamDetail() {

        // 参数接收
        $this->_checkblank('teamId');
        $teamId = I('get.teamId', 0, 'intval');
        
        // 去ots搜索
        $team = D('Team');
        $tableName = 'team';
        $res = $this->_getRowDataToOTS($tableName, array('id' => $teamId));
        if (!$res) {
            $res = $team->getTeamDetail($teamId);
        }

        if (!$res) {
            $this->outPut(null, 3001);
        }

        // 处理数据
        $res = $team->dealTeamData($res, true);

        $this->outPut($res, 0);
    }

}
