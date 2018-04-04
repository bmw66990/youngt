<?php
/**
 * Created by PhpStorm.
 * User: ma
 * Date: 2015-04-07
 * Time: 15:14
 */
namespace NeighborApi\Controller;

/**
 * 用户发送接口
 * Class ReviewController
 * @package NeighborApi\Controller
 */
class MsglistController extends CommonController {

    protected $checkUser = false;
    protected $signCheck = false;
  
     
     // 消息列表
    public function listmymsg() {
            
        $catid = 3;
        $last_id = I('post.last_id');
        $where = array(
            'catid'=> $catid,
        );
        
        if($last_id){
            $where['id']=array('lt',$last_id);
        }  
        $firend  = D('Firend')->getList($where, 'id desc', $this->reqnum, 'id,catid,catname,userid,username,title,content,createtime,hits');
        
        if($firend) {
            $firend_ids = array();
            foreach($firend as &$v){
                if(isset($v['id']) && trim($v['id'])){
                    $firend_ids[$v['id']] = $v['id'];
                }
                if($v['picture']){
                    $v['picture'] = getImagePath($v['picture']);
                }else{
                    $v['picture'] = array();
                }   
            }
            $comment_info = array();
            if($firend_ids){
                $reply_where = array(
                    'catid' => $catid,                          
                );
                $reply_info = M('Firend_reply')->where($reply_where)->getField('id,count(id) count_id',true);
            }
            foreach($firend as &$v){
               $v['count'] = ternary($reply_info[$v['id']]['count_id'], 0);               
            }
        }
        
        $this->outPut($firend, 0);
    }
}