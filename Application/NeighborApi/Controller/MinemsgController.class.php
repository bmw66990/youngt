<?php
/**
 * Created by PhpStorm.
 * User: ma
 * Date: 2015-04-07
 * Time: 15:14
 */
namespace NeighborApi\Controller;


/**
 * 我的发送接口
 * Class ReviewController
 * @package NeighborApi\Controller
 */
class MinemsgController extends CommonController {

    protected $checkUser = false;

     // 我的主页
    public function Minemsg() {            
        $this->check();
        $userid = I('post.userid','');
        $last_id = I('post.last_id', '');
        $mobile = I('post.mobile', '');   //快店专用
        // 判断访问的是青团用户还是快店用户
        if($mobile){
            $user_info = self::judge($userid);
            foreach ($user_info as $k => $m) {
                $userid = $m['id'];
                $avatar = $m['pic'];                                                            
                $myname = $m['nickname'];
            } 
            $username = $myname;
            if($avatar){
                $avatar = self::getImagePath($avatar);
            }else{
                $avatar = '';
            } 
            $where1 = array(
                'uid'=> $mobile,            
            );
        }else{
            $user_info = M('user')->where('id = ' . $userid)->find();
            $username = $user_info['username'];
            $userid = $user_info['id'];
            if($user_info['avatar']){
                $avatar = getImagePath($user_info['avatar']);
            }else{
               $avatar = '';
            } 
            $where1 = array(
                'uid'=> $user_info['mobile'],      
                'id' => $userid,                
                '_logic'    => 'or',      
            );
        }
        // 兼顾快店id和青团userid
        if($last_id){
            $where=array(                
                '_complex'=>$where1,
                '_logic'=>'and'
            );
            $where['id']=array('lt',$last_id);    
        }else{
            $where = $where1;
        } 
          
        $firend  = D('Firend')->getList($where, 'id desc', $this->reqnum, 'id,catid,catname,uid,userid,title,content,createtime,picture,hits,status');
        if($firend) {             
            $firend_ids = array();
            foreach($firend as &$v){
                if(isset($v['id']) && trim($v['id'])){
                    $firend_ids[$v['id']] = $v['id'];
                }                
                if($v['picture'] == "Y"){                    
                    $pic = M('Firend_pic')->where('artid = ' . $v['id'])->getField('img',true);
                    $v['picture'] = $pic;
                }else{   
                    $v['picture'] = array();
                }
                $v['userid'] = $userid;
                $v['username'] = $username; 
                $v['avatar']   = $avatar;                              
            }              
        }
        
        $user = M('Firend_user')->where('uid = ' . $userid)->find();
        $mypic = getImagePath($user['picture']); 

        $data = array(
            'mypic'=>$mypic,
            'msg'=>$firend,
        );
        if ($data === false) {   
            $this->outPut(null, -1, null, '数据为空');            
        }
        $hasnext = $this->setHasNext(false, $data, $this->reqnum - 1);
        $this->outPut($data, 0,'','');
    }

