<?php

/**
 * Created by JetBrains PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 15-3-18
 * Time: 上午14:11
 * To change this template use File | Settings | File Templates.
 */

namespace Manage\Controller;

use Manage\Controller\CommonController;

/**
 * 用户控制器
 * Class UserController
 * @package Manage\Controller
 */
class UserController extends CommonController {

    /**
     *  	获取用户列表
     */
    public function index() {
        $Model = D('User');
        // 初始化是否需要查询
        $select_state = true;
        $paramArray = array(
            array('id', '', '', 'u'),
            array('username', '', 'like', 'u'),
            array('mobile', '', '', 'u'),
        );
        $where = $this->createSearchWhere($paramArray);
        $displayWhere = $this->getSearchParam($paramArray);
        $where['u.city_id'] = $this->_getCityId();
        //+++ ：待createSearchWhere完善后可优化
        // 筛选购买次数大于 {$buy_num} 次的用户
        $buy_num = trim(I('get.buy_num', 0, 'intval'));
        $Order = D('Order');
        if ($buy_num) {
            $user_ids = $Order->getUserBuy(array('city_id' => $this->_getCityId(), 'state' => 'pay'), "count(user_id) > {$buy_num}");
            if ($user_ids === false) {
                $this->_writeDBErrorLog($user_ids, $Order);
            }
            $where['u.id'] = array('in', $user_ids);
            $displayWhere['buy_num'] = $buy_num;
            $select_state = $user_ids ? true : false;
        }

        // 筛选购买余额大于 {$money} 元的用户
        $money = trim(I('get.money', 0));
        if ($money) {
            $where['u.money'] = array('gt', $money);
            $displayWhere['money'] = $money;
        }
        $this->_getData($Model, $where, $select_state);
        $this->assign('displayWhere', $displayWhere);
        $this->display();
    }

    /**
     *  	获取新注册用户列表
     */
    public function newUser() {
        $Model = D('User');

        $where['u.city_id'] = $this->_getCityId();
        // 筛选注册时间在 {$start_time} 至 {$end_time} 之间的用户
        $start_time = I('get.start_time', date('Y-m-d'), 'strval');
        $end_time = I('get.end_time', date('Y-m-d', strtotime("+1 day")), 'strval');
        $where['create_time'] = array('between', array(strtotime($start_time), strtotime($end_time)));
        $this->_getData($Model, $where, true);
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->display();
    }

    /**
     *  获取符合购买条件的用户
     */
    public function getUserBuy() {
        $Model = D('User');
        $Order = D('Order');
        // 初始化是否需要查询
        $select_state = true;

        $where['u.city_id'] = $this->_getCityId();
        // 筛选购买购买时间在 {$start_paytime} 至 {$end_paytime} 之间的用户
        $start_paytime = I('get.start_paytime', 0, 'strval');
        $end_paytime = I('get.end_paytime', 0, 'strval');
        // 筛选购买购买金额大于 {$buy_money} 元的用户
        $buy_money = I('get.buy_money', 0, 'intval');
        $where_buy['city_id'] = $this->_getCityId();
        $where_buy['state'] = 'pay';
        if ($start_paytime && $end_paytime && $buy_money) {
            $where_buy['pay_time'] = array('between', array(strtotime($start_paytime), strtotime($end_paytime)));
            $user_ids = $Order->getUserBuy($where_buy, "sum('origin') > {$buy_money}");
            if ($user_ids === false) {
                //记录错误日志
                $this->_writeDBErrorLog($user_ids, $Order);
            }
            $where['u.id'] = array('in', $user_ids);
            $select_state = $user_ids ? true : false;
            $this->assign('start_paytime', $start_paytime);
            $this->assign('end_paytime', $end_paytime);
            $this->assign('buy_money', $buy_money);
        } elseif ($buy_money) {
            $user_ids = $Order->getUserBuy($where_buy, "sum(origin) > {$buy_money}");
            if ($user_ids === false) {
                //记录错误日志
                $this->_writeDBErrorLog($user_ids, $Order);
            }
            $where['u.id'] = array('in', $user_ids);
            $select_state = $user_ids ? true : false;
            $this->assign('buy_money', $buy_money);
        } elseif ($start_paytime && $end_paytime) {
            $where_buy['pay_time'] = array('between', array(strtotime($start_paytime), strtotime($end_paytime)));
            $user_ids = $Order->getUserBuy($where_buy);
            if ($user_ids === false) {
                //记录错误日志
                $this->_writeDBErrorLog($user_ids, $Order);
            }
            $where['u.id'] = array('in', $user_ids);
            $select_state = $user_ids ? true : false;
            $this->assign('start_paytime', $start_paytime);
            $this->assign('end_paytime', $end_paytime);
        }
        $this->_getData($Model, $where, $select_state);
        $this->display();
    }

