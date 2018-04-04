<?php
/**
 * Created by sublime.
 * User: ma
 * Date: 2016-04-07
 * Time: 15:14
 */
namespace NeighborApi\Controller;

/**
 * 内容详情
 * Class ReviewController
 * @package NeighborApi\Controller
 */
class DetailController extends CommonController {

protected $checkUser = true;

    // 详情列表
    public function getDeltail() {
        
        $this->_checkblank('id');
        $id = trim(I('post.id'));  
        $mobile = I('post.mobile');  
        $last_id = I('post.last_id','');

        $where = array('id' => $id);
         
        // 获得文章详情
        $list   = M('firend')->where($where)->find(); 
        if(empty($list)){
            $this->outPut('', 1, null, '参数错误' );            
        }
        // 获得消息的图片详情
        $pic = $list['picture'];
     
        if($pic == 'Y'){
            $pic_where = array('artid' => $id);            
            $mypic = M('Firend_pic')->where($pic_where)->getField('img',true);
        }else{
            $mypic = array();

        }

        // 获得用户头像        
        if($mobile){            
            $myuser = self::judge($list['uid']);
            foreach ($myuser as $k => $m) {
                $avatar = $m['pic'];                        
                $myname = $m['nickname'];
            }             
            $username = $myname;  
            $userpic = self::getImagePath($avatar);
        }else{
            $myuser = M('user')->where('id='.$list['userid'])->find();            
            $username = $myuser['username'];  
            $userpic = getImagePath($myuser['avatar']);
        }

        // 获得文章的点赞人头像
        $userid = M('firend_zan')->where('artid='.$id)->getField('userid',true); 
        if($userid){
            $zan_where = array(
                'id' => array('in',$userid), 
            );
            $zan_info  = D('User')->getList($zan_where, 'id desc', $this->reqnum, 'id,username,avatar');
            if($zan_info) {
                $user = array();
                foreach ($zan_info as $k=> $v) {
                    $user[$k]['id'] = $v['id'];
                    $user[$k]['username'] = $v['username'];
                    if($v['avatar']){
                        $user[$k]['avator'] = getImagePath($v['avatar']);
                    }else{
                       $user[$k]['avator'] = '';
                    }
                }
            }
        }else{
            $user = array();
        }
        
    
        // 获得消息的回复信息
        $re_where = array(
            'art_id'=>$id,
        );
        if($last_id){
            $re_where['id']=array('lt',$last_id);
        }
        $reply_info  = D('Firend_reply')->getList($re_where, 'id desc', $this->reqnum, 'id,art_id,userid,username,content,comment_id,createtime,re_comid');
        if($reply_info){
            foreach ($reply_info as &$re) {
                if(($re['re_comid'])){           
                    if($mobile){
                        $msguser = self::judge($mobile);  
                        foreach ($msguser as $k => $m) {                                    
                            $username = $m['nickname'];
                        }  
                        $re['comment_name'] = $username;  
                      
                    }else{
                        $username = M('user')->where('id =' .$re['comment_id'])->getField('username');
                        $re['comment_name'] = $username; 
                    }                                
                } 
                $avatar = M('user')->where('id =' .$re['userid'])->getField('avatar');
                if($avatar){
                    $re['avatar'] = getImagePath($avatar);
                }else{
                   $re['avatar'] = '';
                }              
            }
        }else{
            $reply_info=array();
        }
        $firend = array(
            'id' => $list['id'],
            'userid' => $list['userid'],
            'username' => $username, 
            'avatar' => $userpic, 
            'title' => $list['title'],
            'picture' => $mypic,          
            'createtime' => $list['createtime'],
            'hits' => $list['hits'],                      
            'status' => $list['status'],            
            'reply_info' => $reply_info,
            'zan_info' => $user,
        );
        $hasnext = $this->setHasNext(false, $firend, $this->reqnum - 1);
        $this->outPut($firend, 0);
    }

    public function judge($mobile){        
        $sql = "select * from user where mobile = $mobile";
       // var_dump($sql);  
        $msguser = M("User",NULL,"mysql://kuaidian_2016:kuaidian_20162016@rds4s805d2zw55r9o5sj.mysql.rds.aliyuncs.com:3306/youngt_kuaidian")->query($sql);        
        return $msguser;
    }

    public function getImagePath($path) {
        if (!trim($path)) {
            return '';
        }
        if (strpos($path, 'http') !== false) {
            return $path;
        }
        return 'http://kuaidian.youngt.net/Uploads/' . $path;        
    }
}