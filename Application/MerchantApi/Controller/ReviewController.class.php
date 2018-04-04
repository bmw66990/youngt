<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-04-20
 * Time: 15:14
 */
namespace MerchantApi\Controller;

/**
 * 商家评论接口
 * Class ReviewController
 * @package MerchantApi\Controller
 */
class ReviewController extends CommonController {

    /**
     * 团单列表
     */
    public function getTeamList() {
        
        $last_id = I('get.last_id');
        $var = I('get.var',0, 'intval');

        if($var){
            return $this->getTeamListNew($last_id);
        }
        $where = array(
            'partner_id'=>$this->uid,
        );
        if($last_id){
            $where['id']=array('lt',$last_id);
        }
        $model = D('Team');
        $team  = $model->getList($where, 'id desc', $this->reqnum, 'id,title,market_price,end_time,image');
        if($team) {
            
            $team_ids = array();
            foreach($team as &$v){
                if(isset($v['id']) && trim($v['id'])){
                    $team_ids[$v['id']] = $v['id'];
                }
                $v['image'] = getImagePath($v['image']);
            }
            unset($v);
            $comment_info = array();
            if($team_ids){
                $comment_where = array(
                    'team_id' => array('in',array_keys($team_ids)),
                    'is_comment' => 'Y',
                    'comment_display' => 'Y',
                    '_string'=>"comment_num IS NOT NULL",
                );
                $comment_info = M('Comment')->where($comment_where)->group('team_id')->getField('team_id,count(id) count_id,avg(comment_num) avg_num',true);
            }            
            foreach($team as &$v){               
               $v['count'] = ternary($comment_info[$v['id']]['count_id'], 0);
               $v['num'] = ternary($comment_info[$v['id']]['avg_num'], 0);
            }            
        } 
        $this->outPut($team, 0);
    }

    /**
     * 团单列表
     */
    public function getTeamListNew($last_id) {
        
        $last_id = $last_id;
		
        $where = array(
            'partner_id'=>$this->uid,
        );
        if($last_id){
            $where['id']=array('lt',$last_id);
        }
        $model = D('Team');
        $team  = $model->getList($where, 'id desc', $this->reqnum, 'id,title,product,market_price,begin_time,image');
	
        $mydt = M('team');
        $avgNumcount = $mydt->where('partner_id='.$this->uid)->count('id');
        $teamid = $mydt->field('id,title')->where('partner_id='.$this->uid)->order('id desc')->select();
        $teamids = array();
        foreach($teamid as &$v){            
            $teamids[$v['id']] = $v['id'];             
        }        
        $comment_wh = array(
            'team_id' => array('in',$teamids),
            'is_comment' => 'Y',
            'comment_display' => 'Y',
            '_string'=>"comment_num IS NOT NULL",
        );        
        $comment = M('Comment')->where($comment_wh)->group('team_id')->getField('team_id,count(id) count_id,avg(comment_num) avg_num',true);
        $sum = 0;
        foreach($teamid as &$m){  
           $m['num'] = ternary($comment[$m['id']]['avg_num'], 0);
           $sum += $m['num'];
        }

        if($team) {
            
            $team_ids = array();
            foreach($team as &$v){
                if(isset($v['id']) && trim($v['id'])){
                    $team_ids[$v['id']] = $v['id'];
                }
                $v['image'] = getImagePath($v['image']);
                $v['end_time'] = $v['begin_time'];
            }
            unset($v);
            $comment_info = array();
            if($team_ids){
                $comment_where = array(
                    'team_id' => array('in',array_keys($team_ids)),
                    'is_comment' => 'Y',
                    'comment_display' => 'Y',
                    '_string'=>"comment_num IS NOT NULL",
                );
                $comment_info = M('Comment')->where($comment_where)->group('team_id')->getField('team_id,count(id) count_id,avg(comment_num) avg_num',true);
            }
            
            foreach($team as &$v){
                $v['title'] = $v['product'];
                $v['count'] = ternary($comment_info[$v['id']]['count_id'], 0);
                $v['num'] = round(ternary($comment_info[$v['id']]['avg_num'], 0), 1);
            }
            
        } 

        $avgNum = number_format($sum/$avgNumcount);
        $data = array(
            'avgNum'=>$avgNum,
            'list' => $team,
        );

        $this->outPut($data, 0);
    }

