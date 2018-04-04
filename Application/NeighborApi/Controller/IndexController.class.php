<?php
/**
 * Created by sublime.
 * User: ma
 * Date: 2015-04-08
 * Time: 15:14
 */
namespace NeighborApi\Controller;

/**
 * 邻里圈接口
 * Class UserController
 * @package NeighborApi\Controller
 */
class IndexController extends CommonController {

    /**
     * @var bool 是否验证uid
     */
    protected $checkUser = false;
    /**
     * 地球半径 km
     */
    const EARTH_RADIUS = 6378.138;
    
  

  public function index() {
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 10
0px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>青团网邻里圈
接口</b>！', 'utf-8');
        exit;

    }

  // 社区首页
    public function getZoneList() {
        $this->_checkblank('catid');        
        $catid = I('post.catid','');
        $mobile = I('post.mobile');
        $last_id = I('post.last_id',0, 'intval');
        
        $where = array(
            'catid'=>$catid,        
        );
        if($last_id){
            $where['id']=array('lt',$last_id);
        }                 
           
        $cat_picture = M('circle_picture')->field('id,catid,title,picture,link')->where($where)->order('id desc')->select();

        if($cat_picture){            
            $banner = array();
            foreach ($cat_picture as $k => $v) {
                $banner[$k]['id'] =  $v['id'];
                $banner[$k]['catid'] =  $v['catid'];
                $banner[$k]['title'] =  $v['title'];
                $banner[$k]['picture'] =  getImagePath($v['picture']); 
                $banner[$k]['link'] =  $v['link'];
            }            
        }else{
            $banner['0']['id'] = '';
            $banner['0']['catid'] = '';
            $banner['0']['title'] = '';
            $banner['0']['picture'] = 'http://pic.youngt.com/lingliquan/linbanner.jpg';
            $banner['0']['link'] = '';  
        }
         
        $zone_pic  =  'http://pic.youngt.com/lingliquan/linbanner.jpg';
        $firend  = D('Firend')->getList($where, 'id desc', $this->reqnum, 'id,catid,catname,userid,uid,username,title,content,createtime,hits,status'); 
                     
        if($firend) {            
            $firend_ids = array();            
            foreach($firend as &$v){
                if(isset($v['id']) && trim($v['id'])){
                    $firend_ids[$v['id']] = $v['id'];                    
                }

                $pic_where = array(
                    'catid' => $catid, 
                    'userid' => $v['userid'], 
                    'artid' => $v['id'], 
                );  
                
                $mypic = M('Firend_pic')->where($pic_where)->getField('img',true);                
                if($mypic){
                    $v['picture'] = $mypic;
                }else{
                    $v['picture'] = array();
                }
                // 判断是快店用户还是青团用户
                if($v['status'] == 1){                     
                    $msguser = self::judge($v['uid']);  
                    foreach ($msguser as $k => $m) {
                        $avatar = $m['pic'];                        
                        $username = $m['nickname'];
                    }                                         
                    if($avatar){
                        $v['avatar'] = self::getImagePath($avatar);                        
                    }else{
                        $v['avatar'] = '';
                    } 
                    $v['username'] = $username;
                }else{
                    $where = array(
                        'id' => $v['userid'],
                        'mobile'    => $v['uid'],
                        '_logic'    => 'or',
                    );
                    $msguser = M('user')->where($where)->find();
                    $avatar = $msguser['avatar'];
                    if($avatar){
                        $v['avatar'] = getImagePath($avatar);
                    }else{
                        $v['avatar'] = '';
                    } 
                    $v['username'] = $msguser['username'];
                }               
                
                $id = M('Firend_reply')->where('art_id='.$v['id'])->getField('id',true); 
                $counts = count($id); 
                $v['count']=$counts;                
                $userid = M('firend_zan')->where(array('artid'=>array('in',$v['id'])))->limit(5)->getField('userid',true);
                if($userid){          
                    $where = array(
                        'id' => array('in',$userid), 
                    );                              
                    $user  = D('User')->getList($where, 'id desc', $this->reqnum, 'id,username');
                    if($user){
                        foreach ($user as $myid) {
                           $myid['userid'] = $user['id'];
                           $myid['username'] = $user['username'];                         
                        }
                    }
                    $v['zan'] = $user;                   
                }

                $where = array('art_id'=>$v['id']);
                $reply_info  = D('Firend_reply')->getList($where, 'id desc', $this->reqnum, 'id,userid,username,content,createtime,comment_id');
                
                if($reply_info){
                    foreach ($reply_info as &$re) {
                        if($v['status'] == 1){                            
                            $msguser = self::judge($re['userid']);
                            //var_dump($re['userid']);
                            foreach ($msguser as $k => $m) {                                    
                                $username = $m['nickname'];
                                $pic = $m['pic'];                                
                            }
                            if($pic){
                                $re['avatar'] =  self::getImagePath($avatar);

                            }else{
                               $re['avatar'] = '';
                            } 
                                                           
                        }else{                            
                            $avat = M('user')->where('id =' .$re['userid'])->getField('avatar');
                            if($avat){
                                $re['avatar'] = getImagePath($avat);
                            }else{
                               $re['avatar'] = '';
                            }   
                        }              
                    }
                }
                $v['reply'] = $reply_info;               
            }    
              
        }
       
        
        $data = array(
            'banner'    =>  $banner,
            'zone_pic'  =>  $zone_pic,
            'firend'    =>  $firend,
        );  
        $hasnext = $this->setHasNext(false, $data, $this->reqnum - 1);
        $this->outPut($data, 0);
        
    }  

