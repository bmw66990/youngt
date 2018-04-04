<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class PartnerController extends CommonController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 商家详情首页
     */
    public function index() {
        $partner = D('Partner');
        $partnerDetail = $partner->getPartnerDetail($this->partner_id);

        $data = array(
            'partner' => $partnerDetail
        );
        $this->assign($data);

        $this->display();
    }

    /**
     * 更新商家信息
     */
    public function update() {

        // 接收参数
        $oldPassword = I('post.oldpassword', '', 'strval');
        $newPassword = I('post.newpassword', '', 'strval');
        $cnewPassword = I('post.cnewpassword', '', 'strval');
        $contact = I('post.contact', '', 'strval');
        $mobile = I('post.mobile', '', 'strval');

        if (!isset($this->partner_id)) {
            $this->ajaxReturn(array('code' => 0, 'error' => '未登录'));
        }
        if (trim($mobile) && !checkMobile($mobile)) {
            $this->ajaxReturn(array('code' => 0, 'error' => '非法手机号码'));
        }

        $partner = M('Partner');
        $partnerRes = $partner->where(array('id' => $this->partner_id))->field('id,password')->find();
        $data = array();
        if (trim($oldPassword)) {
            // 校验老密码是否正确
            if (!isset($partnerRes['password']) || trim($partnerRes['password']) != encryptPwd(trim($oldPassword))) {
                $this->ajaxReturn(array('code' => -1, 'error' => '旧密码错误！'));
            }
            $length = strlen($newPassword);
            if ($length > 18 || $length < 6) {
                $this->ajaxReturn(array('code' => -1, 'error' => '新密码必须大于6位，小于18位！'));
            }

            if (!trim($newPassword) || !trim($cnewPassword)) {
                $this->ajaxReturn(array('code' => -1, 'error' => '新密码不能为空！'));
            }

            if (trim($newPassword) != trim($cnewPassword)) {
                $this->ajaxReturn(array('code' => -1, 'error' => '两次新密码输入不一致！'));
            }
            $data['password'] = encryptPwd(trim($newPassword));
        }

        if (trim($contact)) {
            $data['contact'] = $contact;
        }

        if (trim($mobile)) {
            $data['mobile'] = $mobile;
        }

        if (!$data) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请填写后再修改！'));
        }

        // 修改
        $res = $partner->where(array('id' => $this->partner_id))->save($data);
        if ($res === false) {
            $this->ajaxReturn(array('code' => -1, 'error' => '修改资料失败！'));
        }

        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 用户修改
     */
    public function editPwd() {
        $show = I('get.show', '', 'strval');
        if (trim($show)) {
            $this->display();
            exit;
        }

        // 接收参数
        $oldPassword = I('post.oldpassword', '', 'strval');
        $newPassword = I('post.newpassword', '', 'strval');
        $cnewPassword = I('post.cnewpassword', '', 'strval');

        // 非法参数判断
        if (!isset($this->partner_id)) {
            $this->ajaxReturn(array('code' => 0, 'error' => '未登录'));
        }
        if (!trim($oldPassword)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '旧密码不能为空！'));
        }
        if (!trim($newPassword) || !trim($cnewPassword)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '新密码不能为空！'));
        }
        $length = strlen($newPassword);
        if ($length > $this->pwd_max_length || $length < $this->pwd_min_length) {
            $this->ajaxReturn(array('code' => -1, 'error' => "新密码必须大于{$this->pwd_min_length}位，小于{$this->pwd_max_length}位！"));
        }
        if (trim($newPassword) != trim($cnewPassword)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '两次新密码输入不一致！'));
        }

        $partner = M('Partner');
        $login_access = M('login_access');
        $loginAccessRes = $login_access->where(array('id' => $this->uid))->find();
        if (!$loginAccessRes) {
            $this->ajaxReturn(array('code' => 0, 'error' => '未登录'));
        }
        if (!isset($loginAccessRes['password']) || trim($loginAccessRes['password']) != encryptPwd(trim($oldPassword))) {
            $this->ajaxReturn(array('code' => -1, 'error' => '旧密码错误！'));
        }

        // 开启事务
        $model = M();
        $model->startTrans();
        $res = $login_access->where(array('id' => $this->uid))->save(array('password' => encryptPwd(trim($newPassword))));
        if ($res === false) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '密码修改失败！'));
        }
        if (isset($loginAccessRes['is_super_admin']) && strtolower(trim($loginAccessRes['is_super_admin'])) == 'y') {
            $res = $partner->where(array('id' => $this->partner_id))->save(array('password' => encryptPwd(trim($newPassword))));
            if ($res === false) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '密码修改失败！'));
            }
        }
        $model->commit();
        
        $this->ajaxReturn(array('code' => 0));
    }

}
