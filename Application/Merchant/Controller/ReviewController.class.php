<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class ReviewController extends CommonController {

    private $numType = array(
        '1' => '很不满意',
        '2' => '不满意',
        '3' => '一般',
        '4' => '满意',
        '5' => '很满意',
    );

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $teamId = I('get.team_id', 0, 'intval');
        $num = I('get.num', 0, 'intval');

        // 查询该用户所有团单
        $teamRes = M('team')->where(array('partner_id' => $this->partner_id))->field('id,product')->select();

        // 查询where
        $where = array('comment.partner_id' => $this->partner_id, 'comment.is_comment' => 'Y');
        if (trim($teamId)) {
            $where['comment.team_id'] = $teamId;
        }
        if (trim($num)) {
            $where['comment.comment_num'] = $num;
        }

        // 查询数据
        $comment = M('comment');
        $count = $comment->where($where)->count();
        $Page = $this->pages($count, $this->reqnum);
        $field = array('comment.team_id,comment.user_id,comment.create_time,comment.order_id,comment.comment_num,comment.content,comment.partner_content,team.product,user.username');
        $list = $comment->field($field)->order('comment.create_time desc')->where($where)
                        ->join('inner join user on user.id=comment.user_id')
                        ->join('inner join team on team.id=comment.team_id')
                        ->limit($Page->firstRow . ',' . $Page->listRows)->select();

        if ($list) {
            foreach ($list as &$v) {
                $v['comment_num'] = intval($v['comment_num']);
                isset($this->numType[$v['comment_num']]) && $v['comment_num'] = $this->numType[$v['comment_num']];
            }
        }

        // 获取团购数和购买人数
        $where = array('comment.partner_id' => $this->partner_id, 'comment.is_comment' => 'Y');
        $res = $comment->where($where)->field(array('count(distinct(team_id))' => 'team_num', 'count(id)' => 'buy_count'))->find();
        $teamNum = 0;
        $buyCount = 0;
        if (isset($res['team_num'])) {
            $teamNum = $res['team_num'];
        }
        if (isset($res['buy_count'])) {
            $buyCount = $res['buy_count'];
        }
        $where['num'] = array('egt', 3);
        $grade_count = $comment->where($where)->count();
        $grade_comment = (doubleval($grade_count / $count)) * 100;

        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
            'team_num' => $teamNum,
            'buy_count' => $buyCount,
            'grade_count' => $grade_count,
            'grade_comment' => $grade_comment,
            'team' => $teamRes,
            'good' => $this->numType,
            'team_id' => $teamId,
            'num' => $num,
        );

        $this->assign($data);
        $this->display();
    }

    public function review() {

        $id = I('get.id', 0, 'intval');

        $comment = M('comment');
        if (trim($id)) {

            $commentRes = $comment->field('content,create_time,user_id')->where('order_id=' . $id)->find();
            $user = M('user');
            $username = $user->where(array('id' => $commentRes['user_id']))->getField('username');

            $data = array(
                'order' => $commentRes,
                'username' => $username,
                'id' => $id,
            );
            $this->assign($data);
            $this->display('Review/review');
            exit;
        }

        // 回复
        $id = I('post.id', 0, 'intval');
        $content = I('post.content', '', 'trim');
        if ($content == '') {
            $this->ajaxReturn(array('code' => -1, 'error' => '评论内容不能为空！'));
        }
        $result = $comment->where(array('order_id'=>$id))->save(array('partner_content' => trim($content)));
        if (!$result) {
            $this->ajaxReturn(array('code' => -1, 'error' => '回复失败！'));
        }
        $this->ajaxReturn(array('code' => 0,'success'=>'回复成功！'));
        
    }

}