    // 定位
    public function location($lng, $lat){
        $lng = I('post.lng',''); 
        $lat = I('post.lat',''); 
        $location = $this->_returnSquarePoint($lng, $lat, 10);
        $map = array(
            'lng' => array('between', array($location['left-top']['lng'],$location['right-bottom']['lng'])),
            'lat' => array('between', array($location['right-bottom']['lat'],$location['right-top']['lat'])),
            'status'=> '1',
        );
        $data = M('firend_zone')->where($map)->field('catid,catname')->select();
        if($data){
            $this->outPut($data, 0); 
        }else{
            $this->outPut('', 1, null, '附近暂无数据' );
        }   
    }

    protected function _returnSquarePoint($lng, $lat, $distance) {
        $dlng = 2 * asin(sin($distance / (2 * self::EARTH_RADIUS)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = $distance / self::EARTH_RADIUS;
        $dlat = rad2deg($dlat);
        return array(
            'left-top' => array('lat' => $lat + $dlat, 'lng' => $lng - $dlng),
            'right-top' => array('lat' => $lat + $dlat, 'lng' => $lng + $dlng),
            'left-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng - $dlng),
            'right-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng + $dlng)
        );
    }

// 删除消息
    public function Minemsgdel() {   
        $this->check();                         
        $this->_checkblank('artid');        
        $artid = I('post.artid',''); 
        $where = array(
            'id' => $artid,
            'userid' => $this->uid,
        );       
        $firend  = M('firend')->where($where)->delete();               
        if($firend){
            $this->outPut($firend, 0);
        }else{
            $this->outPut('', 1, null, '删除失败' );
        }        
    }

// 举报
    public function putReport() { 
        $this->check();                   
        $this->_checkblank(array('artid'));                  
        $artid = I('post.artid',''); 
        $userid = I('post.userid','');
        $mobile = I('post.mobile');
        $myartid = M('firend')->where('id='.$artid)->getField('id');
        if($mobile){
            $msguser = self::judge($mobile);
            foreach ($msguser as $k => $m) {                                    
                $myname = $m['nickname'];
            }  
            $username = $myname;
        }else{
            $where = array(
                'id' => $userid,
                'id' => $this->uid,
                '_logic'    => 'or',
            );
            $username = M('user')->where($where)->getField('username');
           
        }
        
        if(empty($myartid) || empty($username)){            
            $this->outPut('', 1, null, '举报失败，请检查提交参数' );
        }
        $data = array(
            'art_id' => $artid,
            'userid' => $this->uid,
            'black' => 'N',
            'createtime' => time(),
            'hits' => '1',
        );                  
        $firend  = M('firend_report')->add($data);            
        $this->outPut($data, 0);     
    }
   

// 发文字和发图片
    public function putmsg() {
        $this->check();
        $userid = I('post.userid','','trim');
        $catid = I('post.catid','','trim');
        $mobile = I('post.mobile');
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
        
        $title = str_replace(' ', '',I('post.content'));
        $tmpStr = json_encode($title); //暴露出unicode
        $tmpStr = preg_replace("#(ue[0-9a-f]{3})#ie","addslashes('\\1')",$tmpStr); //将emoji的unicode留下，其他不动
        $text = json_decode($tmpStr);
        $catname = M('Firend_zone')->where('catid='.$catid)->getField('catname');
        if($mobile){
            $msguser = self::judge($mobile);
            foreach ($msguser as $k => $m) {                                    
               $myname = $m['nickname'];
            }
            $username = $myname;
            $uid = $mobile;
            $status = '1';
        }else{           
            $usermsg = M('user')->where('id='.$this->uid)->find();
            $username = $usermsg['username'];
            $uid = $usermsg['mobile'];
            $status = '0';
        }
                
        if($picture){            
            $pic = Y;         
            $content = $picture[0];        
        }else{
            $pic = N;
            $content = $title;
        }
        
        $data = array(
            'catid' => $catid,
            'catname' => $catname,
            'uid'=>$uid,
            'userid' => $this->uid,
            'username' => $username,            
            'content' => $content,
            'title' => $text,                                
            'createtime' => time(),
            'picture'=>$pic,
            'status'=>$status,
        );     
        $firend  = M('firend')->add($data);
        if($pic == "Y"){  
            $pic =array(
                'artid' => $firend,
                'catid' => $catid,
                'userid' => $this->uid,
            ); 
            $picData = array();
            foreach ($picture as $row) {
                if ($row && strpos(trim($row), 'http://') === 0) {
                    $pic['img'] = trim($row);
                    $pic['createtime'] = time();
                    $picData[] = $pic;
                }
            }
            if (M('firend_pic')->addAll($picData) === false) {
                $this->errorInfo['info'] = M('firend_pic')->getDbError();
                $this->errorInfo['sql'] = M('firend_pic')->_sql();                
            }
        }
        
        if($mobile){
            $fuser = self::judge($mobile);
            foreach ($fuser as $k => $m) {
                $avatar = $m['pic'];                        
                $id = $m['id'];                        
                $username = $m['nickname'];
            }  
            $myname = $username;
            $avatar =  self::getImagePath($avatar);
        }else{            
            $fuser = M('user')->where('id='.$this->uid)->find();
            $id = $fuser['id'];
            $myname = $fuser['username'];
            $avatar = $fuser['avatar'];
        }

        
        
        $user=array(                    
            'uid' => $id,
            'username' => $myname,                        
            'avatar' => $avatar,                        
            'picture'=>'',
        );

        $uid = M('firend_user')->where('uid='.$id)->getField('uid');
        if(empty($uid)){
             M('firend_user')->add($user);
        }
        
        if($firend){
            $this->outPut($data, 0,'','');
        }else{
            $this->outPut('', 1, null, '发布失败' );
        }
    }

// 点赞
    public function dianzan() {
        $this->check(); 
        $this->_checkblank('art_id');        
        $artid = I('post.art_id'); 
        $userid = I('post.userid');  
        $mobile = I('post.mobile');

        if($mobile){            
            $user = self::judge($mobile);
            foreach ($user as $k => $m) {
                $avat = $m['pic'];                        
                $username = $m['nickname'];
            }  
            $username = $username;
            $avat =  self::getImagePath($avatar);
        }else{
            $user = M('user')->where(array('id'=>$this->uid))->find();
            $username = $user['username'];
            $avat = $user['avatar'];
        }
        
        $where = array(
            'userid' => $this->uid,
            'artid'=>$artid,
        );
        if($avat){
            $avatar = getImagePath($avat);
        }else{
           $avatar = '';
        }
        
        $comment_user = M('Firend_zan')->where($where)->getField('id',true);
        $artuid = M('Firend')->where('id='.$artid)->getField('userid');
        $mycount = count($comment_user);
        if($mycount == 0){                       
            $data = array(
                'artid' => $artid,
                'userid' => $this->uid,                            
                'username' => $username,                            
                'artuid' => $artuid,                            
                'avatar' => $avatar,                            
                'read' => Y,  
                'createtime' => time(),
            ); 

            $zan  = M('Firend_zan')->add($data);
            $firend  = M('firend')->where('id='.$artid)->setInc('hits');
            $this->outPut($data, 0);
        }else{
             $this->outPut('-1', 0,'','');
            exit;
        }
            
        
    }

// 取消点赞
    public function delzan() {  
        $this->check();             
        $artid = I('post.id');  
        $userid = I('post.userid');  
        $where = array('artid' => $artid,'userid'=>$this->uid);        
        $hits = M('firend')->where('id='.$artid)->setDec('hits'); 
        $zan =  M('firend_zan')->where($where)->delete();        
        if($zan){
            $this->outPut($zan, 0,'','');
        }else{
            $this->outPut('-1', 0,'','');
        }        
    }

// 评论
    public function putpinglun() {
        $this->check(); 
        $id = trim(I('post.id',''));       
        $userid = trim(I('post.userid',''));
        $mobile = I('post.mobile');
        $content = str_replace(' ', '',I('post.content','','trim'));        
        $where = array(
            'id'=>$this->uid,
        );
        if($mobile){
            
            $msguser = self::judge($mobile);
            foreach ($msguser as $k => $m) {
                $myavatar = $m['pic'];                        
                $myname = $m['nickname'];
            } 
            $username = $myname;
            if($myavatar){
                $avatar =  self::getImagePath($avatar);
            }else{
                $avatar = '';
            }           
        }else{
            $info = M('user')->where($where)->find();       
            $username = $info['username'];                                                  
            if($info['avatar']){
                $avatar = getImagePath($info['avatar']);
            }else{
               $avatar = '';
            }
        }
                
        $data = array(
                'art_id' => $id,
                'userid' => $this->uid,
                'username' => $username,
                'avatar' => $avatar,
                'content' => $content,
                'createtime' => time(),                
        );   

        $firend  = M('firend_reply')->add($data);
        
        $this->outPut($data, 0);
    }

// 回复
    public function puthuifu() {
        $this->check(); 
        $mid = I('post.id');           
        $comment_id=trim(I('post.comment_id'));  
        $mobile = I('post.mobile');
        $content = str_replace(' ', '',I('post.content','','trim'));
        
        // 获得用户名  
        if($mobile){            
            $comment = self::judge($mobile);
            $comment_name = $comment['nickname']; 
            foreach ($comment as $k => $m) {
                $id = $m['id'];                        
                $avatar = $m['pic'];                        
                $username = $m['nickname'];
            } 
            if($avatar){
                $avatar =  self::getImagePath($avatar);
            }else{
               $avatar = '';
            }
            $com_name=$username;
        }else{
            $comment = M('user')->where('id='.$this->uid)->find();
            $comment_name = $comment['username']; 
            $com_name=$comment['username'];
            if($comment['avatar']){
                $avatar = getImagePath($comment['avatar']);
            }else{
               $avatar = '';
            } 
        }
        $reply = M('firend_reply')->where('id='.$mid)->find();
        //var_dump($this->uid);

        
        $data = array(            
            'userid' => $this->uid,
            'avatar' => $avatar,
            'art_id' => $reply['art_id'],
            'username' => $com_name,
            'content' => $content,
            'createtime' => time(),
            'comment_id' => $reply['userid'],
            'comment_name' => $comment_name,
            're_comid' => $mid,
            'status' => 1,
            'read' => Y,
        );
        
        $firend  = M('firend_reply')->add($data);
        $this->outPut($data, 0);
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