    // 评论提及到我的
    public function aboutMsg() {                       
        $userid = trim(I("post.userid",''));
        $last_id = I('post.last_id','');
        $mobile = I('post.mobile');
        if($mobile){
            $where = array(
                'uid'=> $mobile, 
            );
        }else{
            $user_info = M('user')->where('id = ' . $this->uid)->find();
            $where = array(
                'uid'=> $user_info['mobile'], 
            );
        }
       
        
        $id = M('firend')->where($where)->getField('id',true);
        if(empty($id)){
            $this->outPut(null, -1, null, '参数错误'); 
        }
       // 获得关于我的赞的详情                                        
        $nzan_where = array(
            'artuid' => $this->uid,                                        
            'read' => 'Y',
        );
        if($last_id){
            $nzan_where['id']=array('lt',$last_id);
        } 
        $zan  = D('Firend_zan')->getList($nzan_where, 'id desc', $this->reqnum, 'id,userid,artid,read,artuid,createtime');                
        $zan_info=array();
        if($zan){
            foreach ($zan as $k => $vz) {
                $zan_info[$k]['id'] = $vz['id'];    
                $zan_info[$k]['userid'] = $vz['userid'];    
                $zan_info[$k]['art_id'] = $vz['artid'];    
                $zan_info[$k]['read'] = $vz['read'];    
                $zan_info[$k]['artuid'] = $vz['artuid'];    
                $zan_info[$k]['createtime'] = $vz['createtime'];  
                $avatar = M('user')->where('id='. $vz['userid'])->getField('avatar');
                if($avatar){
                    $zan_info[$k]['avatar'] = getImagePath($avatar);
                }else{
                    $zan_info[$k]['avatar'] = '';
                }
                $zan_info[$k]['title'] = M('firend')->where('id='. $vz['artid'])->getField('content');    
                $zan_info[$k]['type'] = 'zan';                
                $zan_info[$k]['username'] = ''; 
                $zan_info[$k]['content'] = ''; 
                $zan_info[$k]['comment_id'] = '';
                $zan_info[$k]['comment_name'] = '';
            }
            if($zan_info){                
                $data =  array_merge($zan_info,'');
            }else{
                $zan_info = array();                
            }
        }else{
            $zan_info = array(); 
        }
        
        // 获取关于我的评论的详情
        $re_where = array(
            'art_id' => array('in',$id),
            'read' => 'Y',
        );
        if($last_id){
            $re_where['id']=array('lt',$last_id);
        } 
        $reply  = D('Firend_reply')->getList($re_where, 'id desc', $this->reqnum, 'id,art_id,userid,username,content,createtime,read,comment_id');
        $reply_info=array();
        if($reply){
            foreach ($reply as $k => $va) {  
                $reply_info[$k] = $va;
                if($va['comment_id']){
                    $avatar = M('user')->where('id='. $va['comment_id'])->getField('avatar');
                    $comment_name = M('user')->where('id=' . $va['comment_id'])->getField('username');
                     $reply_info[$k]['comment_name'] = $comment_name;
                    if($avatar){
                        $reply_info[$k]['avatar'] = getImagePath($avatar);
                    }else{
                        $reply_info[$k]['avatar'] = '';
                    }
                }else{
                    $avatar = M('user')->where('id=' . $va['userid'])->getField('avatar');
                    $reply_info[$k]['comment_name'] = '';
                    if($avatar){
                        $reply_info[$k]['avatar'] = getImagePath($avatar);
                    }else{
                        $reply_info[$k]['avatar'] = '';
                    }                                
                }  
                $reply_info[$k]['title'] = M('firend')->where('id='. $va['art_id'])->getField('content');
                $reply_info[$k]['type'] = 'reply'; 
                $reply_info[$k]['artuid'] = '';        
            }
            if($reply_info){                
                $data =  array_merge($zan_info,$reply_info);
            }else{
                $reply_info = array();
            }
        }else{
            $reply_info = array();
        }
        if($data){    
            $hasnext = $this->setHasNext(false, $data, $this->reqnum - 1);
            $this->outPut($data, 0);            
        }else{
            $this->outPut(null, -1, null, '数据为空');             
        }
    }

    // 清空消息列表
    public function clearMsg() {    
        
        $userid =trim(I('post.userid',''));  
        $mobile = I('post.mobile');
        if($mobile){
            $where = array(
                'uid'=> $mobile, 
            );
        }else{
            $user_info = M('user')->where('id = ' . $this->uid)->find();
            $where = array(
                'uid'=> $user_info['mobile'], 
            );
        }
        
        $art_id  = M('firend')->where($where)->getField('id',true);
        if(empty($art_id)){
            $this->outPut(null, -1, null, '参数错误');
        }
        $where = array(
            'artid' => array('in',$art_id),
        );
        $reply  = M('firend_reply')->where($where)->setField('read','N');               
        $zan  = M('firend_zan')->where('artuid='.$this->uid)->setField('read','N');               
        $clear = array(
            'reply'=>$reply,
            'zan'=>$zan,
        );
        if($clear){
            $this->outPut($clear, 0);
         }else{
            $this->outPut(null, -1, null, '数据为空');
         }
       
    }

    // 我的主页更换用户主图
    public function  replacePic() {       
        $id = trim(I('post.userid',''));  
                  
        if (!empty($_FILES)) {
            $res = $this->upload('img', 'points', '', array('maxSize' => 1024 * 1024 * 5));            
            ob_end_clean(); //之前有输出,清空掉                        
            if ($res) {
                $picture = array(); 
                foreach ($res as $k => $row) {
                    $picture[] = getImagePath($row['newpath'] . '/' . $row['savename']);
                }   
                $data['image'] = $picture;                  
            } 
        }   

        if($picture){               
            $pic = $picture[0];        
        }else{
            $pic = '';
        }
        $result = M('firend_user')->where('uid=' .$this->uid)->setField('picture',$pic);
        if($result){
            $this->outPut($result, 0);
        }else{
            $this->outPut(null, -1, null, '更换失败');
        }        
    }

    // 删除消息
    public function Minemsgdel() {       
        $id = trim(I('post.id',''));            
        $firend  = M('firend')->where('id='.$id)->delete();               
        if($firend){
            $this->outPut($firend, 0);
        }else{
            $this->outPut(null, -1, null, '删除失败');
        }        
    } 

    public function judge($mobile){
        
        $sql = "select * from user where id = $mobile";
       // var_dump($sql);  
        $msguser = M("User",NULL,"mysql://kuaidian_2016:kuaidian_20162016@rds4s805d2zw55r9o5sj.mysql.rds.aliyuncs.com:3306/youngt_kuaidian")->query($sql); 
      //  var_dump($sql);       
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

?>