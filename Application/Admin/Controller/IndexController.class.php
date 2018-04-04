<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/8
 * Time: 15:26
 */

namespace Admin\Controller;

/**
 * 首页控制器
 * Class IndexController
 * @package Admin\Controller
 */
class IndexController extends CommonController {

    /**
     * 首页
     */
    public function index() {
        //获取最新的10条内部新闻
        $data = M('news')->field('id,title,begin_time')->where("type=0")->order('id desc')->limit(10)->select();
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 异步获取统计数量
     */
    public function ajaxTotal() {
        if (IS_AJAX) {
            $time = strtotime(date('Y-m-d'));
            $where = array(
                'newUser' => array('create_time' => array('egt', $time)),
                'newOrder' => array('create_time' => array('egt', $time)),
                'newCoupon' => array('consume_time' => array('egt', $time)),
                'newCloseTeam' => array('end_time' => array('between', array($time, time()))),
                'newRefundOrder' => array('allowrefund' => 'Y', 'rstate' => 'askrefund','state'=>'pay','team_id'=>array('gt',0)),
            );
            $dataTotal['newUserTotal'] = ternary(D('User')->getTotal($where['newUser']), 0);
            $dataTotal['newOrderTotal'] = ternary(D('Order')->getTotal($where['newOrder']), 0);
            $dataTotal['newCouponTotal'] = ternary(D('Coupon')->getTotal($where['newCoupon']), 0);
            $dataTotal['newCloseTeamTotal'] = ternary(D('Team')->getTotal($where['newCloseTeam']), 0);
            $dataTotal['newRefundTotal'] = ternary(D('Order')->getTotal($where['newRefundOrder']), 0);
            $data = array('status' => 1, 'data' => $dataTotal);
        } else {
            $data = array('status' => -1, 'error' => '非法操作');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 签到
     */
    public function daySign() {
        $param = array('error' => base64_encode('请到钉钉签到'));
        $this->redirect_message(U('Index/index'), $param);

        //判断是否已经向签到表添加过数据
        $is_daySign = S('daySign');
        if (!$is_daySign) {
            $res = $this->_addDaySign();
            if ($res === false) {
                redirect(U('Index/index'));
            }
        }
        $day = date('Y-m-d');
        $data = M('qd')->where(array('day' => $day))->order('id desc')->select();
        foreach ($data as &$val) {
            if ($val['result'] == '今天未签到') {
                $val['result_data'] = $val['result'];
            } else {
                if ($val['result'] >= 60) {
                    $val['result_data'] = '早到' . $this->_NewTime($val['result']);
                } elseif (abs($val['result']) < 60 ) {
                    $val['result_data'] = '准时签到';
                } elseif ($val['result'] <= -60) {
                    $val['result_data'] = '迟到' . $this->_NewTime(abs($val['result']));
                }
            }
        }
        $user = $this->user;
        $count = M('qd')->where(array('day' => $day, 'uname' => $user['realname'], 'time > 0'))->count('id');
        $mouth = date('Y-m');
        $late_count = M('qd')->where(array('day' => array('like', "{$mouth}%"), 'uname' => $user['realname'], 'result'=>array('elt',-60)))->count('id');
        $state = array('is_daySign' => 0, 'uname' => $user['realname'], 'late_count' => ternary($late_count, 0));
        if ($count == 0) {
            $state ['is_daySign'] = 1;
        }
        $this->assign('user', $state);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 时间转换
     */
    protected function _NewTime($val) {
        if ($val >= 3600) {
            $hour = floor($val / 3600);
            $minute = floor($val % 3600 / 60);
            $second = floor($val % 3600 % 60);
        } elseif ($val >= 60) {
            $minute = floor($val / 60);
            $second = floor($val % 3600 % 60);
        } else {
            $second = $val;
        }
        if (isset($hour) && isset($minute) && isset($second)) {
            return $hour . '时' . $minute . '分' . $second . '秒';
        } else if (isset($minute) && isset($second)) {
            return $minute . '分' . $second . '秒';
        } else {
            return $second . '秒';
        }
    }

    /**
     * 向签到表添加数据
     */
    protected function _addDaySign() {
        $user = $this->user;
        $day = date('Y-m-d');
        $endtime = mktime(8, 40, 0);
        $user_where = array('uname' => $user['realname'], 'day' => $day);
        $count = M('qd')->where($user_where)->count('id');
        if ($count == 0) {
            $data = D('User')->getList(array('manager' => 'Y'), 'id desc', '', 'realname');
            if ($data) {
                $qdData = array();
                foreach ($data as $val) {
                    $qdData[] = array(
                        'uname' => $val['realname'] ? $val['realname'] : '未知用户名',
                        'time' => 0,
                        'day' => $day,
                        'endtime' => $endtime,
                        'result' => '今天未签到',
                        'ip' => '',
                    );
                }
                $res = M('qd')->addAll($qdData);
                if ($res) {
                    S('daySign', '1', 15 * 3600);
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * ajax异步签到处理
     */
    public function ajaxDaySign() {
        $data = array('status' => -1, 'error' => '请到钉钉！');
        $this->ajaxReturn($data);
        if (IS_AJAX) {
            if (is_mobile()) {
                $data = array('status' => -1, 'error' => '您的客户端是手机，不能签到！');
            } else {
                $Model = M('qd');
                $user = $this->user;
                $day = date('Y-m-d');
                $endtime = mktime(8, 40, 0);
                $count = $Model->where(array('uname' => $user['realname'], 'day' => $day, 'time>0'))->count();
                if ($count == 1) {
                    $data = array('status' => -1, 'error' => '您已经签过到了，请勿重复签到！');
                } else {
                    $result = $endtime - time();
                    if ($result > 2400) {
                        $data = array('status' => -1, 'error' => '请八点以后再来签到！');
                    } else {
                        if(abs($result) > 0 && abs($result) <= 60 ){
                            $result = 0;
                        }
                        $where = array('uname' => $user['realname'], 'day' => $day);
                        $save_data = array('ip' => get_client_ip(), 'time' => time(), 'result' => $result);
                        $res = $Model->where($where)->save($save_data);
                        if ($res) {
                            $this->addOperationLogs("操作：管理员签到,签到管理员id:{$this->user['id']},签到管理员名称:{$this->user['username']}");
                            $data = array('status' => 1, 'success' => '签到成功');
                        } else {
                            $data = array('status' => -1, 'error' => '签到失败');
                        }
                    }
                }
            }
        } else {
            $data = array('status' => -1, 'error' => '非法操作');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 市场反馈
     */
    public function feedback() {
        $Model = D('Feedback');
        $count_paramArray = array(
            array('content', '', 'like'),
            array('category', '0', ''),
        );
        $count_where = $this->createSearchWhere($count_paramArray);
        $count = $Model->getTotal($count_where);
        $this->_writeDBErrorLog($count, $Model);

        $paramArray = array(
            array('content', '', 'like', 'f'),
            array('category', '0', '', 'f'),
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $page = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $field = 'f.id,f.category,f.title as username,f.create_time,f.content,f.contact as mobile,f.address,f.phone as qq,u.username as name,f.user_id';
        $data = $Model->getAllList($where, 'f.id desc', $limit, $field);
        if ($data === false) {
            //TODO 错误日志
            $this->_writeDBErrorLog($data, $Model);
        }
        $feedcate = array('suggest' => '意见反馈', 'seller' => '商务反馈', 'sms' => '短信反馈', 'zhaoshang' => '招商加盟');
        $this->assign('feedback', $feedcate);
        $this->assign('displayWhere', $displayWhere);
        $this->assign('data', $data);
        $this->assign('pages', $page->show());
        $this->display();
    }

    /**
     * 删除反馈
     */
    public function delFeedback() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $res = D('Feedback')->delete($id);
            if ($res) {
                $this->addOperationLogs("操作：删除市场反馈,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},反馈id:{$id}");
                $param = array('success' => base64_encode('删除成功'));
            } else {
                $param = array('error' => base64_encode('删除失败'));
            }
        } else {
            $param = array('error' => base64_encode('请求参数非法'));
        }
        $this->redirect_message(U('Index/feedback'), $param);
    }

    /**
     * 处理反馈
     */
    public function saveFeedback() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $user = $this->user;
            $save_data = array('id' => $id, 'user_id' => $user['id']);
            $res = D('Feedback')->save($save_data);
            if ($res) {
                $this->addOperationLogs("操作：处理市场反馈,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},反馈id:{$id}");
                $param = array('code'=>0, 'msg' => '处理成功');
            } else {
                $param = array('code'=>1, 'msg' => '处理失败');
            }
        } else {
            $param = array('code'=>1, 'msg' => '请求参数非法');
        }
        $this->ajaxReturn($param);
        // $this->redirect_message(U('Index/feedback'), $param);
    }

    /**
     * 点评列表
     */
    public function comment() {
        $action = I('get.action', '', 'trim');
        $Model = D('Comment');
        $select = true;
        $data = array();
        $count = 0;
        $paramArray = array(
            array('team_id', '', '', 'c'),
            array('partner_id', '', '', 'c'),
            array('content', '', 'like', 'c'),
            array('comment_num', 0, '', 'c'),
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $account = I('get.account', '', 'strval');
        if ($account) {
            $user_ids = array();
            $user_idArr = M('user')->field('id')->where(array('username|mobile|email' => $account))->select();
            if ($user_idArr) {
                foreach ($user_idArr as $val)
                    $user_ids[] = $val['id'];
                $where['c.user_id'] = array('in', $user_ids);
            } else {
                $select = false;
            }
            $displayWhere['account'] = $account;
        }
        if ($select === true) {
            $where['c.is_comment'] = 'Y';
            $where_count = $this->_createCountWhere($where);
            $count = $Model->getTotal($where_count);
            $this->_writeDBErrorLog($count, $Model);
            $page = $this->pages($count, $this->reqnum, '', 7);
            $limit = $page->firstRow . ',' . $page->listRows;
            $this->assign('pages', $page->show());
            $field = 'c.id,c.team_id,c.user_id,t.city_id,c.content,c.comment_num,c.create_time,t.product,u.username,u.email,c.comment_display,u.mobile';
            $data = $Model->getComments($where, $limit, 'id desc' .
                '', $field);
            $this->_writeDBErrorLog($data, $Model);
            if ($data) {
                $city = $this->_getCategoryList('city');
                foreach ($data as &$row) {
                    $row['city_name'] = $city[$row['city_id']]['name'];
                }
            }
        }
        $grade = array('1' => '很不满意', '2' => '不满意', '3' => '一般', '4' => '满意', '5' => '很满意');
        $this->assign('grade', $grade);
        $this->assign('data', $data);
        $this->assign('displayWhere', $displayWhere);
        if (strtolower($action) == 'customerservice') {
            $this->display('CustomerService/comment');
            exit;
        }
        $this->display();
    }

    /**
     * 屏蔽评论
     */
    public function commentDisplay() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $Model = D('Comment');
            $info = $Model->info($id);
            if ($info && $info['comment_display'] == 'Y') {
                $uparr = array('id' => $id, 'comment_display' => 'N');
                $res = $Model->save($uparr);
                if ($res === false) {
                    //TODO 记录错误日志
                    $this->_writeDBErrorLog($res, $Model);
                    $param = array('error' => base64_encode('屏蔽失败'));
                } else {
                    $this->addOperationLogs("操作：屏蔽用户评论,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},评论id:{$id}");
                    $param = array('success' => base64_encode('屏蔽成功'));
                }
            } else {
                $param = array('error' => base64_encode('评论已被删除或已屏蔽'));
            }
        } else {
            $param = array('error' => base64_encode('请求参数非法'));
        }
        $this->redirect_message(U('Index/comment'), $param);
    }

    /**
     * 删除评论
     */
    public function delComment() {
        $id = I('get.id', 0, 'intval');
        if ($id) {
            $res = D('Comment')->delete($id);
            if ($res) {
                $this->addOperationLogs("操作：删除评论,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},评论id:{$id}");
                $param = array('success' => base64_encode('删除成功'));
            } else {
                $param = array('error' => base64_encode('删除失败'));
            }
        } else {
            $param = array('error' => base64_encode('请求参数非法'));
        }
        $this->redirect_message(U('Index/comment'), $param);
    }

}
