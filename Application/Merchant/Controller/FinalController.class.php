<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class FinalController extends CommonController {

    public function index() {

        // 查询条件
        $where = array(
            'partner_id' => $this->partner_id,
            'is_express' => 'N',
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

        // 查询可提取的总金额
        $nowTime = time();
        $where['pay_id'] = 0;
        $where['create_time'] = array('elt', $nowTime);
        $money = M('partner_income')->where($where)->sum('money');
        if (!trim($money)) {
            $money = '0.00';
        }

        // 商户信息
        $partner = M('partner');
        $field = array('bank_name', 'bank_user', 'bank_no','bank_large_no');
        $partnerRes = $partner->field($field)->where(array('id' => $this->partner_id))->find();
        //$unpay_count = M('partner_pay')->where($where)->where(array('paymark'=>0))->count();
        $data = array(
            'partners' => $partnerRes,
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
            'money' => $money,
        );
        $this->assign($data);
        $this->display('Final/apply_settlement');
    }

    /**
     * 申请结算
     */
    public function paymentApply() {
        $partner = D('Partner');
        $res = $partner->paymentApply($this->partner_id);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '申请提款失败！'));
        }
        $this->ajaxReturn(array('code' => 0,'success'=>'申请提款成功！'));
    }

    /**
     * 邮购结算显示
     */
    public function mailGoodsIndex() {

        // 查询条件
        $where = array(
            'partner_id' => $this->partner_id,
            'is_express' => 'Y',
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

        // 查询可提取的总金额
        $nowTime = time();
        $where['pay_id'] = 0;
        $where['create_time'] = array('elt', $nowTime);
        $money = M('partner_income')->where($where)->sum('money');
        if (!trim($money)) {
            $money = '0.00';
        }

        // 商户信息
        $partner = M('partner');
        $field = array('bank_name', 'bank_user', 'bank_no','bank_large_no');
        $partnerRes = $partner->field($field)->where(array('id' => $this->partner_id))->find();
        $data = array(
            'partners' => $partnerRes,
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
            'money' => $money,
        );
        $this->assign($data);
        $this->display('Final/goods_apply_settlement');
    }

    /**
     * 邮购产品申请结算
     */
    public function mailGoodsPaymentApply() {
        $partner = D('Partner');
        $res = $partner->paymentApply($this->partner_id, 'Y');
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '申请提款失败！'));
        }
        $this->ajaxReturn(array('code' => 0,'success'=>'申请提款成功！'));
    }

    /**
     * 邮购结算明细下载
     */
    public function mailGoodsDownload(){
         $pay_id = I('get.pay_id', '', 'strval');
         
           // 查询条件
        $where = array(
            'partner_income.partner_id' => $this->partner_id,
            'partner_income.is_express' => 'Y',
        );
        if (trim($pay_id)) {
            $where['partner_income.pay_id'] = $pay_id;
        }
        $order_ids = array();
        $order_id_res = M('partner_income')->where($where)->field('coupon_id')->select();
        if($order_id_res){
            foreach($order_id_res as $v){
                $order_ids[$v['coupon_id']]= $v['coupon_id'];
            }
        }
        if(!$order_ids){
            array_push($order_ids, 0);
        }
        $where = array('order.id'=>array('in',  array_keys($order_ids)));
        $field = array(
            'order.id'=>'order_id',
            'order.pay_time'=>'order_pay_time',
            'order.price'=>'order_team_price',
            'order.ucaii_price'=>'order_ucaii_price',
            'order.quantity'=>'order_quantity',
            'order.fare'=>'order_fare',
            'order.optional_model'=>'order_optional_model',
            'team.product'=>'team_product',
        );
        $list = M('order')->where($where)->field($field)->join('inner join team on team.id=order.team_id')->select();
        
        // 数据整理
        if($list){
            foreach($list as &$v){
                
                // 处理购买详情
                $v['order_pay_detail'] = "总共购买{$v['order_quantity']}份";
                $order_optional_model = json_decode(ternary($v['order_optional_model'], ''), true);
                if ($order_optional_model) {
                    $oom_str = '';
                    foreach ($order_optional_model as $oom) {
                        $oom_str .= "{$oom['name']} X {$oom['num']}份; ";
                    }
                    $v['order_pay_detail'] = "{$v['order_pay_detail']}; $oom_str";
                }
                
                // 支付时间
                if(isset($v['order_pay_time']) && $v['order_pay_time']>0){
                    $v['order_pay_time'] = date('Y-m-d H:i:s',$v['order_pay_time']);
                }
                
                // sprintf("%.2f", $v['margin']);
                // 计算团购总价
                $v['order_all_team_price'] = '0.00';
                if(isset($v['order_team_price']) && isset($v['order_quantity']) && isset($v['order_fare'])){
                     $v['order_all_team_price'] = ($v['order_team_price']*$v['order_quantity'])+$v['order_fare'];
                     $v['order_all_team_price'] = sprintf("%.2f", $v['order_all_team_price']);
                }
                
                 // 计算供货总价
                $v['order_all_ucaii_price'] = '0.00';
                if(isset($v['order_ucaii_price']) && isset($v['order_quantity']) && isset($v['order_fare'])){
                     $v['order_all_ucaii_price'] = ($v['order_ucaii_price']*$v['order_quantity'])+$v['order_fare'];
                     $v['order_all_ucaii_price'] = sprintf("%.2f", $v['order_all_ucaii_price']);
                }
                
                // 计算手续费
                $v['margin']='0.00';
                if($v['order_all_ucaii_price']<$v['order_all_team_price'] ){
                     $v['margin'] = sprintf("%.2f", $v['order_all_team_price']-$v['order_all_ucaii_price']);
                }
            }
        }
        
         
          // 头信息
        $head = array(
            'order_id' => '订单id',
            'team_product' => '套餐名称',
            'order_pay_time' => '支付时间',
            'order_team_price' => '团购单价',
            'order_ucaii_price' => '供货单价',
            'order_quantity' => '购买数量',
            'order_pay_detail' => '购买详情',
            'order_fare' => '邮费',
            'order_all_team_price' => '团购总价(团购单价x购买数量+邮费)',
            'order_all_ucaii_price' => '供货总价(供货单价x购买数量+邮费)',
            'margin' => '手续费(团购总价-供货总价)',
        );

        // 文件名称
        $fileName = 'mail_goods_' . $this->partner_id . date('YmdHis');
        partner_down_xls($list, $head, $fileName);
    }
    
    /**
        * 商户保存银行大额行号操作
        */
    public function save_bank_info(){
        $partner_id = trim($this->partner_id);
        $bank_large_no = I('post.bank_large_no','','trim');
        
        if(!$partner_id){
            $this->ajaxReturn(array('code' => -1, 'error' => '商家登录超时，请重新登录！'));
        }
        
        if(!$bank_large_no){
            $this->ajaxReturn(array('code' => -1, 'error' => '商家银行大额行号不能为空，请重新设置！'));
        }
        
        $res = M('partner')->where(array('id'=>$partner_id))->save(array('bank_large_no'=>$bank_large_no));
        if($res === false){
            $this->ajaxReturn(array('code' => -1, 'error' => '大额行号设置失败！'));
        }
        
        $this->ajaxReturn(array('code' => 0, 'success' => '大额行号设置成功！'));
    }

    /**
     * 优惠买单结算显示
     */
    public function discountIndex() {

        // 查询条件
        $where = array(
            'partner_id' => $this->partner_id,
            'is_express' => 'D'
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

        // 查询可提取的总金额
        $nowTime = time();
        unset($where['is_express']);
        $where['state'] = 'pay';
        $where['payed_id'] = 0;
        $where['create_time'] = array('elt', $nowTime);
        $model = M('discountOrder');
        $amoneysql = $model->field('(origin - round(origin * aratio, 2)) as amoney')->where($where)->buildSql();
        $money = $model->table($amoneysql .' x')->sum('amoney');
        if (!trim($money)) {
            $money = '0.00';
        }

        // 商户信息
        $partner = M('partner');
        $field = array('bank_name', 'bank_user', 'bank_no','bank_large_no');
        $partnerRes = $partner->field($field)->where(array('id' => $this->partner_id))->find();
        $data = array(
            'partners' => $partnerRes,
            'count' => $count,
            'page' => $Page->show(),
            'list' => $list,
            'money' => $money,
        );
        $this->assign($data);
        $this->display('Final/discount_apply_settlement');
    }

    /**
     * 优惠买单申请结算
     */
    public function discountPaymentApply() {
        $discount = D('Discount');
        $res = $discount->paymentApply($this->partner_id);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '申请提款失败！'));
        }
        $this->ajaxReturn(array('code' => 0,'success'=>'申请提款成功！'));
    }

}
