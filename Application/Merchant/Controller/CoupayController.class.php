<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class CoupayController extends CommonController {

    public function index() {

        // 接收参数
        $partner_id = I('post.partner_id', 0, 'intval');
        if (!trim($partner_id)) {
            $partner_id = I('get.partner_id', 0, 'intval');
        }
        if (!trim($partner_id)) {
            $partner_id = $this->partner_id;
        }
        $st = I('get.st', '', 'strval');
        $et = I('get.et', '', 'strval');

        // where 查询条件
        $where = array(
            'team.partner_id' => $partner_id,
            'team.team_type'=>array('not in',array('goods','cloud_shopping'))
        );
        if (trim($st) && trim($et)) {
            $where['partner_income.create_time'] = array(array('egt', strtotime($st)), array('lt', strtotime($et)));
        }

        // 分页
        $team = M('team');
        $count = $team->where($where)->field('team.id')
                ->join('left join partner_income on partner_income.team_id=team.id')
                ->group("team.id,FROM_UNIXTIME(partner_income.create_time,'%Y-%m-%d')")
                ->select();
        $count = count($count);
        $Page = $this->pages($count, $this->reqnum);

        // 查询数据
        $field = array(
            'team.id' => 'id',
            'team.product' => 'product',
            'team.begin_time' => 'begin_time',
            'team.end_time' => 'end_time',
            'team.expire_time' => 'expire_time',
            'partner_income.create_time' => 'create_time',
            'count(partner_income.team_id)' => 'num'
        );
        $list = $team->where($where)->field($field)->limit($Page->firstRow . ',' . $Page->listRows)
                ->order("partner_income.create_time desc")->join('left join partner_income on partner_income.team_id=team.id')
                ->group("team.id,FROM_UNIXTIME(partner_income.create_time,'%Y-%m-%d')")
                ->select();

        // 查询分店
        $partner = D('Partner');
        $partner_breach = $partner->getPartnerBranch($this->partner_id);

        $data = array(
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
            'partner_id' => $partner_id,
            'et' => $et,
            'st' => $st,
            'partner_breach' => $partner_breach
        );

        $this->assign($data);
        $this->display('Final/coupay_list');
    }

    // 整理下载数据
    public function download() {

        // 接收参数
        $st = I('get.st', '', 'strval');
        $et = I('get.et', '', 'strval');
        $pay_id = I('get.pay_id', '', 'strval');

        // 查询条件
        $where = array(
            'partner_income.partner_id' => $this->partner_id,
        );
        if (trim($pay_id)) {
            $where['partner_income.pay_id'] = $pay_id;
        }
        if (trim($st) && trim($et)) {
            $where['partner_income.create_time'] = array(array('egt', strtotime($st)), array('lt', strtotime($et) + 86399));
        }
        $field = array(
            'partner_income.create_time' => 'consume_time',
            'partner_income.coupon_id' => 'coupon',
            'team.product' => 'product',
            'team.team_price' => 'team_price',
            'team.ucaii_price' => 'ucaii_price',
        );
        $list = M('partner_income')->field($field)->where($where)->join('inner join team on team.id=partner_income.team_id')->select();
        if ($list) {
            foreach ($list as &$v) {
                $v['num'] = 1;
                $v['margin'] = 0;
                if (isset($v['team_price']) && isset($v['ucaii_price'])) {
                    $v['margin'] = $v['team_price'] - $v['ucaii_price'];
                    $v['margin'] = sprintf("%.2f", $v['margin']);
                }
                if(isset($v['consume_time']) && trim($v['consume_time'])){
                     $v['consume_time'] = date('Y-m-d H:i:s',$v['consume_time']);
                }               
            }
        }

        // 头信息
        $head = array(
            'consume_time' => '消费时间',
            'coupon' => '青团券号',
            'product' => '套餐名称',
            'num' => '消费数量',
            'team_price' => '团购价',
            'margin' => '手续费',
            'ucaii_price' => '结算价'
        );

        // 文件名称
        $fileName = 'coupon_' . $this->partner_id . date('YmdHis');
        partner_down_xls($list, $head, $fileName);
    }

}