    /***
     * 新版商家
     */
    public function getTeamCountList() {
        $comment_count=array();
        $last_id = I('get.last_id','','trim');
        $where = array(
            'partner_id'=>$this->uid,
        );
        if($last_id){
            $where['id']=array('lt',$last_id);
        }else{
            //获取总的评价总值已经各个值统计和start
            $model               = M('team');
            $team                = $model->field('id')->where($where)->select();
            if($team){
                $team_ids = array();
                foreach($team as &$v){
                    if(isset($v['id']) && trim($v['id'])){
                        $team_ids[$v['id']] = $v['id'];
                    }
                }
                unset($v);
                $comment_info = array();
                if($team_ids){
                    $comment_where = array(
                        'team_id' => array('in',array_keys($team_ids)),
                        'is_comment' => 'Y',
                        'comment_display' => 'Y',
                        '_string'=>"comment_num IS NOT NULL",
                    );
                    $comment_info = M('Comment')->where($comment_where)->group('comment_num')->field('count(id) count_id,avg(comment_num) avg_num')->select();
                    $comment_tongj=array();
                    foreach($comment_info as &$v){
                        if(intval($v['avg_num'])>3){
                            $coutid4 +=$v['count_id'];
                            $avgnum4 +=$v['avg_num']*$v['count_id'];
                        }
                        if(intval($v['avg_num'])==3){
                            $coutid3 +=$v['count_id'];
                            $avgnum3 +=$v['avg_num']*$v['count_id'];
                        }
                        if(intval($v['avg_num'])<3){
                            $coutid2 +=$v['count_id'];
                            $avgnum2 +=$v['avg_num']*$v['count_id'];
                        }

                    }
                    unset($v);
                    $comment_tongj['3']=array(
                        'coutid'=>$coutid4,
                        'avgnum'=>$avgnum4,
                    );
                    $comment_tongj['2']=array(
                        'coutid'=>$coutid3,
                        'avgnum'=>$avgnum3,
                    );
                    $comment_tongj['1']=array(
                        'coutid'=>$coutid2,
                        'avgnum'=>$avgnum2,
                    );
                    $comment_tongj['cout']=$coutid4+$coutid3+$coutid2;
                    $avg_Num=($avgnum4+$avgnum3+$avgnum2)/($coutid4+$coutid3+$coutid2);
                    $avg_Num=isset($avg_Num) && trim($avg_Num) ? number_format($avg_Num, 1) : '0.0';
                    $comment_count['avg_list']=$comment_tongj;
                    $comment_count['avg_Num']=$avg_Num;
                }
            }
             //获取总的评价总值已经各个值统计和end
        }
        $data['comment_count']=$comment_count;
        //获取团单对于及评论
        $model               = D('Team');
        $team                = $model->getList($where, 'id desc', $this->reqnum, 'id,title');
        if($team) {
            $team_ids = array();
            foreach($team as &$v){
                if(isset($v['id']) && trim($v['id'])){
                    $team_ids[$v['id']] = $v['id'];
                }
            }
            unset($v);
            if($team_ids){
                $comment_where = array(
                    'team_id' => array('in',array_keys($team_ids)),
                    'is_comment' => 'Y',
                    'comment_display' => 'Y',
                    '_string'=>"comment_num IS NOT NULL",
                );
                $comment_info = M('Comment')->where($comment_where)->field('user_id,team_id,id,order_id,content,partner_content,create_time,comment_num')->order('create_time')->group('team_id')->select();
                $user_ids = array();
                foreach($comment_info as &$vv){
                    $user_ids[$vv['user_id']] = $vv['user_id'];
                    if($vv['partner_content']==''){
                        $vv['reply']='Y';
                    }else{
                        $vv['reply']='N';
                    }
                }
                unset($vv);
                if ($user_ids) {
                    $user_info_res = M('user')->where(array('id' => array('in', $user_ids)))->index('id')->field('id,username,avatar')->select();
                }
                $user_comment = array();
                foreach($comment_info as &$v){
                    $v['username'] = ternary($user_info_res[$v['user_id']]['username'], '');
                    // 用户头像
                    $v['user_image'] = '';
                    if (isset($user_info_res[$v['user_id']]['avatar']) && trim($user_info_res[$v['user_id']]['avatar'])) {
                        $v['user_image'] = getImagePath($user_info_res[$v['user_id']]['avatar']);
                    }
                    $user_comment[$v['team_id']]=$v;
                }
                unset($v);
            }
            foreach($team as &$v){
                $v['comment_info']=$user_comment[$v['id']];
            }
            unset($v);unset($vv);
            //$team['comment_info']=$comment_info;
        }
        $data['list']=$team;

        //获取团单对于及评论结束
        $this->outPut($data, 0);
    }
    /**
     * 获取团单评分
     */
    public function getTeamReview() {
        $this->_checkblank('team_id');
        $id    = I('get.team_id', 0, 'intval');
        $limit    = I('get.limit', 0, 'intval');
        $state    = I('get.state', 0, 'intval');
        $last_id    = I('get.last_id', 0, 'intval');
        $end_id    = I('get.end_id', 0, 'intval');
        if(!$limit){
            $limit = $this->reqnum;
        }
        
        $model = D('Comment');
        $where = array(
            'comment.team_id'=>$id,
            'comment.is_comment' => 'Y',
            'comment.comment_display' => 'Y',
        );
        if($last_id && $end_id){
            $order_where = D('Team')->getMysqlSortWhere('comment.create_time', $last_id, 'comment.id', $end_id, '-');
            if ($order_where) {
                if (isset($where['_string']) && trim($where['_string'])) {
                    $order_where = "({$where['_string']}) and $order_where";
                }
                $where['_string'] = $order_where;
            }
        }
        
        switch ($state) {
            case 1:                
                $data = $model->getCommentList($where, 'comment.create_time desc,comment.id desc', $limit,1);
                break;
            case 2:
                $data = $model->getCommentList($where, 'comment.create_time desc,comment.id desc', $limit,2);               
                break;
            case 3:
                $data = $model->getCommentList($where, 'comment.create_time desc,comment.id desc', $limit,3);
                break;            
            default:
                $data = $model->getCommentList($where, 'comment.create_time desc,comment.id desc', $limit,1);
                break;
        }  
        if (isset($data['error'])) {
            $this->outPut(null, -1, null, $data['error']);
        }

        //处理评论列表
        foreach($data['list'] as $key=>$row) {
            if($row['partner_id'] == $this->uid && $row['partner_content'] == '') {
                $data['list'][$key]['reply'] = 'Y';
            } else {
                $data['list'][$key]['reply'] = 'N';
            }
        }

        $this->getHasNext($model, 'id', $data['list'], $where);
        $this->outPut($data, 0);
    }