    /**
     * 	获取数据
     */
    protected function _getData($Model, $where, $select_state) {
        //判断是否需要查询
        if ($select_state) {
            $where_count = $this->_createCountWhere($where);
            $count = $Model->getTotal($where_count);

            //记录错误日志
            $this->_writeDBErrorLog($count, $Model);

            $page = $this->pages($count, $this->reqnum);
            $limit = $page->firstRow . ',' . $page->listRows;
            $this->assign('count', $count);
            $this->assign('pages', $page->show());
            $field = 'u.id,u.email,u.username,u.mobile,u.create_time,c.name,u.money,u.manager';
            $data = $Model->getUsers($where, $limit, 'id DESC', $field);
            $this->_writeDBErrorLog($data, $Model);
        }
        $this->assign('data', $data);
    }

    /**
     * 	获取用户详情
     */
    public function getUserInfo() {
        $paramArray = array(
            array('id', ''),
        );
        $where = $this->createSearchWhere($paramArray);
        if ($where) {
            $Model = D('User');
            $field = 'email,username,mobile,create_time,money,score';
            $data = $Model->getDetail($where, $field);
            $this->_writeDBErrorLog($data, $Model);
            $this->assign('data', $data);
        } else {
            $this->assign('error', '获取用户详情失败');
        }
        $this->display();
    }

    /**
     *  	获取用户交易明细
     */
    public function getUserTransaction() {
        $user_id = I('get.user_id', 0, 'intval');
        if ($user_id) {
            $where['o.user_id'] = $user_id;
            $where['o.state'] = 'pay';
            $Model = D('order');
            $where['state'] = 'pay';
            $field = 'o.team_id,o.pay_id,o.quantity,o.pay_time,o.money,o.credit';
            $count = $Model->getTotal($where);
            if ($count === false) {
                //TODO 错误日志
                $this->_writeDBErrorLog($count, $Model);
            }
            $page = $this->pages($count, $this->reqnum);
            $limit = $page->firstRow . ',' . $page->listRows;
            $this->assign('pages', $page->show());
            $data = $Model->getUserTransaction($where, $limit, 'pay_time DESC', $field);
            if ($data === false) {
                //TODO 错误日志
                $this->_writeDBErrorLog($data, $Model);
            }
            $this->assign('data', $data);
        } else {
            $this->assign('error', '获取用户交易明细失败');
        }
        $this->display();
    }

    /**
     *  获取用户交易流水
     */
    public function getUserFlow() {
        $user_id = I('get.user_id', 0, 'intval');
        if ($user_id) {
            $Model = D('Flow');
            $where['user_id'] = $user_id;
            $field = 'create_time,direction,money,action,money,team_id,detail_id';
            $count = $Model->getTotal($where);
            $this->_writeDBErrorLog($count, $Model);
            $page = $this->pages($count, $this->popup_reqnum, '', 5);
            $limit = $page->firstRow . ',' . $page->listRows;
            $this->assign('pages', $page->show());
            $this->assign('count', $count);
            $data = $Model->getUserFlow($where, $limit, 'create_time DESC', $field);
            if ($data === false) {
                //TODO 错误日志
                $this->_writeDBErrorLog($data, $Model);
            }
            $this->assign('data', $data);
        } else {
            $this->assign('error', '获取用户交易流水失败！');
        }
        $this->display();
    }

