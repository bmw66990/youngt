<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-07-27
 * Time: 15:39
 */
namespace Manage\Controller;

use Manage\Controller\CommonController;

class FeedbackQuestionController extends CommonController {

    /**
     * 反馈列表
     */
    public function index() {
        $where = array(
            'admin_id' => array('NEQ', 0),
            'answer' => array('NEQ', ''),
            'status' => 'reply'
        );

        if(isset($_GET['keyword'])) {
            $keyword = urldecode(I('get.keyword', '', 'strval'));
            $where['content'] = array('like', '%' . $keyword . '%');
            $searchValue['keyword'] = $keyword;
        }

        $model = D('FeedbackQuestion');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getList($where, 'id DESC', $limit);
        $cityList = $this->_getCategoryList('city');
        $userList = $this->_getUserList($list);

        // 分类
        $cateList = $this->_getCategoryList('feedback');

        $this->assign('cityList', $cityList);
        $this->assign('userList', $userList);
        $this->assign('list', $list);
        $this->assign('count', $total);
        $this->assign('pages', $page->show());
        $this->assign('searchValue', $searchValue);
        $this->display();
    }

    /**
     * 发表反馈
     */
    public function add() {
        $cateList = $this->_getCategoryList('feedback');
        $this->assign('cateList', $cateList);
        $this->display();
    }

    /**
     * 提交反馈
     */
    public function insert() {
        $_POST['city_id'] = $this->_getCityId();
        $_POST['user_id'] = $this->_getUserId();

        $model = D('FeedbackQuestion');
        $data = D('FeedbackQuestion')->create();
        if(!$data) {
            $this->error($model->getError());
        }
        $model->content = I('post.content', '', false);
        if($model->add()) {
            $this->success('反馈提交成功', U('FeedbackQuestion/myFeedback'));
            //echo '<script>window.parent.jQuery.fancybox.close();window.parent.parent.location.reload();</script>';
        } else {
            $this->error('反馈提交失败');
        }
    }

    /**
     * 我的反馈
     */
    public function myFeedback() {
        $where = array(
            'user_id' => $this->_getUserId()
        );

        if(isset($_GET['keyword'])) {
            $keyword = urldecode(I('get.keyword', '', 'strval'));
            $where['content'] = array('like', '%' . $keyword . '%');
            $searchValue['keyword'] = $keyword;
        }

        $model = D('FeedbackQuestion');
        $total = $model->getTotal($where);
        $page = $this->pages($total, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = $model->getList($where, 'id DESC', $limit);
        $cityList = $this->_getCategoryList('city');

        // 分类
        $cateList = $this->_getCategoryList('feedback');

        $this->assign('cityList', $cityList);
        $this->assign('count', $total);
        $this->assign('pages', $page->show());
        $this->assign('list', $list);
        $this->assign('searchValue', $searchValue);
        $this->display();

    }

    protected function _getUserList($list) {
        $userId = array();
        foreach($list as $row) {
            $userId[] = $row['user_id'];
        }

        if(!$userId) {
            return array();
        }

        $map = array(
            'id' => array('IN', array_unique($userId))
        );

        $data = M('User')->where($map)->getField('id,realname', true);
        return $data;
    }
}