<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/7/28
 * Time: 14:47
 */

namespace Admin\Controller;

/**
 * Class FeedbackController
 * @package Admin\Controller
 */
class FeedbackController extends CommonController{

    /**
     * 代理问题反馈列表
     */
    public function index(){
        $Model = D('FeedbackQuestion');
        $status = I('get.status','','trim');
        $where = $total_where = array();
        if($status){
            $where['f.status'] = $status;
            $total_where['status'] = $status;
        }
        $total = $Model->getTotal($total_where);
        $this->_writeDBErrorLog($total, $Model);
        $page  = $this->pages($total, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $field = 'f.*,c.name,u.realname';
        $data = $Model->getAllList($where, 'f.id desc', $limit,$field);
        $city_data = $this->_getCategoryList('city');
        $this->assign('status',$status);
        $this->assign('data',$data);
        $this->assign('city_data',$city_data);
        $this->display();
    }

    /**
     * 回复反馈
     */
    public function reply(){
        $Model = D('FeedbackQuestion');
        $id = I('post.id',0,'intval');
        $answer = I('post.answer','','trim');
        if($id && $answer){
            $upData = array('status'=>'reply','answer_time'=>time(),'answer'=>$answer,'id'=>$id,'admin_id'=>$this->user['id']);
            $res = $Model->save($upData);
            if($res){
                $this->addOperationLogs("操作：回复代理反馈,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},回复id：id={$id}，回复内容：{$answer}");
                $this->redirect_message(U('Feedback/index'),  array('success' => base64_encode('回复成功')));
            }else{
                $this->redirect_message(U('Feedback/checkFeedback',array('id'=>$id)),  array('error' => base64_encode('回复失败！')));
            }
        }else{
            $this->redirect_message(U('Feedback/checkFeedback',array('id'=>$id)),  array('error' => base64_encode('请输入回复内容后再提交！')));
        }
    }

    /**
     * 查看反馈
     */
    public function checkFeedback(){
        $id = I('get.id',0,'intval');
        $Model = D('FeedbackQuestion');
        if($id){
            $info = $Model->find($id);
            if($info){
                $this->assign('feedback_info',$info);
            }else{
                $this->redirect_message(U('Feedback/index'),  array('error' => base64_encode('信息不存在！')));
            }
        }else{
            $this->redirect_message(U('Feedback/index'),  array('error' => base64_encode('请求非法！')));
        }
        $this->display();
    }

    /**
     * 关闭或启用问题反馈
     */
    public function doFeedbackStatus(){
        $id = I('get.id',0,'intval');
        $Model = D('FeedbackQuestion');
        if($id){
            $info = $Model->find($id);
            if($info){
                $upData = array('id'=>$id,'status'=> $info['status'] != 'close' ? 'close' : ($info['answer_time'] > 0 ? 'reply' : 'noreply') );
                $Model->save($upData);
                $this->addOperationLogs("操作：关闭或启用问题反馈,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},id：id={$id}，状态：{$upData['status']}");
                $this->redirect_message(U('Feedback/index'),  array('success' => base64_encode('操作成功')));
            }else{
                $this->redirect_message(U('Feedback/index'),  array('error' => base64_encode('信息不存在！')));
            }
        }else{
            $this->redirect_message(U('Feedback/index'),  array('error' => base64_encode('请求非法！')));
        }
    }
}