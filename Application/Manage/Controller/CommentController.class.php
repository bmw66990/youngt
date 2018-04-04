<?php
/**
 * Created by PhpStorm.
 * User: daipingshan <491906399@qq.com>
 * Date: 2015/3/27
 * Time: 10:40
 */

namespace Manage\Controller;
use Manage\Controller\CommonController;
/**
 * 点评控制器
 * Class CommentController
 * @package Manage\Controller
*/
class CommentController extends CommonController {
    /**
     *  评论列表
     */
    public function index(){
        $paramArray=array(
            array('team_id','','','c'),
            // array('partner_id','','','c'),
            array('title', '', 'like', 'p'),
            array('content','','like','c'),
            array('comment_num', '','','c'),
        );
        $where=$this->createSearchWhere($paramArray);
        $displayWhere=$this->getSearchParam($paramArray);
        $where['p.city_id'] = $this->_getCityId();
        $where['c.is_comment'] = 'Y';
        $count = M('comment')->alias('c')->join('partner p on c.partner_id=p.id')->where($where)->count();

        if($count) {
            $page = $this->pages($count, $this->reqnum);
            $limit = $page->firstRow . ',' . $page->listRows;
            $list = M('comment')->alias('c')
                ->join('partner p on c.partner_id=p.id')
                ->field('c.id,c.team_id,c.partner_id,c.user_id,c.content,c.comment_num,c.comment_display,c.create_time,p.title')
                ->where($where)
                ->order('c.create_time DESC')
                ->limit($limit)
                ->select();
            $this->assign('data', $list);
            $this->assign('pages', $page->show());
            $this->assign('count', $count);

            $teamList = D('Team')->getOrderTeam($list);
            $this->assign('teamList', $teamList);

            $uid = array();
            foreach($list as $row) {
                $uid[] = $row['user_id'];
            }
            if(count($uid) > 0) {
                $map = array(
                    'id' => array('IN', array_unique($uid))
                );
                $userList = M('user')->where($map)->getField('id,username,mobile', true);
                $this->assign('userList', $userList);
            }

        }
        $this->assign('displayWhere', $displayWhere);
        $this->display();
    }

    /**
     *  屏蔽评论
     */
    public function commentDisplay(){
        $id = I('get.id', 0, 'intval');
        if($id){
            $Model=D('Comment');
            $info=$Model->info($id);
            if($info && $info['comment_display']=='Y'){
                $uparr=array('id'=>$id,'comment_display'=>'N');
                $res=$Model->save($uparr);
                if($res===false){
                    //TODO 记录错误日志
                    $this->error('屏蔽失败', U('Comment/index'));
                } else{
                    //TODO 更新search服务
                    //$this->_updateToSearch($data, $table);
                    $this->success('屏蔽成功', U('Comment/index'));
                }
            }else{
                $this->error('评论已被删除或已屏蔽',U('Comment/index'));
            }
        }else{
            $this->error('请求失败',U('Comment/index'));
        }
    }
}