    /**
     *  删除用户
     */
    public function delUser() {
        $user_id = I('get.id', 0, 'intval');
        if ($user_id) {
            $state = M('order')->where(array('user_id' => $user_id, 'state' => 'pay'))->count('id');
            if ($state === false) {
                //TODO 错误日志
                $this->_writeDBErrorLog($state, M('order'));
            }
            if ($state) {
                $this->error('该用户已经产生付款订单不能删除');
            } else {
                $rs = D('User')->delete($user_id);
                if ($rs === false) {
                    //TODO 错误日志
                    $this->_writeDBErrorLog($rs, D('user'));
                    $this->error('删除失败');
                } else {
                    $this->success('删除成功');
                }
            }
        }
    }

    /**
     *  修改用户信息
     */
    public function editUser() {
        //TODO　：管理员后台完善此功能
    }

    /**
     *  获取业务员列表
     */
    public function getSalesman() {
        $Model = M('bd_user');
        //$where['city_id'] = 102;
        $paramArray = array(
            array('db_name', '', 'like'),
            array('db_phone', ''),
        );
        $data = $this->_getList($Model, $paramArray);
        if ($data === false) {
            //TODO 错误日志
        }
        $this->display();
    }

    /**
     * 给用户开通登录权限
     */
    public function openLoginAuth() {
        $o_uid = I('get.uid', '', 'trim');
        if (!$o_uid) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户id不能为空！'));
        }

        $admin_uid = $this->_getUserId();
        $city_id = $this->_getCityId();
        if (strcmp($admin_uid, $o_uid) === 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户不能修改自身权限！'));
        }
        $user = M('user');
        $userRes = $user->where(array('id' => $o_uid, 'city_id' => $city_id))->find();
        if (!$userRes || $userRes <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '该用户不属于该城市下的用户！'));
        }
        if (isset($userRes['manager']) && strtolower(trim($userRes['manager'])) == 'p') {
            $this->ajaxReturn(array('code' => 0));
        }
        $res = $user->where(array('id' => $o_uid, 'city_id' => $city_id))->save(array('manager' => 'P', 'fagent_id' => $admin_uid));
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '开通权限失败！'));
        }
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 给用户授权
     */
    public function doUserAuth() {
        $user_id = I('get.uid', '', 'trim');
        $operation_type = I('post.operation_type', '', 'trim');
        if (!$operation_type) {
            if (!$user_id) {
                redirect(U("User/index"));
            }
            $userRes = M('user')->where(array('id' => $user_id))->field('id')->find();
            if (!$userRes) {
                redirect(U("User/index"));
            }
            $this->assign($userRes);
            $module_name = strtolower(MODULE_NAME);
            $auth_group_list = $this->getAuthGroupList($module_name, $user_id);
            // 获取城市
            $data = array(
                'auth_group_list' => $auth_group_list,
                'operation_type' => 'doUserAuth',
                'module_name' => $module_name,
            );
            $this->assign($data);
            $this->display('User/doAuth');
            exit;
        }

        $user = I('post.user', array(), '');
        if (!$user_id) {
            $user_id = ternary($user['id'], '');
        }
        if (!$user_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户id不能为空！'));
        }
        if (!isset($user['module_name']) || trim($user['module_name']) == '') {
            $this->ajaxReturn(array('code' => -1, 'error' => '权限组名称不能为空！'));
        }
        if (!isset($user['group_id']) || !$user['group_id']) {
            $user['group_id'] = array();
        }
        if (is_string($user['group_id'])) {
            $user['group_id'] = @explode(',', $user['group_id']);
        }
        $auth_group_access = M('auth_group_access');
        $module_name = strtolower($user['module_name']);
        $data = array();
        foreach ($user['group_id'] as $v) {
            $data[] = array(
                'module_name' => $module_name,
                'uid' => $user_id,
                'group_id' => trim($v),
            );
        }
        // 权限修改开启事务
        $model = M();
        $model->startTrans();
        $where = array(
            'uid' => $user_id,
            'module_name' => $module_name
        );
        $res = $auth_group_access->where($where)->count();
        if ($res && $res > 0) {
            $res = $auth_group_access->where($where)->delete();
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '授权失败！'));
            }
        }
        if ($data) {
            $res = $auth_group_access->addAll($data);
            if (!$res) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '授权失败！'));
            }
        }
        $model->commit();
        $this->ajaxReturn(array('code' => 0));
    }

}
