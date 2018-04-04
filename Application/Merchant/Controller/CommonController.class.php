<?php

/**
 * Created by JetBrains PhpStorm.
 * User: runtoad
 * Date: 15-3-9
 * Time: 上午11:42
 * To change this template use File | Settings | File Templates.
 */

namespace Merchant\Controller;

use Common\Controller\CommonBusinessController;

class CommonController extends CommonBusinessController {

    /**
     * 商户id
     */
    protected $partner_id = '';
    protected $uid = '';
    protected $partner = null;
    private $auth = null;
    private $auth_config = null;
    protected $pwd_min_length = 6;
    protected $pwd_max_length = 18;

    /**
     * 普通分页大小
     */
    protected $reqnum = 10;

    /**
     * 弹出层分页大小
     */
    protected $popup_reqnum = 10;

    /**
     * 初始化函数
     */
    function _initialize() {
        $this->auth = new \Common\Org\Auth(strtolower(MODULE_NAME));
        $this->auth_config = C('AUTH_CONFIG');

        // 登录权限认证
        $this->__auth();

        // 权限点自动注册
        $this->_register($this);
    }

    /**
     * @return bool  用户权限验证
     */
    private function __auth() {
        // 获取当前用户ID
        $this->partner = session(C('USER_AUTH_KEY'));
        if (is_null($this->partner)) {
            //跳转到认证网关

            redirect(U(C('USER_AUTH_GATEWAY')));
        }
        $this->partner_id = ternary($this->partner['id'], '');
        $this->uid = ternary($this->partner['login_access_id'], '');
        // 是否
        if (!isset($this->partner['is_goods'])) {
            $now_time = time();
            $this->partner['is_goods'] = 0;
            $goods_where = array(
                'partner_id' => $this->_getPartnerByid($this->partner_id),
                'team_type'=>'goods'
            );
            $isGoods = M('team')->where($goods_where)->count();
            if($isGoods && $isGoods>0){
                $this->partner['is_goods'] = 1;
            }
            session(C('USER_AUTH_KEY'), $this->partner);
        }

        $this->assign('partners_info', $this->partner);
        if (!$this->partner_id || !$this->uid) {
            //跳转到认证网关
            redirect(U(C('USER_AUTH_GATEWAY')));
        }

        if (isset($this->partner['login_access_is_super_admin']) && trim($this->partner['login_access_is_super_admin']) == 'Y') {
            return true;
        }

        $res = $this->auth->auth_check_access($this->uid, $this->auth_config);
        if (!$res) {
            if (IS_AJAX) {
                $this->ajaxReturn(array('error' => '无权限该操作', 'info' => '无权限该操作', 'code' => -1, 'status' => -1));
            }
            redirect(U('Index/index'));
        }
    }

    protected function _register($class_name) {
        if (isset($this->auth_config['OPEN_AUTH_RULE_REGISTER']) && $this->auth_config['OPEN_AUTH_RULE_REGISTER']) {
            $this->auth->register($class_name);
        }
    }

    /**
     * 获取查询时 的商户id查询条件
     */
    protected function _getPartnerIdWhere($partnerId = '') {
        if (!trim($partnerId)) {
            return false;
        }
        $partner = M('partner');

        $res = $partner->where(array('id' => $partnerId))->getField('is_branch');
        if (!$res || strtolower(trim($res)) == 'n') {
            return $partnerId;
        }

        $partnerRes = $partner->where(array('fid' => $partnerId))->field('id')->select();
        $where = array();
        if ($partnerRes) {
            foreach ($partnerRes as $v) {
                $where[] = $v['id'];
            }
        }
        if (!$where) {
            return $partnerId;
        }
        return array('in', $where);
    }

    /**
     * 根据商户id 获取该商户总分店的id
     * @param type $partnerId
     */
    protected function _getPartnerByid($partnerId, $isBranch = false) {
        if (!trim($partnerId)) {
            return false;
        }

        $data = D('Team')->getParnerAllBranchList($partnerId);
        if ($data) {
            $partnerIds = array();
            foreach ($data as $v) {
                if (isset($v['partner_id']) && $v['partner_id']) {
                    $partnerIds[] = $v['partner_id'];
                }
            }
            if ($partnerIds) {
                return array('in', $partnerIds);
            }
        }
        return false;

    }

}
