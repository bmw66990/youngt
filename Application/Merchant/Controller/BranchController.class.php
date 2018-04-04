<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class BranchController extends CommonController {

    public function index() {

        $st = I('post.st', '', 'strval');
        $et = I('post.et', '', 'strval');

        // 获取分店列表
        $partner = D('Partner');
        $list = $partner->getPartnerBranch($this->partner_id);

        // 获取结账总额
        $pid = array();
        foreach ($list as $v) {
            if (isset($v['id'])) {
                $pid[] = $v['id'];
            }
        }
        $where = array('partner_id' => array('in', $pid));
        if (trim($st) && trim($et)) {
            $where['create_time'] = array(array('egt', strtotime($st)), array('lt', strtotime($et) + 86399));
        }
        $field = array('partner_id' => 'partner_id', 'sum(money)' => 'sum_money');
        $moneyRes = M('partner_income')->where($where)->group('partner_id')->getField('partner_id,sum(money) as sum_money');
        foreach ($list as &$_v) {
            $_v['money'] = 0;
            if (isset($moneyRes[$_v['id']]) && $moneyRes[$_v['id']]) {
                $_v['money'] = $moneyRes[$_v['id']];
            }
        }

        $data = array(
            'st' => $st,
            'et' => $et,
            'list' => $list,
        );

        $this->assign($data);
        $this->display('Partner/branch_list');
    }

    /**
     * 分店明细
     */
    public function detail() {
        $partner_id = I('get.partner_id',0,'intval');

        // 查询条件
        $where = array(
            'partner_id' => $partner_id,
        );

        // 查询结账纪录
        $partnerPay = M('partner_pay');
        $count = $partnerPay->where($where)->count();
        $Page = $this->pages($count, $this->reqnum);
        $field = array('id', 'pay_time', 'money', 'create_time');
        $list = $partnerPay->where($where)->field($field)
                ->order('create_time desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        
        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
        );

        $this->assign($data);
        $this->display('Partner/branch_detail');
    }

}
