<?php

/**
 * Created by PhpStorm.
 * User: daipingshan <491906399@qq.com>
 * Date: 2015/3/27
 * Time: 11:22
 */

namespace Common\Model;

use Common\Model\CommonModel;

/*
 *  comment 评论表模型
 */

class CommentModel extends CommonModel {

    /**
     * 评论评分类型
     * @var type 
     */
    private $commentNumType = array(
        '1' => 'one',
        '2' => 'two',
        '3' => 'three',
        '4' => 'four',
        '5' => 'five',
    );

    /**
     *  获取评论列表
     *  @param array $where     : 获取数据信息的条件
     *  @param string $field    : 获取数据信息字段名
     *  @param string $limit    : 分页
     *  @param string $orderby  : 排序
     *  @return array|bool $data: 返回数据信息
     */
    public function getComments($where, $limit, $orderby = 'id desc', $field = '*') {
        $data = $this->alias('c')
                ->field($field)
                ->join(array('inner join user u ON c.user_id = u.id ', 'inner join team t ON c.team_id = t.id'))
                ->where($where)
                ->order($orderby)
                ->limit($limit)
                ->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

    /**
     * 获取未评价订单数量
     * @param $uid
     * @return int
     */
    public function getUnReviewNum($uid) {
        $map = array(
            'user_id' => $uid,
            'is_comment' => 'N',
            'consume' => 'Y',
        );
        $num = $this->where($map)->count();
        if ($num === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        $num = $num ? $num : 0;
        return $num;
    }

    /**
     * 检测订单是否评论
     * @param $order
     * @return bool
     */
    public function checkIsComment($order) {
        if (is_numeric($order)) {
            $order_id = $order;
            $state = M('Order')->getFieldById($order_id, 'state');
        } else {
            $order_id = $order['id'];
            $state = $order['state'];
        }

        if ($state != 'pay') {
            return array(
                'error' => '订单未支付，无法评论',
                'code' => 1020,
            );
        }

        //只要消费一张就可以评价  只要点击确认收货 也可以评论
        $where = array(
            'order_id' => $order_id,
            'consume' => 'N',
        );

        if (M('Coupon')->where($where)->count() != 0) {
            return array(
                'error' => '未点击”确认收货“ 或者 券号未消费，不能评论！',
                'code' => 1032
            );
        }

        $map = array(
            'order_id' => $order_id,
            'is_comment' => 'N',
        );
        if ($this->where($map)->count() == 0) {
            return array(
                'error' => '此订单已经评论',
                'code' => 1019
            );
        }
        return true;
    }

    /**
     * 评论团单
     * @param $order_id
     * @param $user_id
     * @param $data
     * @return mixed
     */
    public function addComment($order_id, $user_id, $data) {
        $comment = $this->where('order_id=' . intval($order_id))->find();
        $image = ternary($data['image'], '');
        $order = M('Order')->find($order_id);
        $user = M('User')->find($user_id);

        if ($image) {
            $data['is_pic'] = 'Y';
            unset($data['image']);
        }
        $model = M();
        $model->startTrans();
        $data['is_comment'] = 'Y';
        $data['create_time'] = time();
        $res = $this->where('order_id=' . intval($order_id))->setField($data);
        if ($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            $model->rollback();
            return false;
        }
        if ($image) {
            $pic['order_id'] = $order_id;
            $pic['partner_id'] = $comment['partner_id'];
            $pic['user_id'] = $user_id;
            $pic['team_id'] = $comment['team_id'];
            $picData = array();
            foreach ($image as $row) {
                if ($row && strpos(trim($row), 'http://') === 0) {
                    $pic['image'] = trim($row);
                    $pic['create_time'] = time();
                    $picData[] = $pic;
                }
            }
            if (M('CommentPic')->addAll($picData) === false) {
                $this->errorInfo['info'] = M('CommentPic')->getDbError();
                $this->errorInfo['sql'] = M('CommentPic')->_sql();
                $model->rollback();
                return false;
            }
        }

        /* $points = C('POINTS');
          if(isset($points)) {
          $points = $points['review'] > 10 ? 10 : $points['review'];
          } else {
          $points = 10;
          }
          $score = ceil($order['origin'] / $points * 10);
          if (!empty($image)) {
          $score = $score * 2;    //晒图双倍积分
          } */

        // 2015-08-17 update 修改评论积分规则
        $score = 50;
        if (!empty($image)) {
            $score = 100;
        }
        $rs = M('User')->where('id=' . $user_id)->setInc('score', $score); //评论积分
        if ($rs === false) {
            $this->errorInfo['info'] = M('User')->getDbError();
            $this->errorInfo['sql'] = M('User')->_sql();
            $model->rollback();
            return false;
        }
        $creditData = array(
            'create_time' => time(),
            'user_id' => $user_id,
            'score' => $score,
            'action' => 'comment',
            'detail_id' => $order['team_id'],
            'sumscore' => $user['score'] + $score
        );
        $flowRes = M('Credit')->add($creditData);   //积分流水
        if ($flowRes === false) {
            $this->errorInfo['info'] = M('Credit')->getDbError();
            $this->errorInfo['sql'] = M('Credit')->_sql();
            $model->rollback();
            return false;
        }
        $model->commit();
        return array('score' => $score);
    }

    /**
     * 获取评论列表
     * @param type $where
     * @param type $sort
     * @return type
     */
    public function getCommentList($where, $order = '', $limit = 20,$state) {

        if (!isset($where['comment.comment_display'])) {
            $where['comment.comment_display'] = 'Y';
        }

        // 获取多少人评价和评价平均分
        $res = $this->where($where)->where("comment_num IS NOT NULL")->field(array('COUNT(id)' => 'user_count', 'AVG(comment_num)' => 'avg_num'))->find();
        $count = $this->where($where)->count('id');
        //增加未读和差评人数
        $worstwhere = array(
            '_complex'=>$where,
            'comment_num'=>'1',
            '_logic'=>'and'
        );
        $worstcount = $this->where($worstwhere)->count('id');
        $unwhere = array(
            '_complex'=>$where,            
            '_logic'=>'and'
        );       
        $unwhere['_string'] = "comment.partner_content IS NULL";    
        $unreadcount = $this->where($unwhere)->count('id');

        $avgNum = isset($res['avg_num']) && trim($res['avg_num']) ? number_format($res['avg_num'], 1) : '0.0';
        $userCount = $count;

        // 获取每个评分的人数
        $res = $this->where($where)->where("comment_num IS NOT NULL")->field(array('COUNT(id)' => 'user_count', 'comment_num' => 'comment_num'))->group("comment_num")->select();
        $_commentNum = array_combine(array_values($this->commentNumType), array('0', '0', '0', '0', '0'));

        foreach ($res as $v) {
            if (!isset($_commentNum[$v['comment_num']]) && isset($this->commentNumType[$v['comment_num']])) {
                $_commentNum[$this->commentNumType[$v['comment_num']]] = $v['user_count'];
            }
        }

        // 获取评论列表
        if (!trim($order)) {
            $order = 'comment.id desc';
        }
        $field = array(
            'comment.id' => 'id',
            'comment.user_id' => 'user_id',
            'comment.team_id' => 'team_id',
            'comment.order_id' => 'order_id',
            'comment.content' => 'content',
            'comment.partner_id' => 'partner_id',
            'comment.create_time' => 'create_time',
            'comment.partner_content' => 'partner_content',
            'comment.comment_time' => 'comment_time',
            'comment.is_comment' => 'is_comment',
            'comment.cate_id' => 'cate_id',            
            'comment.comment_time' => 'comment_time',
            'comment.comment_detail' => 'comment_detail',
            'comment.comment_display' => 'comment_display',
            'comment.comment_num' => 'comment_num',
            'comment.consume' => 'consume',
            'comment.is_pic' => 'is_pic',
        );
        if (isset($where['_string']) && trim($where['_string'])) {
            $where['_string'] = "({$where['_string']}) and comment.comment_num IS NOT NULL";
        } else {
            switch ($state) {
                case 1:                
                    $where['_string'] = "comment.comment_num IS NOT NULL";
                    break;
                case 2:
                    $where['_string'] = "comment.comment_num = '1'";                    
                    break;
                case 3:
                    $where['_string'] = "comment.partner_content IS NULL";
                    break;            
                default:
                    $res = false;
                    break;
            }
        }   
        $res = $this->where($where)->field($field)->limit($limit)->order($order)->select();
        
        if (!$res) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            $data = array(
                'avgNum' => $avgNum,
                'userCount' => $userCount,
                'worstcount' => $worstcount,
                'unreadcount' => $unreadcount,
                'commentNum' => $_commentNum,
                'list' => array(),
            );
            return $data;
        }

        $team_ids = $partner_ids = $user_ids = $comment_pic_ids = array();
        foreach ($res as &$comment) {
            $team_ids[$comment['team_id']] = intval($comment['team_id']);
            $user_ids[$comment['user_id']] = intval($comment['user_id']);
            $partner_ids[$comment['partner_id']] = intval($comment['partner_id']);


            if (isset($comment['content']) && trim($comment['content'])) {
                $comment['content'] = htmlspecialchars($comment['content']);
            }

            // 获取图片
            if (isset($comment['is_pic']) && strtolower($comment['is_pic']) == 'y') {
                $comment['images'] = array();
                $comment_pic_ids[$comment['order_id']] = intval($comment['order_id']);
            }
        }
        unset($comment);

        $team_info_res = $partner_info_res = $user_info_res = $comment_pic_info_res = array();
        if ($team_ids) {
            $team_info_res = M('team')->where(array('id' => array('in', $team_ids)))->index('id')->field('id,product')->select();
        }
        if ($user_ids) {
            $user_info_res = M('user')->where(array('id' => array('in', $user_ids)))->index('id')->field('id,username,avatar')->select();
        }
        if ($partner_ids) {
            $partner_info_res = M('partner')->where(array('id' => array('in', $partner_ids)))->index('id')->field('id,title')->select();
        }
        if ($comment_pic_ids) {
            $comment_pic_where = array('order_id' => array('in', $comment_pic_ids), '_string' => "image is not null and image<>'(null)'");
            $comment_image_res = M('comment_pic')->where($comment_pic_where)->field(array('image', 'order_id'))->select();
            foreach ($comment_image_res as &$v) {
                if (!isset($v['image']) || !trim($v['image']) || strpos($v['image'], 'http://') === false) {
                    continue;
                }
                if (!isset($comment_pic_info_res[$v['order_id']])) {
                    $comment_pic_info_res[$v['order_id']] = array();
                }
                $comment_pic_info_res[$v['order_id']][] = $v['image'];
            }
            unset($v);
        }

        if ($res) {
            foreach ($res as &$v) {
                $v['team_product'] = ternary($team_info_res[$v['team_id']]['product'], '');
                $v['username'] = ternary($user_info_res[$v['user_id']]['username'], '');
                $v['partner_title'] = ternary($partner_info_res[$v['partner_id']]['title'], '');
                if (isset($v['is_pic']) && strtolower($v['is_pic']) == 'y') {
                    $images = ternary($comment_pic_info_res[$v['order_id']], array());
                    if($images){
                        $v['images'] = $images;
                    }
                }
                $v['username_hide'] = '';
                if (isset($v['username']) && trim($v['username'])) {
                    if (checkMobile($v['username'])) {
                        $v['username_hide'] = substr($v['username'], 0, 3) . '*****' . substr($v['username'], -3, 4);
                        $v['username']=$v['username_hide'];
                    } 
                    /*else {
                        $v['username_hide'] = cutStr($v['username'], 1, 0, 0) . '**';
                    }*/
                }
                // 用户头像
                $v['user_image'] = '';
                if (isset($user_info_res[$v['user_id']]['avatar']) && trim($user_info_res[$v['user_id']]['avatar'])) {
                    $v['user_image'] = getImagePath($user_info_res[$v['user_id']]['avatar']);
                }
            }
            unset($v);
        }

        // 整合数据
        $data = array(
            'avgNum' => $avgNum,
            'userCount' => $userCount,
            'worstcount' => $worstcount,
            'unreadcount' => $unreadcount,
            'commentNum' => $_commentNum,
            'list' => $res,
        );
        return $data;
    }

    /**
     * 检查订单是否可回复
     * @param $order
     * @param $partner_id
     * @return array|bool
     */
    public function checkIsReply($order, $partner_id) {
        if (is_numeric($order)) {
            $order = M('Order')->info($order);
        }
        //获取团单partner_id
        // $partner = D('Team')->info($order['team_id'], 'partner_id');
        // if($partner === false) {
        //     $this->errorInfo = D('Team')->getErrorInfo();
        //     return false;
        // }
        // if($partner['partner_id'] != $partner_id) {
        //     return array(
        //         'error' => '错误访问！',
        //         'code' => -1,
        //     );
        // }
        $comment = $this->where('order_id=' . $order['id'])->find();
        if ($comment === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        if ($comment['is_comment'] == 'N') {
            return array(
                'error' => '此订单未发布评论，无法回复！',
                'code' => -1
            );
        }

        if ($comment['partner_comment'] != '') {
            return array(
                'error' => '此订单您已经回复，不能重复',
                'code' => -1
            );
        }

        return true;
    }

    /**
     * 发布回复信息
     * @param $order_id
     * @param $content
     * @return bool
     */
    public function addReply($order_id, $content) {    
        $data = array(
            'partner_content'=>$content,
            'comment_time'=>time(),
        );     
        $res = $this->where('order_id=' . $order_id)->setField($data);
        if ($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $res;
    }

}