    /**
     * 回复评论
     */
    public function putReply() {
        $this->_checkblank(array('id', 'content'));
        $id      = I('post.id', 0, 'intval');
        $content = I('post.content');
        //检测订单是否可回复
        $order = D('Order')->isExistOrder($id);
        if(empty($order)) {
            $this->_writeDBErrorLog($order, D('Order'), 'merchantapi');
            $this->outPut('', -1, '', '订单不存在');
        }

        $model = D('Comment');
        $data  = $model->checkIsReply($order, $this->uid);
        if($data === false) {
            $this->_writeDBErrorLog($data, $model, 'merchantapi');
            $this->outPut('', 1002);
        }
        if(isset($data['error'])) {
            $this->outPut('', -1, '', $data['error']);
        }

        $rs = $model->addReply($id, $content);
        if($rs) {
            $this->outPut('回复成功',0);
        } else {
            //发布失败
            $this->_writeDBErrorLog($rs, $model, 'merchantapi');
            $this->outPut('', -1);
        }
    }

    /**
     * 回复评论
     */
    public function UpdateReply() {

        $this->_checkblank(array('id', 'content'));
        $id      = I('post.id', 0, 'intval');
        $content = I('post.content');
        $comment = M('comment');        
        $where=array(
            'id'=>$id,
        );
        $data = array(
            'partner_content'=>$content,
            'comment_time'=>time(),
        );        
        $result = $comment->where($where)->setField($data);     
        if($result) {
            $this->outPut('修改成功',0);
        } else {
            //发布失败
            $this->_writeDBErrorLog($result, $model, 'merchantapi');
            $this->outPut('', -1);
        }

    }
}