<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;

/**
 * 财务模块
 * Class TeamController
 * @package Admin\Controller
 */
class FinancialController extends CommonController {

    private $option_state = array(
        'Y' => '已消费',
        'N' => '未消费',
        'E' => '已过期',
    );
    private $team_state = array(
        'normal' => '正常团单',
        'lottery' => '活动团单'
    );
    private $youngt_bank = array(
        '0' => '未知卡号',
        '1' => '青团平安账号',
        '2' => '农行账号尾号686',
        '3' => '农行账号尾号769',
        '4' => '光大账号尾号5613',
        '5' => '光大账号尾号4038',
        '6' => '恒丰银行卡号'
    );

    const BANK_ACCESS = '6230583000001724824';
    const BANK_ACCESS_USER = '郑红勇';

    public function index() {
        redirect(U('Financial/localConsumptionSet'));
    }

    /**
     * 本地消费结算
     */
    public function localConsumptionSet() {
        $partner_id = I('get.partner_id', '', 'strval');
        $city_id = I('get.city_id', '', 'strval');
        $apply_date = I('get.apply_date', '', 'strval');
        $pay_date = I('get.pay_date', '', 'strval');
        $is_express = I('get.is_express', 'N', 'trim');
        $bank_type = I('get.bank_type', '', 'trim');
        $bank_large_no = I('get.bank_large_no', '', 'trim');
        $title = I('get.title', '', 'strval');
        $bank_id = I('get.bank_id', '', 'trim');
        $money = I('get.money', '', 'trim');
        $partner = M('partner');

        // 查询条件
        $where = array(
            'partner_pay.verify_state' => 'N',
                //  'partner_pay.is_express'=>'N'
        );

//        if($is_express == 'Y'){
//            $where['partner_pay.is_express'] = $is_express;
//        }
        // 申请时间
        if (trim($apply_date)) {
            $where['partner_pay.create_time'] = array(array('egt', strtotime($apply_date)), array('lt', strtotime('+1 day ' . $apply_date)));
        }

        if ($title) {
            $where['partner.username|partner.title'] = array('like', "%{$title}%");
        }
        // 结算时间
        if (trim($pay_date)) {
            $where['partner_pay.pay_time'] = array(array('egt', strtotime($pay_date)), array('lt', strtotime('+1 day ' . $pay_date)));
        }
        if(trim($bank_id)){
            $where['partner_pay.bank_id']=$bank_id;
        }
        if(trim($money)){
            $where['partner_pay.money']=$money;
        }
        // 城市区域
        if (trim($city_id)) {
            $partner_ids = $partner->where(array('city_id' => $city_id))->field('id')->select();
            $pids = array();
            if ($partner_ids) {
                foreach ($partner_ids as $v) {
                    if (isset($v['id'])) {
                        $pids[] = $v['id'];
                    }
                }
            }
            if ($pids) {
                $where['partner_pay.partner_id'] = array('in', $pids);
            }
        }
        // 商户编码
        if (trim($partner_id)) {
            $where['partner_pay.partner_id'] = trim($partner_id);
        }
        
        // 农行
        if($bank_type){
            $bank_where = "";
            if($bank_type == 'Y'){
                $bank_where = "partner.bank_name like '%农%' OR partner.sbank like '%农%'";
            }else{
                $bank_where = "partner.bank_name not like '%农%' AND partner.sbank not like '%农%'";
            }
            if($bank_where){
                if(isset($where['_string']) && trim($where['_string'])){
                    $where['_string'] = "({$where['_string']}) and ({$bank_where})";
                }else{
                    $where['_string'] = $bank_where;
                }
            }
        }
        // 大额行号
        if($bank_large_no){
            $bank_where = "";
            if($bank_large_no == 'Y'){
                $bank_where = "partner.bank_large_no <>''";
            }else{
                $bank_where = "partner.bank_large_no=''";
            }
            if($bank_where){
                if(isset($where['_string']) && trim($where['_string'])){
                    $where['_string'] = "({$where['_string']}) and ({$bank_where})";
                }else{
                    $where['_string'] = $bank_where;
                }
            }
        }

        $partner_pay = M('partner_pay');
        $count = $partner_pay->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'partner.id' => 'partner_id',
            'partner.title' => 'partner_title',
            'partner.city_id' => 'partner_city_id',
            //'partner.bank_name' => 'partner_bank_name',//修改从申请结算表取值
            'partner_pay.partner_bankname' => 'partner_bank_name',
            'partner.mobile' => 'partner_mobile',
            //'partner.bank_user' => 'partner_bank_user',
            //'partner.bank_no' => 'partner_bank_no',
            'partner_pay.partner_bankuser' => 'partner_bank_user',//修改从申请结算表取值
            'partner_pay.partner_bankno' => 'partner_bank_no',//修改从申请结算表取值
            'partner.bank_large_no' => 'partner_bank_large_no',
            'partner.banks' => 'partner_banks',
            'partner.bankx' => 'partner_bankx',
            'partner.sbank' => 'partner_sbank',
            'partner_pay.id' => 'partner_pay_id',
            'partner_pay.bank_id' => 'bank_id',
            'partner_pay.money' => 'partner_pay_money',
            'partner_pay.create_time' => 'partner_pay_create_time',
            'partner_pay.paymark' => 'partner_pay_paymark',
            'partner_pay.verify_state' => 'partner_pay_verify_state',
            'partner_pay.is_express' => 'partner_pay_is_express',
        );
        $list = $partner_pay->field($field)->order(array('partner_pay.paymark' => 'asc', 'partner_pay.id' => 'desc'))
                ->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        // 数据整理
        $city_res = $this->_getCategoryList('city');
        if ($list) {//
            foreach ($list as &$v) {
                $partner_bank = M('partner_bank')->where(array('partner_id'=>$v['partner_id']))->field('account')->limit(10)->order('id desc')->select();
                $v['account_status'] = "Y";
                if($partner_bank){
                    foreach ($partner_bank as $p){
                        if($v['partner_bank_no'] != $p['account']){
                            $v['account_status'] = 'N';
                            break;
                        }
                    }
                }else{
                    $v['account_status'] = 'N';
                }
                $v['partner_city_name'] = ternary($city_res[$v['partner_city_id']]['name'], '');
                $v['partner_status'] = '0';
                if (trim($v['partner_banks']) && trim($v['partner_bankx']) && trim($v['partner_sbank']) && trim($v['partner_bank_no']) && trim($v['partner_bank_user']) && trim($v['partner_bank_name'])) {
                    $v['partner_status'] = '1';
                }
                $v['bank_id']=$this->youngt_bank[$v['bank_id']];
            }
            unset($v);
        }

        // 统计
        $all_price = $partner_pay->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->sum('money');
        $where['partner_pay.paymark'] = '0';
        $un_settlement_all_price = $partner_pay->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->sum('money');
        $where['partner_pay.paymark'] = '1';
        $settlement_all_price = $partner_pay->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->sum('money');


        $data = array(
            'citys' => $city_res,
            'title' => $title,
            'city_id' => $city_id,
            'partner_id' => $partner_id,
            'apply_date' => $apply_date,
            'pay_date' => $pay_date,
            'is_express' => $is_express,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
            'all_price' => $all_price,
            'money'=>$money,
            'settlement_all_price' => $settlement_all_price,
            'un_settlement_all_price' => $un_settlement_all_price,
            'bank_id'=>$this->youngt_bank[$bank_id]
        );
        $this->assign($data);
        $this->display();
    }

    /***
     * 批量下载短信
     */
   public function smsSettlement(){
       $stime = date('Y-m-d');
       if (trim($stime)) {
           $where['partner_pay.pay_time'] = array(array('egt', strtotime($stime)), array('lt', strtotime('+1 day ' . $stime)));
       }
       $partner_pay = M('partner_pay');
       $field = array(
           'partner.phone' => 'partner_mobile',
           'partner.title' => 'partner_username',
           'partner_pay.create_time' => 'partner_pay_create_time',
           'partner_pay.money' => 'partner_pay_money',
           'partner.bank_no' => 'partner_bank_no',
           'partner.bank_name' => 'partner_bank_name',
       );
       $list = $partner_pay->field($field)
           ->where($where)
           ->join(array('left join partner_bank on partner_bank.id=partner_pay.id', 'left join partner on partner.id=partner_pay.partner_id'))
           ->select();
       $head = array(
           'mobile' => '号码',
           'cout' => '类容'
       );
       $team=array();
       foreach ($list as $k=>$v) {
           $team[$k]['mobile']=$v['partner_mobile'];
           $cout='【青团生活】'.$v['partner_username'].'商家您好，您于'.date("Y-m-d ",$v['partner_pay_create_time']).'号提现金额'.$v['partner_pay_money'].'元，已转入您尾数'.substr($v['partner_bank_no'], -4, 4).$v['partner_bank_name'].'，请注意查收。';
           $team[$k]['cout']=$cout;
       }
       $file_name ='短信' . date('YmdHis', $stime).'下载';
       download_xls($team,$head, $file_name);
   }
    /**
     * 结算操作
     */
    public function settlement() {
        $id = I('get.id', '', 'strval');
        $partner_id = I('get.partner_id', '', 'strval');
        $money = I('get.money', '', 'strval');

        if (!trim($id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '结款id不能为空！'));
        }
        if (!trim($partner_id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商户编号不能为空！'));
        }
        if (!isset($this->user['id']) || !trim($this->user['id'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户未登录！'));
        }
        $partner_pay = M('partner_pay');
        $partner_pay_res = $partner_pay->where(array('id' => $id))->find();
        if (!$partner_pay_res || empty($partner_pay_res)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '要结算的信息不存在！'));
        }

        if (isset($partner_pay_res['money'])) {
            $money = $partner_pay_res['money'];
        }

        $order_ids = array();
        if (isset($partner_pay_res['is_express']) && trim($partner_pay_res['is_express']) == 'Y') {
            $order_ids_res = M('partner_income')->where(array('pay_id' => $id, 'is_express' => 'Y'))->field('coupon_id')->select();
            if ($order_ids_res) {
                foreach ($order_ids_res as &$v) {
                    $coupon_id_v = intval($v['coupon_id']);
                    $coupon_id_v > 0 && $order_ids[$coupon_id_v] = $coupon_id_v;
                }
            }
            unset($v);
        }

        $model = M();
        $model->startTrans();

        $partner = M('partner');
        $now_time = time();
        $data = array(
            'remarks' => date('Y-m-d H:i:s', $now_time) . " $money.元已经提现成功，请注意查收!",
        );
        $res = $partner->where(array('id' => $partner_id))->save($data);
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '结算失败！'));
        }
        $data = array(
            'paymark' => '1',
            'user_id' => $this->user['id'],
            'pay_time' => $now_time,
        );
        $res = $partner_pay->where(array('id' => $id))->save($data);
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '结算失败！'));
        }
        if ($order_ids) {
            $res = M('order')->where(array('id' => array('in', $order_ids)))->save(array('is_pay' => 'Y'));
            if ($res === false) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '结算失败！'));
            }
        }

        // 添加操作日志
        $this->addOperationLogs("操作：结算,partner_pay_id:{$id},金额:{$money},商家:{$partner_id}");
        $model->commit();

        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 批量结算
     */
    public function batchSettlement() {
        $pay_id = I('post.pay_id', '', 'strval');
        $bank_type=I('post.bank_type', '', 'strval');
        if (trim($pay_id)) {
            $pay_id = @explode(',', $pay_id);
        }
        if (!$bank_type) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择标示银行！'));
        }
        if (!$pay_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择批量结算的数据！'));
        }
        $res = $this->batchSettlementUpdateData($pay_id,$bank_type);
        if (isset($res['error'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
        }
        $success = ternary($res['success'], '');
        // 操作日志
        $this->addOperationLogs("操作：批量结算,partner_pay_id:[" . implode(',', $pay_id) . "],$success");
        $this->ajaxReturn(array('code' => 0, 'success' => "操作成功！$success"));
    }

    /**
     * 批量结算审核通过的商家
     */
    public function batchSettlementTodayAllPartner() {
        $bank_type=I('post.bank_type', '', 'strval');
        ini_set("max_execution_time", 1800);
        $now_time = date('Y-m-d');
        $start_time = strtotime($now_time);
        $end_time = strtotime('+1 day ' . $now_time);

        $where = array(
            // 'partner_bank.create_time' => array(array('egt', $start_time), array('lt', $end_time)),
            'partner_pay.paymark' => '0',
            'partner_pay.verify_state' => 'Y',
        );
        $partner_pay_list = M('partner_pay')->field('partner_pay.id')->where($where)
                        //->join('inner join partner_pay on partner_pay.id=partner_bank.pay_id')
                        ->limit(100)->select();
        $pay_ids = array();
        if ($partner_pay_list) {
            foreach ($partner_pay_list as &$v) {
                if (isset($v['id']) && trim($v['id'])) {
                    array_push($pay_ids, trim($v['id']));
                }
            }
            unset($v);
        }
        if (!$pay_ids) {
            $this->ajaxReturn(array('code' => -1, 'error' => '没有要结算的商家！'));
        }
        $res = $this->batchSettlementUpdateData($pay_ids,$bank_type);
        if (isset($res['error'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => $res['error']));
        }
        $success = '记录数:' . count($pay_ids);
        // 操作日志
        $this->addOperationLogs("操作：批量结算已审核的商家，{$success}，详情：{$res['success']}");
        ini_set("max_execution_time", 30);
        $this->ajaxReturn(array('code' => 0, 'success' => "操作成功！$success"));
    }

    /**
     * 批量结算修改数据库
     */
    private function batchSettlementUpdateData($pay_id = array(),$bank_type='') {
        if (!$pay_id) {
            return array('error' => '批量结算的数据为空！');
        }

        $where = array(
            'id' => array('in', $pay_id),
            'paymark' => '0',
        );
        $partner_pay = M('partner_pay');
        $partner_pay_res = $partner_pay->where($where)->select();
        if (!$partner_pay_res || empty($partner_pay_res)) {
            return array('error' => '该信息可能已经结款了！');
        }
        $partner_ids = array();
        foreach ($partner_pay_res as $v) {
            if (!isset($partner_ids[$v['partner_id']])) {
                $partner_ids[$v['partner_id']] = $v['money'];
                continue;
            }
            $partner_ids[$v['partner_id']] = sprintf("%.2f", $partner_ids[$v['partner_id']] + $v['money']);
        }

        // 获取邮购的 订单id
        $order_ids = array();
        $order_ids_res = M('partner_income')->where(array('pay_id' => array('in', $pay_id), 'is_express' => 'Y'))->field('coupon_id')->select();
        if ($order_ids_res) {
            foreach ($order_ids_res as &$v) {
                $coupon_id_v = intval($v['coupon_id']);
                $coupon_id_v > 0 && $order_ids[$coupon_id_v] = $coupon_id_v;
            }
            unset($v);
        }

        $model = M();
        $model->startTrans();
        $partner = M('partner');
        $now_time = time();
        $success = '';
        foreach ($partner_ids as $partner_id => $money) {
            $data = array(
                'remarks' => date('Y-m-d H:i:s', $now_time) . " $money.元已经提现成功，请注意查收!",
            );
            $res = $partner->where(array('id' => $partner_id))->save($data);
            if (!$res) {
                $model->rollback();
                return array('error' => '批量结算失败！');
            }
            $success .= "商家：{$partner_id}，结款：{$money }元；";
        }
        $data = array(
            'paymark' => '1',
            'user_id' => $this->user['id'],
            'pay_time' => $now_time,
            'bank_id'=>$bank_type,
        );
        $res = $partner_pay->where($where)->save($data);
        if (!$res) {
            $model->rollback();
            return array('error' => '批量结算失败！');
        }
        // 邮购结算字段更新
        if ($order_ids) {
            $res = M('order')->where(array('id' => array('in', $order_ids)))->save(array('is_pay' => 'Y'));
            if ($res === false) {
                $model->rollback();
                $this->ajaxReturn(array('code' => -1, 'error' => '结算失败！'));
            }
        }

        $model->commit();
        return array('success' => $success);
    }

    /**
     * 审核操作 
     */
    public function examine() {
        $id = I('get.id', '', 'strval');
        $partner_id = I('get.partner_id', '', 'strval');
        $money = I('get.money', '', 'strval');

        if (!trim($id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => 'id不能为空！'));
        }
        if (!trim($partner_id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商户编号不能为空！'));
        }
        if (!isset($this->user['id']) || !trim($this->user['id'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户未登录！'));
        }

        $partner_pay = M('partner_pay');
        $partner_pay_res = $partner_pay->where(array('id' => $id))->find();
        if (!$partner_pay_res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '结款信息不存在！'));
        }
        if (!isset($partner_pay_res['verify_state']) || trim($partner_pay_res['verify_state']) == 'Y') {
            $this->ajaxReturn(array('code' => -1, 'error' => '该商户已经审核，不能重复审核！'));
        }
        if (!isset($partner_pay_res['partner_id']) || $partner_pay_res['partner_id'] != $partner_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商户编号不一致！'));
        }
        $partner = M('partner');
        $partner_res = $partner->where(array('id' => $partner_pay_res['partner_id']))->find();
        if (!$partner_res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商户信息不存在！'));
        }
        // 商家信息完整性判断
        foreach (array('id', 'username', 'banks', 'bankx', 'sbank', 'bank_name', 'bank_user', 'bank_no') as $v) {
            if (!isset($partner_res[$v]) || !trim($partner_res[$v])) {
                $this->ajaxReturn(array('code' => -1, 'error' => '商户信息不完整，无法审核！'));
            }
        }

        $data = array(
            'pay_id' => $id,
            'partner_id' => ternary($partner_res['id'], ''),
            'partner_username' => ternary($partner_res['username'], ''),
            'money' => ternary($partner_pay_res['money'], ''),
            'account' => ternary($partner_res['bank_no'], ''),
            'username' => ternary($partner_res['bank_user'], ''),
            'bank_name' => ternary($partner_res['sbank'], ''),
            'bank_pro' => ternary($partner_res['banks'], ''),
            'bank_city' => ternary($partner_res['bankx'], ''),
            'bank_type' => '异地跨行',
            'create_time' => time(),
        );
        if (strpos($partner_res['sbank'], '平安银行') !== false) {
            $data['bank_type'] = '行内转账';
        } else if (strpos($partner_res['bankx'], '西安市') !== false) {
            $data['bank_type'] = '同城跨行';
        }

        $model = M();
        $model->startTrans();
        $partner_bank = M('partner_bank');
        $res = $partner_bank->add($data);
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '审核失败！'));
        }

        $res = $partner_pay->where(array('id' => $id))->save(array('verify_state' => 'Y'));
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '审核失败！'));
        }
        // 操作日志
        $this->addOperationLogs("操作：审核,partner_pay_id:{$id},金额：{$partner_pay_res['money']}，商家:{$partner_pay_res['partner_id']}");
        $model->commit();

        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 批量审核
     */
    public function batchExamine() {
        $pay_id = I('post.pay_id', '', 'strval');
        if (trim($pay_id)) {
            $pay_id = @explode(',', $pay_id);
        }

        if (!$pay_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '请选择批量审核的数据！'));
        }

        $where = array('id' => array('in', $pay_id));
        $partner_pay = M('partner_pay');
        $partner_pay_list = $partner_pay->where($where)->select();
        $pdata = array();
        $success = '';
        $partner = M('partner');
        $now_time = time();
        $update_pay_ids = array();
        foreach ($partner_pay_list as $v) {

            if (!isset($v['id']) || !trim($v['id'])) {
                continue;
            }

            if (!isset($v['verify_state']) || trim($v['verify_state']) == 'Y') {
                $success .= "pay_id:{$v['id']},处理结果：该数据已经审核，不能重复审核";
                continue;
            }

            if (!isset($v['partner_id']) || !trim($v['partner_id'])) {
                $success .= "pay_id:{$v['id']},处理结果：商户id为空！";
                continue;
            }
            $partner_res = $partner->where(array('id' => $v['partner_id']))->find();
            // 商家信息完整性判断
            $flag = false;
            foreach (array('id', 'username', 'banks', 'bankx', 'sbank', 'bank_name', 'bank_user', 'bank_no') as $_v) {
                if (!isset($partner_res[$_v]) || !trim($partner_res[$_v])) {
                    $flag = true;
                    $success .= "pay_id:{$v['id']},处理结果：商户信息不完整，无法审核[{$_v}]！";
                    break;
                }
            }
            if ($flag) {
                continue;
            }
            $data = array(
                'pay_id' => $v['id'],
                'partner_id' => ternary($partner_res['id'], ''),
                'partner_username' => ternary($partner_res['username'], ''),
                'money' => ternary($v['money'], ''),
                'account' => ternary($partner_res['bank_no'], ''),
                'username' => ternary($partner_res['bank_user'], ''),
                'bank_name' => ternary($partner_res['sbank'], ''),
                'bank_pro' => ternary($partner_res['banks'], ''),
                'bank_city' => ternary($partner_res['bankx'], ''),
                'bank_type' => '异地跨行',
                'create_time' => $now_time,
            );
            if (strpos($partner_res['sbank'], '平安银行') !== false) {
                $data['bank_type'] = '行内转账';
            } else if (strpos($partner_res['bankx'], '西安市') !== false) {
                $data['bank_type'] = '同城跨行';
            }
            $pdata[] = $data;
            $update_pay_ids[$data['pay_id']] = $data['pay_id'];
            $success .= "pay_id:{$v['id']},处理结果：审核款项[{$data['money']}]元成功！";
        }

        if (!$pdata || !$update_pay_ids) {
            // 操作日志
            $this->addOperationLogs("操作：批量审核，详情：{$success}");
            $this->ajaxReturn(array('code' => -1, 'error' => '审核数据不合格，无法审核，详情查看管理目录下的操作日志！'));
        }

        $model = M();
        $model->startTrans();
        $partner_bank = M('partner_bank');
        $res = $partner_bank->addAll($pdata);
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '批量审核失败！'));
        }
        $res = $partner_pay->where(array('id' => array('in', array_keys($update_pay_ids))))->save(array('verify_state' => 'Y'));
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '审核失败！'));
        }
        // 操作日志
        $this->addOperationLogs("操作：批量审核，详情：{$success}");
        $model->commit();
        $this->ajaxReturn(array('code' => 0, 'success' => '操作成功！详情查看管理目录下的操作日志'));
    }

    /**
     * 取消审核操作
     */
    public function cancelExamine() {
        $id = I('get.id', '', 'strval');
        $partner_id = I('get.partner_id', '', 'strval');
        $money = I('get.money', '', 'strval');

        if (!trim($id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => 'id不能为空！'));
        }
        if (!trim($partner_id)) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商户编号不能为空！'));
        }
        if (!isset($this->user['id']) || !trim($this->user['id'])) {
            $this->ajaxReturn(array('code' => -1, 'error' => '用户未登录！'));
        }

        $partner_pay = M('partner_pay');
        $partner_pay_res = $partner_pay->where(array('id' => $id))->find();
        if (!$partner_pay_res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '结款信息不存在！'));
        }
        if (!isset($partner_pay_res['partner_id']) || $partner_pay_res['partner_id'] != $partner_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商户编号不一致！'));
        }
        $partner = M('partner');
        $partner_count = $partner->where(array('id' => $partner_pay_res['partner_id']))->count();
        if (!$partner_count || $partner_count <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '商户信息不存在！'));
        }
        $where = array(
            'pay_id' => $id,
            'partner_id' => ternary($partner_pay_res['partner_id'], ''),
        );
        $model = M();
        $model->startTrans();
        $partner_bank = M('partner_bank');
        $res = $partner_bank->where($where)->delete();
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '取消审核失败！'));
        }

        $res = $partner_pay->where(array('id' => $id))->save(array('verify_state' => 'N'));
        if (!$res) {
            $model->rollback();
            $this->ajaxReturn(array('code' => -1, 'error' => '取消审核失败！'));
        }
        // 操作日志
        $this->addOperationLogs("操作：取消审核,partner_pay_id:{$id},金额：{$partner_pay_res['money']}，商家:{$partner_pay_res['partner_id']}");
        $model->commit();

        $this->ajaxReturn(array('code' => 0));
    }

    /***
     * 获取银行相关信息
     */
    public function youngtbank(){

        if($_POST){
            $id = I('post.id', '', 'strval');
            $bank_id = I('post.refund_type', '', 'strval');
            $partner_bank_user = I('post.partner_bank_user', '', 'strval');
            $partner_bank_no = I('post.partner_bank_no', '', 'strval');
            $partner_bank_name = I('post.partner_bank_name', '', 'strval');
            $partner_pay = M('partner_pay');
            if($id){
                $where['id']=$id;
                $data['bank_id']=$bank_id;
                $data['partner_bankname']=$partner_bank_name;
                $data['partner_bankuser']=$partner_bank_user;
                $data['partner_bankno']=$partner_bank_no;
                $partner_pay->where($where)->save($data);
                $this->ajaxReturn(array('code' => 0));
            }else{
                $this->ajaxReturn(array('code' => -1, 'error' => '修改失败！'));
            }

            exit;
        }else{
            $id = I('get.id', '', 'strval');
            if (!trim($id)) {
                $this->redirect_message(U('Financial/auditConsumptionSet'), array('error' => base64_encode('结算id不能为空！')));
            }else{
                $partner_pay = M('partner_pay');
                $where['partner_pay.id']=$id;
                $field = array(
                    'partner.title' => 'partner_title',
                    //'partner.bank_name' => 'partner_bank_name',//修改从申请结算表取值
                    'partner_pay.partner_bankname' => 'partner_bank_name',
                    //'partner.bank_user' => 'partner_bank_user',
                    //'partner.bank_no' => 'partner_bank_no',
                    'partner_pay.partner_bankuser' => 'partner_bank_user',//修改从申请结算表取值
                    'partner_pay.partner_bankno' => 'partner_bank_no',//修改从申请结算表取值
                    'partner.bank_large_no' => 'partner_bank_large_no',
                    'partner_pay.id' => 'partner_pay_id',
                    'partner_pay.bank_id' => 'bank_id',
                    'partner_pay.money' => 'partner_pay_money',
                    'partner_pay.create_time' => 'partner_pay_create_time',
                    'partner_pay.pay_time' => 'partner_pay_pay_time',
                );
                $data=M('partner_pay')->field($field)->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->find();
                if ($data) {
                    $data['bank_id']=$this->youngt_bank[$data['bank_id']];
                }
                $this->assign('order',$data);
                $this->display();
            }
        }

    }
    /**
     * 已审核消费结算
     */
    public function auditConsumptionSet() {
        $partner_id = I('get.partner_id', '', 'strval');
        $city_id = I('get.city_id', '', 'strval');
        $apply_date = I('get.apply_date', '', 'strval');
        $pay_date = I('get.pay_date', '', 'strval');
        $is_express = I('get.is_express', 'N', 'trim');
        $bank_type = I('get.bank_type', '', 'trim');
        $bank_id = I('get.bank_id', '', 'trim');
        $title = I('get.title', '', 'strval');
        $money = I('get.money', '', 'trim');
        $partner = M('partner');

        // 查询条件
        $where = array(
            'partner_pay.verify_state' => 'Y',
                //  'partner_pay.is_express'=>'N'
        );

        // 申请时间
        if (trim($apply_date)) {
            $where['partner_pay.create_time'] = array(array('egt', strtotime($apply_date)), array('lt', strtotime('+1 day ' . $apply_date)));
        }
        if ($title) {
            $where['partner.username|partner.title'] = array('like', "%{$title}%");
        }
        // 结算时间
        if (trim($pay_date)) {
            $where['partner_pay.pay_time'] = array(array('egt', strtotime($pay_date)), array('lt', strtotime('+1 day ' . $pay_date)));
        }
        //
        if(trim($bank_id)){
            $where['partner_pay.bank_id']=$bank_id;
        }
        if(trim($money)){
            $where['partner_pay.money']=$money;
        }
        // 城市区域
        if (trim($city_id)) {
            $partner_ids = $partner->where(array('city_id' => $city_id))->field('id')->select();
            $pids = array();
            if ($partner_ids) {
                foreach ($partner_ids as $v) {
                    if (isset($v['id'])) {
                        $pids[] = $v['id'];
                    }
                }
            }
            if ($pids) {
                $where['partner_pay.partner_id'] = array('in', $pids);
            }
        }
        // 商户编码
        if (trim($partner_id)) {
            $where['partner_pay.partner_id'] = trim($partner_id);
        }
        
        // 农行
        if($bank_type){
            $bank_where = "";
            if($bank_type == 'Y'){
                $bank_where = "partner.bank_name like '%农%' OR partner.sbank like '%农%'";
            }else{
                $bank_where = "partner.bank_name not like '%农%' AND partner.sbank not like '%农%'";
            }
            if($bank_where){
                if(isset($where['_string']) && trim($where['_string'])){
                    $where['_string'] = "({$where['_string']}) and ({$bank_where})";
                }else{
                    $where['_string'] = $bank_where;
                }
            }
        }

        $partner_pay = M('partner_pay');
        $count = $partner_pay->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'partner.id' => 'partner_id',
            'partner.title' => 'partner_title',
            'partner.city_id' => 'partner_city_id',
            //'partner.bank_name' => 'partner_bank_name',//修改从申请结算表取值
            'partner_pay.partner_bankname' => 'partner_bank_name',
            'partner.mobile' => 'partner_mobile',
            //'partner.bank_user' => 'partner_bank_user',
            //'partner.bank_no' => 'partner_bank_no',
            'partner_pay.partner_bankuser' => 'partner_bank_user',//修改从申请结算表取值
            'partner_pay.partner_bankno' => 'partner_bank_no',//修改从申请结算表取值
            'partner.bank_large_no' => 'partner_bank_large_no',
            'partner.banks' => 'partner_banks',
            'partner.bankx' => 'partner_bankx',
            'partner.sbank' => 'partner_sbank',
            'partner_pay.id' => 'partner_pay_id',
            'partner_pay.bank_id' => 'bank_id',
            'partner_pay.money' => 'partner_pay_money',
            'partner_pay.create_time' => 'partner_pay_create_time',
            'partner_pay.paymark' => 'partner_pay_paymark',
            'partner_pay.verify_state' => 'partner_pay_verify_state',
            'partner_pay.is_express' => 'partner_pay_is_express',
        );
        $list = $partner_pay->field($field)->order(array('partner_pay.paymark' => 'asc', 'partner_pay.id' => 'desc'))
                ->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
// 数据整理
        $city_res = $this->_getCategoryList('city');
        if ($list) {
            foreach ($list as &$v) {
                $v['partner_city_name'] = ternary($city_res[$v['partner_city_id']]['name'], '');
                $v['partner_status'] = '0';
                if (trim($v['partner_banks']) && trim($v['partner_bankx']) && trim($v['partner_sbank']) && trim($v['partner_bank_no']) && trim($v['partner_bank_user']) && trim($v['partner_bank_name'])) {
                    $v['partner_status'] = '1';
                }
                $v['bank_id']=$this->youngt_bank[$v['bank_id']];
            }
            unset($v);
        }

        // 统计
//        $where = array(
//            'verify_state' => 'Y'
//        );
        $all_price = $partner_pay->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->sum('money');
        $where['partner_pay.paymark'] = '0';
        $un_settlement_all_price = $partner_pay->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->sum('money');
        $where['partner_pay.paymark'] = '1';
        $settlement_all_price = $partner_pay->where($where)->join('inner join partner on partner.id=partner_pay.partner_id')->sum('money');

        $data = array(
            'citys' => $city_res,
            'title' => $title,
            'city_id' => $city_id,
            'partner_id' => $partner_id,
            'apply_date' => $apply_date,
            'pay_date' => $pay_date,
            'is_express' => $is_express,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
            'money'=>$money,
            'all_price' => $all_price,
            'settlement_all_price' => $settlement_all_price,
            'un_settlement_all_price' => $un_settlement_all_price,
            'bank_id'=>$this->youngt_bank[$bank_id]
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 本地消费结算明细
     */
    public function couponDetail() {
        // 参数接收
        $pay_id = I('get.pay_id', '', 'strval');
        $team_id = I('post.team_id', '', 'strval');
        $coupon_id = I('post.coupon_id', '', 'strval');
        $coupon_consume = I('post.coupon_consume', '', 'strval');
        if (!trim($pay_id)) {
            $pay_id = I('post.pay_id', '', 'strval');
        }
        if (!trim($pay_id)) {
            $this->redirect_message(U('Financial/localConsumptionSet'), array('error' => base64_encode('明细pay_id不能为空!')));
        }
        // 查询条件
        $where = array(
            'partner_income.pay_id' => trim($pay_id),
        );
        if (trim($team_id)) {
            $team_id = trim($team_id);
            $where['_string'] = "partner_income.team_id='{$team_id}' OR team.product LIKE '%{$team_id}%'";
        }
        if (trim($coupon_id)) {
            $where['partner_income.coupon_id'] = trim($coupon_id);
        }
        if (trim($coupon_consume) && isset($this->option_state[$coupon_consume])) {
            $where['coupon.consume'] = trim($coupon_consume);
        }

        $partner_income = M('partner_income');
        $count = $partner_income->where($where)
                        ->join('INNER JOIN team  ON team.id=partner_income.`team_id`')
                        ->join('INNER JOIN coupon ON coupon.id=partner_income.`coupon_id`')->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'team.id' => 'team_id',
            'team.product' => 'team_product',
            'team.team_price' => 'team_team_price',
            'team.ucaii_price' => 'team_ucaii_price',
            'partner_income.coupon_id' => 'partner_income_coupon_id',
            '`user`.username' => 'user_username',
            '`user`.email' => 'user_email',
            'coupon.consume_time' => 'coupon_consume_time',
            'coupon.consume' => 'coupon_consume',
            'coupon.team_price' => 'coupon_team_price',
            'coupon.ucaii_price' => 'coupon_ucaii_price',
        );
        $list = $partner_income->field($field)->order(array('coupon.team_id' => 'asc', 'coupon.consume_time' => 'desc', 'coupon.id' => 'asc'))
                ->where($where)
                ->join('INNER JOIN team  ON team.id=partner_income.`team_id`')
                ->join('INNER JOIN coupon ON coupon.id=partner_income.`coupon_id`')
                ->join('inner join `user` on coupon.`user_id`=`user`.`id`')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();

        if ($list) {
            foreach ($list as &$v) {
                $v['team_price'] = ternary($v['coupon_team_price'], '0');
                if ($v['team_price'] <= 0) {
                    $v['team_price'] = ternary($v['team_team_price'], '0');
                }
                $v['ucaii_price'] = ternary($v['coupon_ucaii_price'], '0');
                if ($v['ucaii_price'] <= 0) {
                    $v['ucaii_price'] = ternary($v['team_ucaii_price'], '0');
                }
                $v['coupon_consume_text'] = ternary($this->option_state[$v['coupon_consume']], '未知');
            }
            unset($v);
        }

        // 计算统计
        $field = array(
            'partner_income.team_id' => 'partner_income_team_id',
            'COUNT(partner_income.coupon_id)' => 'partner_coupon_count',
            'team.ucaii_price' => 'team_ucaii_price',
            'count(partner_income.coupon_id)*team.ucaii_price' => 'all_team_ucaii_price'
        );
        $team_info = $partner_income->field($field)->order(array('partner_income.team_id' => 'desc'))->where(array('partner_income.pay_id' => $pay_id))
                        ->join('INNER JOIN team  ON team.id=partner_income.`team_id`')
                        ->group('partner_income.team_id')->select();
        $team_info_all_price = 0;
        if ($team_info) {
            $field = array(
                'coupon.ucaii_price' => 'coupon_ucaii_price'
            );
            foreach ($team_info as &$v) {
                if (!isset($v['partner_income_team_id']) || !trim($v['partner_income_team_id'])) {
                    unset($v);
                    continue;
                }
                $coupon_list = $partner_income->field($field)->where(array('partner_income.pay_id' => $pay_id, 'partner_income.team_id' => $v['partner_income_team_id']))
                        ->join('inner join coupon on partner_income.coupon_id=coupon.id')
                        ->select();
                $all_price = 0;
                if ($coupon_list) {
                    foreach ($coupon_list as $cl) {
                        $ucaii_price = $cl['coupon_ucaii_price'];
                        if (!$ucaii_price || $ucaii_price <= 0) {
                            $ucaii_price = $v['team_ucaii_price'];
                        }
                        $all_price = $all_price + $ucaii_price;
                    }
                }
                $v['all_price'] = sprintf("%.2f", $all_price);
                $team_info_all_price = $team_info_all_price + $v['all_price'];
            }
            unset($v);
        }

        $data = array(
            'pay_id' => $pay_id,
            'coupon_consume' => $coupon_consume,
            'coupon_id' => $coupon_id,
            'team_id' => $team_id,
            'coupon_consume_type' => $this->option_state,
            'team_info' => $team_info,
            'team_info_all_price' => sprintf("%.2f", $team_info_all_price),
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 邮购商品结算明细
     */
    public function orderDetail() {
        // 参数接收
        $pay_id = I('get.pay_id', '', 'strval');
        $team_id = I('post.team_id', '', 'strval');
        $coupon_id = I('post.coupon_id', '', 'strval');
        if (!trim($pay_id)) {
            $pay_id = I('post.pay_id', '', 'strval');
        }
        if (!trim($pay_id)) {
            $this->redirect_message(U('Financial/localConsumptionSet'), array('error' => base64_encode('明细pay_id不能为空!')));
        }
        // 查询条件
        $where = array(
            'partner_income.pay_id' => trim($pay_id),
        );
        if (trim($team_id)) {
            $team_id = trim($team_id);
            $where['_string'] = "partner_income.team_id='{$team_id}' OR team.product LIKE '%{$team_id}%'";
        }
        if (trim($coupon_id)) {
            $where['partner_income.coupon_id'] = trim($coupon_id);
        }

        $partner_income = M('partner_income');
        $count = $partner_income->where($where)
                        ->join('INNER JOIN team  ON team.id=partner_income.`team_id`')
                        ->join('INNER JOIN `order` ON order.id=partner_income.`coupon_id`')->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'team.id' => 'team_id',
            'team.product' => 'team_product',
            'order.price' => 'order_team_price',
            'order.ucaii_price' => 'order_ucaii_price',
            'partner_income.coupon_id' => 'partner_income_coupon_id',
            '`user`.username' => 'user_username',
            '`user`.email' => 'user_email',
            '`order`.quantity' => 'order_quantity',
            '`order`.fare' => 'order_fare',
        );
        $list = $partner_income->field($field)->order(array('team.id' => 'asc', 'order.pay_time' => 'desc'))
                ->where($where)
                ->join('INNER JOIN team  ON team.id=partner_income.`team_id`')
                ->join('INNER JOIN `order` ON order.id=partner_income.`coupon_id`')
                ->join('inner join `user` on `order`.`user_id`=`user`.`id`')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();

        // 计算统计
        $field = array(
            'partner_income.team_id' => 'partner_income_team_id',
            'sum(`order`.quantity)' => 'partner_team_count',
            'team.team_price' => 'team_team_price',
            '(sum(`order`.quantity*order.ucaii_price))+(count(partner_income.coupon_id)*`order`.fare)' => 'all_team_team_price'
        );
        $team_info = $partner_income
                ->field($field)
                ->join('INNER JOIN team  ON team.id=partner_income.`team_id`')
                ->join('INNER JOIN `order` ON order.id=partner_income.`coupon_id`')
                ->where(array('partner_income.pay_id' => $pay_id))
                ->group('partner_income.team_id')
                ->select();
        $team_info_all_price = 0;
        if ($team_info) {
            foreach ($team_info as &$v) {
                if (!isset($v['partner_income_team_id']) || !trim($v['partner_income_team_id'])) {
                    unset($v);
                    continue;
                }
                $team_info_all_price += $v['all_team_team_price'];
            }
            unset($v);
        }

        $data = array(
            'pay_id' => $pay_id,
            'coupon_id' => $coupon_id,
            'team_id' => $team_id,
            'team_info' => $team_info,
            'team_info_all_price' => sprintf("%.2f", $team_info_all_price),
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($data);
        $this->display();
    }

    // 团单下载 ----替换 teamInfoDown()
    public function teamlistDown() {
        if (IS_POST) {
            $city_id    = I('post.city_id', '', 'strval');
            $start_time = I('post.start_time', '', 'strval');
            $end_time   = I('post.end_time', '', 'strval');
            if (!trim($city_id)) {
                $this->redirect_message(U('Financial/teamInfoDown'), array('error' => base64_encode('请选择区域！')));
            }
            if (!trim($start_time)) {
                $start_time = date('Y-m-d 00:00:00', NOW_TIME);
            }
            if (!trim($end_time)) {
                $end_time = date('Y-m-d H:i:s', NOW_TIME);
            }

            $model = M('team');
            $field = 't.id,t.product,t.ucaii_price,t.team_price,t.lottery_price,count(c.id) as sell_num,sum(case c.consume when \'Y\' then 1 else 0 end) as consume_num,t.team_price * sum(case c.consume when \'Y\' then 1 else 0 end) as sell_money,t.ucaii_price * sum(case c.consume when \'Y\' then 1 else 0 end) as supply_money';
            $map = [
                't.city_id'   => $city_id,
                'c.consume_time' => [['gt', strtotime($start_time)], ['lt', strtotime($end_time)]],
                'c.consume' => 'Y'
            ];
            $list = $model->alias('t')
                  ->field($field)
                  ->join('RIGHT JOIN coupon c ON c.team_id=t.id')
                  ->where($map)
                  ->group('t.id')
                  ->order('t.id')
                  ->select();
            // echo $model->getLastSql();
            // echo count($list);exit;
            $omap = ['pay_time'=>[['gt', strtotime($start_time)], ['lt', strtotime($end_time)]],'state'=>'pay'];
            foreach ($list as $i => $row) {
                $omap['team_id'] = $row['id'];
                $list[$i]['sell_num'] = M('order')->where($omap)->sum('quantity');
            }
            $head = array(
                'id' => '项目ID',
                'product'     => '项目名称',
                'ucaii_price' => '供货价',
                'team_price'  => '销售价',
                'sell_num'      => '购买数量',
                'consume_num'   => '验证数量',
                'sell_money'    => '总验证销售价',
                'supply_money'  => '总验证供货价'
            );
            $file_name = '下载表' . date('YmdHis', NOW_TIME);
            download_xls($list, $head, $file_name);
        } else {
            $city_res = $this->_getCategoryList('city');
            $this->assign('citys',$city_res);
            $this->assign('start_time',date('Y-m-d 00:00:00', NOW_TIME));
            $this->assign('end_time',date('Y-m-d H:i:s', NOW_TIME));
            $this->display();
        }
    }

    /**
     * 团单下载
     */
    public function teamInfoDown() {
        $down_type = I('post.down_type', '', 'strval');
        $now_time = time();
        if (!trim($down_type)) {
            $city_res = $this->_getCategoryList('city');
            $this->assign(array(
                'down_type' => 'download',
                'citys' => $city_res,
                'start_time' => date('Y-m-d 00:00:00', $now_time),
                'end_time' => date('Y-m-d H:i:s', $now_time),
                'team_types' => $this->team_state,
            ));
            $this->display();
            exit;
        }

        ini_set("max_execution_time", 1800);
        $city_id = I('post.city_id', '', 'strval');
        $start_time = I('post.start_time', '', 'strval');
        $end_time = I('post.end_time', '', 'strval');
        $team_type = I('post.team_type', '', 'strval');
        if (!trim($city_id)) {
            $this->redirect_message(U('Financial/teamInfoDown'), array('error' => base64_encode('请选择区域！')));
        }
        if (!trim($start_time)) {
            $start_time = date('Y-m-d 00:00:00', $now_time);
        }
        if (!trim($end_time)) {
            $end_time = date('Y-m-d H:i:s', $now_time);
        }
        if (!trim($team_type)) {
            $team_type = 'normal';
        }

        $where = array(
            //'`coupon`.team_type' => 'normal',
            'coupon.consume_time' => array(array('gt', strtotime($start_time)), array('lt', strtotime($end_time))),
            'coupon.`consume`' => 'Y'
        );
        if (trim($city_id)) {
            $where['team.city_id'] = trim($city_id);
        }
        if (strtolower(trim($team_type)) == 'lottery') {
            $where['`coupon`.team_type'] = array('NEQ', 'normal');
        }

        $coupon = M('coupon');
        $field = array(
            'team.id' => ' team_id',
            'team.`product`' => 'team_product',
            'team.`ucaii_price`' => 'team_ucaii_price',
            'team.`lottery_price`' => 'team_lottery_price',
            'team.`team_price`' => 'team_team_price',
            'count(coupon.id)' => 'coupon_count'
        );
        $list = $coupon->where($where)->field($field)->order('team.id')->group('team.id')
                ->join("left JOIN `team` ON team.`id` =  `coupon`.`team_id`")
                ->select();
        // echo $coupon->getLastSql();
        // echo count($list);exit;
        // 整理数据
        if ($list) {
            $coupon = M('coupon');
            $field = array(
                'coupon.id' => 'coupon_id',
                'coupon.ucaii_price' => 'ucaii_price',
                'coupon.team_price' => 'team_price',
            );
            $coupon_where = array(
                'coupon.consume_time' => array(array('gt', strtotime($start_time)), array('lt', strtotime($end_time))),
                'coupon.`consume`' => 'Y'
            );
            $order = M('order');
            $order_where = array(
                '`order`.pay_time' => array(array('gt', strtotime($start_time)), array('lt', strtotime($end_time))),
                'order.state' => 'pay',
            );
            foreach ($list as $k => &$v) {
                if (!isset($v['team_id']) || !trim($v['team_id'])) {
                    unset($v);
                    continue;
                }

                $order_where['order.team_id'] = $coupon_where['coupon.`team_id`'] = $v['team_id'];
                $coupon_list = $coupon->where($coupon_where)->field($field)->select();
                $v['order_quantity_sum'] = $order->where($order_where)->sum('quantity');
                $all_ucaii_price = 0;
                $all_team_price = 0;
                if ($coupon_list) {
                    foreach ($coupon_list as $cl) {
                        $ucaii_price = ternary($cl['ucaii_price'], '0.00');
                        if (!$ucaii_price || $ucaii_price <= 0) {
                            $ucaii_price = ternary($v['team_ucaii_price'], '0.00');
                        }
                        $team_price = ternary($cl['team_price'], '0.00');
                        if (!$team_price || $team_price <= 0) {
                            $team_price = ternary($v['team_team_price'], '0.00');
                        }
                        $all_ucaii_price = $all_ucaii_price + $ucaii_price;
                        $all_team_price = $all_team_price + $team_price;
                    }
                } else {
                    unset($list[$k]);
                }
                $v['all_ucaii_price'] = sprintf("%.2f", $all_ucaii_price);
                $v['all_team_price'] = sprintf("%.2f", $all_team_price);
            }
            unset($v);
        }

        $head = array(
            'team_id' => '项目ID',
            'team_product' => '项目名称',
            'team_ucaii_price' => '供货价',
            'team_team_price' => '销售价',
            'team_lottery_price' => '销售价',
            'order_quantity_sum' => '购买数量',
            'coupon_count' => '验证数量',
            'all_team_price' => '总验证销售价',
            'all_ucaii_price' => '总验证供货价'
        );
        if (strtolower(trim($team_type)) == 'lottery') {
            unset($head['team_team_price']);
        } else {
            unset($head['team_lottery_price']);
        }
        ini_set("max_execution_time", 30);
        $file_name = $this->team_state[$team_type] . '下载表' . date('YmdHis', $now_time);
        download_xls($list, $head, $file_name);
    }

    /**
     * 打款信息下载
     */
    public function partnerBankDownload() {

        $where = array(
            'partner_bank.down_type' => 'N',
            'partner_pay.paymark' => 0
        );
        $partner_bank = M('partner_bank');
        $partner_bank_res = $partner_bank->field('partner_bank.*,partner_pay.is_express')->where($where)->join('inner join partner_pay on partner_pay.id=partner_bank.pay_id')->select();
        if (!$partner_bank_res || empty($partner_bank_res)) {
            $this->redirect_message(U('Financial/auditConsumptionSet'), array('error' => base64_encode('暂时没有数据！请处理后再下载！')));
        }
        $partner = M('partner');
        $bank_no=$partner->getField('id,bank_large_no');
        // 结果处理
        $partner_bank_ids = array();
        foreach ($partner_bank_res as &$v) {
            $v['manage_account'] = self::BANK_ACCESS;
            $v['manage_user'] = self::BANK_ACCESS_USER;
            $v['bank_large_no']=$bank_no[$v['partner_id']];
            $v['remark'] = "{$v['partner_username']} {$v['partner_id']}-青团网结款";
            $partner_bank_ids[] = ternary($v['id'], '');
        }
        unset($v);

        $model = M();
        $model->startTrans();

        // 更新状态
        $res = $partner_bank->where(array('id' => array('in', $partner_bank_ids)))->save(array('down_type' => 'Y'));
        if (!$res && intval($res) != count($partner_bank_ids)) {
            $model->rollback();
            $this->redirect_message(U('Financial/auditConsumptionSet'), array('error' => base64_encode('下载失败，稍后重试！')));
        }
        // 记录操作日志
        $res = $this->addOperationLogs("操作：下载打款信息,partner_bank_id:[" . implode(',', $partner_bank_ids) . "],数据条数:" . count($partner_bank_ids) . "");
        if (!$res) {
            $model->rollback();
            $this->redirect_message(U('Financial/auditConsumptionSet'), array('error' => base64_encode('下载失败，稍后重试！')));
        }
        $model->commit();

        $head = array(
            'money' => '金额',
            'manage_account' => '付款人账号',
            'manage_user' => '付款人名称',
            'account' => '收款人账号',
            'username' => '收款人名称',
            'bank_name' => '收款账号开户行名称',
            'bank_pro' => '收款方所在省',
            'bank_city' => '收款方所在市县',
            'bank_type' => '转账类型',
            'bank_large_no'=>'大额行号',
            'remark' => '汇款用途',
        );

        $file_name = date('Y-m-d') . '商家结款下载表' . date('YmdHis');
        partner_down_xls($partner_bank_res, $head, $file_name);
    }
    public function hfpartnerBankDownload() {

        $where = array(
            'partner_bank.down_type' => 'N',
            'partner_pay.paymark' => 0
        );
        $partner_bank = M('partner_bank');
        $partner_bank_res = $partner_bank->field('partner_bank.*,partner_pay.is_express')->where($where)->join('inner join partner_pay on partner_pay.id=partner_bank.pay_id')->select();
        if (!$partner_bank_res || empty($partner_bank_res)) {
            $this->redirect_message(U('Financial/auditConsumptionSet'), array('error' => base64_encode('暂时没有数据！请处理后再下载！')));
        }
        $partner = M('partner');
        $bank_no=$partner->getField('id,bank_large_no');
        // 结果处理
        $partner_bank_ids = array();
        foreach ($partner_bank_res as &$v) {
            $v['manage_account'] = self::BANK_ACCESS;
            $v['manage_user'] = self::BANK_ACCESS_USER;
            $v['bank_large_no']=$bank_no[$v['partner_id']];
            $v['remark'] = "{$v['partner_title']}{$v['partner_id']}青团网结款";
            $v['hangwai'] = "行外";
            $v['bank_type'] = "普通";
            //$v['yongtou'] = "青团网商家结款恒丰";
           // $v['remark']=substr($v['remark'],0,30);
            $v['yongtou'] = substr($v['remark'],0,30);
            $v['remark']="青团网商家结款恒丰";
            $partner_bank_ids[] = ternary($v['id'], '');

        }
        unset($v);

        $model = M();
        $model->startTrans();

        // 更新状态
        $res = $partner_bank->where(array('id' => array('in', $partner_bank_ids)))->save(array('down_type' => 'Y'));
        if (!$res && intval($res) != count($partner_bank_ids)) {
            $model->rollback();
            $this->redirect_message(U('Financial/auditConsumptionSet'), array('error' => base64_encode('下载失败，稍后重试！')));
        }
        // 记录操作日志
        $res = $this->addOperationLogs("操作：下载打款信息,partner_bank_id:[" . implode(',', $partner_bank_ids) . "],数据条数:" . count($partner_bank_ids) . "");
        if (!$res) {
            $model->rollback();
            $this->redirect_message(U('Financial/auditConsumptionSet'), array('error' => base64_encode('下载失败，稍后重试！')));
        }
        $model->commit();

        $head1 = array(
            'money' => '金额',
            'manage_account' => '付款人账号',
            'manage_user' => '付款人名称',
            'account' => '收款人账号',
            'username' => '收款人名称',
            'bank_name' => '收款账号开户行名称',
            'bank_pro' => '收款方所在省',
            'bank_city' => '收款方所在市县',
            'bank_type' => '转账类型',
            'bank_large_no'=>'大额行号',
            'remark' => '汇款用途',
        );
        $head = array(
            'account' => '收款账号',
            'username' => '收款账号户名',
            'bank_name' => '收款账号开户行',
            'bank_large_no'=>'联行号',
            'hangwai'=>'行外',
            'money' => '交易金额',
            'bank_type' => '转账类型',
            'yongtou' => '用途',
            'remark' => '备注',
        );
        $file_name = date('Y-m-d') . '商家结款下载表' . date('YmdHis');
        partner_down_xls($partner_bank_res, $head, $file_name);
    }
    /**
     * 现金支付
     */
    public function cashPayment() {

        $start_time = I('get.start_time', '', 'strval');
        $end_time = I('get.end_time', '', 'strval');
        $operation_type = I('get.operation_type', '', 'strval');
        $operation_type = strtolower($operation_type);
        $where = array('`order`.service' => 'cash', '`order`.state' => 'pay');
        if (trim($start_time) && trim($end_time)) {
            $where['`order`.pay_time'] = array(array('egt', strtotime($start_time)), array('elt', strtotime($end_time)));
        }

        $order = M('order');
        $count = $order->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'team.id' => 'team_id',
            'team.`product`' => 'team_product',
            'team.`city_id`' => 'team_city_id',
            '`user`.`username`' => 'user_username',
            '`user`.`email`' => 'user_email',
            '`user`.`id`' => 'user_id',
            '`user`.`mobile`' => 'user_mobile',
            '`order`.`id`' => 'order_id',
            '`order`.`service`' => 'order_service',
            '`order`.`pay_time`' => 'order_pay_time',
            '`order`.`money`' => 'order_money',
            'order.adminremark' => 'order_adminremark',
            'admin_user.`id`' => 'admin_user_id',
            'admin_user.`username`' => 'admin_user_username',
            'admin_user.`email`' => 'admin_user_email'
        );
        $list = $order->field($field)->order(array('`order`.pay_time' => 'desc'))->where($where)
                ->join('INNER JOIN team ON team.id=`order`.`team_id`')
                ->join('inner join `user` on `user`.id=`order`.`user_id`')
                ->join('LEFT JOIN `user` AS admin_user ON admin_user.id=`order`.`admin_id`')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        if (trim($operation_type) == 'cashpaymentdownload') {
            $head = array(
                'team_product' => '项目名称',
                'user_email_username' => 'Email/用户名',
                'order_service_name_time' => '动作',
                'order_money_name' => '金额',
                'admin_user_email_username' => '操作员',
                'team_city_name' => '城市',
                'order_adminremark' => '备注',
            );
            $city_res = $this->_getCategoryList('city');
            if ($list) {//
                foreach ($list as &$v) {
                    $v['team_city_name'] = ternary($city_res[$v['team_city_id']]['name'], '');
                    $v['user_email_username'] = ternary($v['user_email'], '') . '/' . ternary($v['user_username'], '');
                    $v['order_service_name_time'] = '现金支付/' . date('Y-m-d H:i', ternary($v['order_pay_time'], ''));
                    $v['order_money_name'] = '￥' . ternary($v['order_money'], '');
                    $v['admin_user_email_username'] = ternary($v['admin_user_username'], '') . '/' . ternary($v['admin_user_email'], '');
                }
                unset($v);
            }
            $file_name = '现金支付' . date('YmdHis');
            download_xls($list, $head, $file_name);
            exit();
        }

        // 计算总金额
        $all_money = $order->where($where)->sum('money');

        $data = array(
            'end_time' => $end_time,
            'start_time' => $start_time,
            'all_money' => $all_money,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 现金支付详情
     */
    public function viewCashPayment() {

        $order_id = I('get.order_id', '', 'strval');
        if (!trim($order_id)) {
            $this->redirect_message(U('Financial/cashPayment'), array('error' => base64_encode('订单id不能为空！')));
        }

        $where = array(
            'order.id' => $order_id,
        );
        $order = M('order');
        $field = array(
            'order.id' => 'order_id',
            'order.state' => 'order_state',
            'order.quantity' => 'order_quantity',
            'order.credit' => 'order_credit',
            'order.service' => 'order_service',
            'order.money' => 'order_money',
            'order.origin' => 'order_origin',
            'order.pay_time' => 'order_pay_time',
            'order.mobile' => 'order_mobile',
            'order.adminremark' => 'order_adminremark',
            'order.rereason' => 'order_rereason',
            'order.retime' => 'order_retime',
            'order.dorefund' => 'order_dorefund',
            'order.yuming' => 'order_yuming',
            'order.create_time' => 'order_create_time', 'order.pay_id' => 'order_pay_id',
            'user.mobile' => 'user_mobile',
            'user.username' => 'user_username',
            'user.email' => 'user_email',
            'team.id' => 'team_id',
            'team.product' => 'team_product',
            'team.delivery' => 'team_delivery',
        );
        $orderRes = $order->field($field)->where($where)
                        ->join('INNER JOIN user ON user.id=`order`.user_id')
                        ->join('INNER JOIN team ON team.id=`order`.team_id')->find();
        if (!$orderRes) {
            $this->redirect_message(U('Financial/cashPayment'), array('error' => base64_encode('订单信息不能为空！')));
        }

        // 团购券
        $orderRes['coupon_list'] = array();
        if (isset($orderRes['team_delivery']) && trim($orderRes['team_delivery']) == 'coupon') {
            $field = array('id', 'consume');
            $coupon_list = M('coupon')->where(array('order_id' => $order_id))->field()->select();
            // 处理数据
            if ($coupon_list) {
                foreach ($coupon_list as & $v) {
                    $v['consume_name'] = ternary($this->option_state[$v['consume']], '');
                } unset($v);
                $orderRes['coupon_list'] = $coupon_list;
            }
        }

        // 整理数据
        $orderRes['order_adminremark'] = htmlspecialchars($orderRes[
                'order_adminremark']);
        $orderRes['order_state_name'] = ternary($this->paystate[$orderRes['order_state']], '未知');
        $orderRes['order_yuming_name'] = order_from($orderRes['order_yuming']);
        $orderRes['order_service_name'] = '';
        if (isset($orderRes['order_service']) && trim($orderRes['order_service']) != 'credit') {
            $orderRes['order_service_name'] = order_service($orderRes['order_service'], '第三方支付');
        }

        $data = array(
            'order' => $orderRes,
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 提现记录
     */
    public function withdrawRecord() {
        $where = array('flow.`action`' => 'withdraw');
        $flow = M('flow');
        $count = $flow->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'flow.id' => 'flow_id',
            'flow.`money`' => 'flow_money',
            'flow.`create_time`' => 'flow_create_time',
            'flow.action' => 'flow_action',
            '`user`.`email`' => 'user_email',
            '`user`.`id`' => 'user_id',
            '`user`.`mobile`' => 'user_mobile',
            '`user`.`username`' => 'user_username',
            'admin_user.`id`' => 'admin_user_id',
            'admin_user.`username`' => 'admin_user_username',
            'admin_user.`email`' => 'admin_user_email'
        );
        $list = $flow->field($field)->order(array('`flow`.id' => 'desc'))->where(
                        $where)
                ->join('INNER JOIN `user` ON `user`.id=flow.`user_id`')->join('LEFT JOIN `user` AS admin_user ON admin_user.id=`flow`.`admin_id`')->limit($page->firstRow . ',' . $page->listRows)
                ->select();

        // 计算总金额
        $all_money = $flow->where($where)->sum('money');
        $data = array(
            'all_money' => $all_money,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );

        $this->assign($data);
        $this->display();
    }

    /**
     * 退款记录
     */
    public function refundRecord() {
        $team_id = I('get.team_id', '', 'trim');
        $admin_name = I('get.admin_name', '', 'trim');
        $user_name = I('get.user_name', '', 'trim');

        $where = array('action' => 'refund');
        if ($team_id) {
            $where['team.id'] = $team_id;
        }
        $admin_where_string = '';
        if ($admin_name) {
            $admin_where_string = "(admin_user.username like '%$admin_name%' OR admin_user.email like '%$admin_name%')";
        }
        $user_where_string = '';
        if ($user_name) {
            $user_where_string = "(user.username like '%$user_name%' OR user.email like '%$user_name%')";
        }
        $where_string = '';
        if (trim($admin_where_string)) {
            $where_string = $admin_where_string;
        }
        if (trim($user_where_string)) {
            $where_string = $user_where_string;
        } if (trim($user_where_string) && trim($admin_where_string)) {
            $where_string = "$user_where_string AND $admin_where_string";
        }
        if (trim($where_string)) {
            $where['_string'] = $where_string;
        }
        $flow = M('flow');
        $count = $flow->where($where)
                ->join('INNER JOIN team ON  team.id=flow.detail_id')
                ->join('LEFT JOIN `user` ON `user`.id=flow.`user_id`')
                ->join('LEFT JOIN `user` AS admin_user ON admin_user.id=`flow`.`admin_id`')
                ->count();
        $page = $this->pages($count, $this->reqnum);
        $field = array(
            'flow.id' => 'flow_id',
            'flow.`money`' => 'flow_money',
            'flow.`create_time`' => 'flow_create_time',
            'flow.action' => 'flow_action',
            '`user`.`email`' => 'user_email',
            '`user`.`id`' => 'user_id',
            '`user`.`mobile`' => 'user_mobile',
            '`user`.`username`' => 'user_username',
            'admin_user.`id`' => 'admin_user_id',
            'admin_user.`username`' => 'admin_user_username',
            'admin_user.`email`' => 'admin_user_email',
            'team.id ' => 'team_id',
            'team.`product`' => ' team_product'
        );
        $list = $flow->field($field)->order(array('`flow`.id' => 'desc'))->where($where)
                ->join('INNER JOIN team ON  team.id=flow.detail_id')
                ->join('LEFT JOIN `user` ON `user`.id=flow.`user_id`')
                ->join('LEFT JOIN `user` AS admin_user ON admin_user.id=`flow`.`admin_id`')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();

        // 计算总金额
        $all_money = $flow->where($where)
                ->join('INNER JOIN team ON  team.id=flow.detail_id')
                ->join('LEFT JOIN `user` ON `user`.id=flow.`user_id`')
                ->join('LEFT JOIN `user` AS admin_user ON admin_user.id=`flow`.`admin_id`')
                ->sum('flow.money');

        $data = array(
            'team_id' => $team_id,
            'admin_name' => $admin_name,
            'user_name' => $user_name,
            'all_money' => $all_money,
            'count' => $count,
            'list' => $list,
            'page' => $page->show(),
        );
        $this->assign($data);
        $this->display();
    }

    /**
     * 代理结算
     */
    public function agentSettlement() {
        $city_id = I('get.city_id', '', 'trim');
        $is_paymark = I('get.is_paymark', '', 'trim');
        $apply_date = I('get.apply_date', '', 'trim');
        $pay_date = I('get.pay_date', '', 'trim');
        $down_type = I('get.down_type', '', 'strval');
        $where = array(
        );

        // 申请时间
        if (trim($apply_date) && strtotime($apply_date)) {
            $where['agent_pay.create_time'] = array(array('egt', strtotime($apply_date)), array('lt', strtotime('+1 day ' . $apply_date)));
        }
        // 结算时间
        if (trim($pay_date) && strtotime($pay_date)) {
            $where['agent_pay.pay_time'] = array(array('egt', strtotime($pay_date)), array('lt', strtotime('+1 day ' . $pay_date)));
        }
        // 城市区域
        if (trim($city_id)) {
            $where['agent_pay.city_id'] = $city_id;
        }
        // 是否结算
        if (trim($is_paymark)) {
            $where['agent_pay.paymark'] = trim($is_paymark) == "Y" ? 1 : 0;
        }

        $agent_pay = M('agent_pay');
        $count = $agent_pay->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        //2016.4.14加下载
        if($down_type){
            $where['down_type']='N';
            $list = $agent_pay->order(array('paymark' => 'asc', 'id' => 'desc'))->where($where)
                ->select();
        }else{
            $list = $agent_pay->order(array('paymark' => 'asc', 'id' => 'desc'))->where($where)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        }

        $city_res = $this->_getCategoryList('city');
        if ($list) {
            $agent_user_ids = $agent_city_ids = array();
            foreach ($list as &$v) {
                if (isset($v['apply_agent_id']) && trim($v['apply_agent_id'])) {
                    $agent_user_ids[$v['apply_agent_id']] = $v['apply_agent_id'];
                }
                if (isset($v['city_id']) && trim($v['city_id'])) {
                    $agent_city_ids[$v['city_id']] = $v['city_id'];
                }
            }
            unset($v);
            $agent_user_info = array();
            if ($agent_user_ids) {
                $agent_user_where = array(
                    'id' => array('in',array_keys($agent_user_ids)),
                );
                $agent_user_info = M('user')->where($agent_user_where)->getField('id,username,mobile', true);
            }
            
            $agent_city_info = array();
            if($agent_city_ids){
                $agent_city_where = array(
                    'id' => array('in',array_keys($agent_city_ids)),
                );
                $agent_city_info = M('category')->where($agent_city_where)->getField('id,name,agent_bank_no,agent_bank_name,agent_bank_user', true);
            }
           
            foreach ($list as &$v) {
                $v['agent_user_name'] = ternary($agent_user_info[$v['apply_agent_id']]['username'], '');
                $v['agent_user_mobile'] = ternary($agent_user_info[$v['apply_agent_id']]['mobile'], '');
                $v['agent_bank_no'] = ternary($agent_city_info[$v['city_id']]['agent_bank_no'], '');
                $v['agent_bank_name'] = ternary($agent_city_info[$v['city_id']]['agent_bank_name'], '');
                $v['agent_bank_user'] = ternary($agent_city_info[$v['city_id']]['agent_bank_user'], '');
                $v['city_name'] = ternary($city_res[$v['city_id']]['name'], '');
                $agent_pay_ids[] = ternary($v['id'], '');
            }
            unset($v);
        }
        if($down_type){
            $model = M();
            $model->startTrans();

            // 更新状态
            $res = $agent_pay->where(array('id' => array('in', $agent_pay_ids)))->save(array('down_type' => 'Y'));
            if (!$res && intval($res) != count($agent_pay_ids)) {
                $model->rollback();
                $this->redirect_message(U('Financial/agentSettlement'), array('error' => base64_encode('下载失败，稍后重试！')));
            }
            // 记录操作日志
            $res = $this->addOperationLogs("操作：下载代理打款信息,partner_bank_id:[" . implode(',', $agent_pay_ids) . "],数据条数:" . count($agent_pay_ids) . ",");
            if (!$res) {
                $model->rollback();
                $this->redirect_message(U('Financial/agentSettlement'), array('error' => base64_encode('下载失败，稍后重试！')));
            }
            $model->commit();

            $head = array(
                'agent_bank_no' => '账号',
                'agent_bank_user' => '户名',
                'money' => '金额',
                'yongtu' => '用途',
            );
            if ($list) {//
                foreach ($list as $k=>&$v) {
                    $v['yongtu']=$v['city_name'].'代理费用结算';
                }
                unset($v);
            }
            $file_name = '代理结算' . date('YmdHis');
            partner_down_xls($list, $head, $file_name);
            exit();
        }
        $data = array(
            'list' => $list,
            'page' => $page->show(),
            'count' => $count,
            'city_id' => $city_id,
            'is_paymark' => $is_paymark,
            'apply_date' => $apply_date,
            'pay_date' => $pay_date,
            'citys' => $city_res,
        );

        $this->assign($data);

        $this->display();
    }

    /**
     * 审核待结算
     */
    public function agentshenh(){
        $agent_pay_id = I('get.id', '', 'trim');
        if (!$agent_pay_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '记录id不能为空！'));
        }
        $agent_pay_count = M('agent_pay')->where(array('id' => $agent_pay_id))->find();
        if (!$agent_pay_count) {
            $this->ajaxReturn(array('code' => -1, 'error' => '要审核记录不存在！'));
        }
        $model = M();
        $model->startTrans();
        unset ($agent_pay_count['id']);
        unset ($agent_pay_count['down_type']);
        $agent_pay_count['agent_pay_id']=$agent_pay_id;
        $data = $agent_pay_count;
        //变更审核状态
        $res = M('agent_pay')->where(array('id' => $agent_pay_id))->save(array('down_type'=>'Y'));
         if (!$res) {
             $model->rollback();
             $this->redirect_message(U('Financial/agentSettlement'), array('error' => base64_encode('审核失败')));
         }
        $ress = M('agent_pay_shen')->add($data);
        if (!$ress) {
            $model->rollback();
            $this->redirect_message(U('Financial/agentSettlement'), array('error' => base64_encode('审核失败')));
        }
        $model->commit();
        $this->addOperationLogs("操作：结算审核操作,agent_pay_id:{$res}");
        $this->ajaxReturn(array('code' => 0));
    }
    /**
     * 审核待结算
     */
    public function agentquxiao(){
        $agent_pay_id = I('get.id', '', 'trim');
        if (!$agent_pay_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '记录id不能为空！'));
        }
        $agent_pay_count = M('agent_pay_shen')->where(array('agent_pay_id' => $agent_pay_id))->find();
        if (!$agent_pay_count) {
            $this->ajaxReturn(array('code' => -1, 'error' => '要审核记录不存在！'));
        }
        $model = M();
        $model->startTrans();
        //变更审核状态
        $res = M('agent_pay_shen')->where(array('agent_pay_id' => $agent_pay_id))->delete();
        if (!$res) {
            $model->rollback();
            $this->redirect_message(U('Financial/agentSettlement'), array('error' => base64_encode('审核失败')));
        }
        $ress = M('agent_pay')->where(array('id' => $agent_pay_id))->save(array('down_type'=>'N'));
        if (!$ress) {
            $model->rollback();
            $this->redirect_message(U('Financial/agentSettlement'), array('error' => base64_encode('审核失败')));
        }
        $model->commit();
        $this->addOperationLogs("操作：结算审核操作,取消审核:{$res}");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 下载审核代理结算
     */
    public function  agentSettlementdown(){
        $pay_date = I('get.pay_date', '', 'trim');
        $down_type = I('get.down_type', '', 'strval');
        $where = array(
        );

        // 结算时间
        if (trim($pay_date) && strtotime($pay_date)) {
            $where['agent_pay.pay_time'] = array(array('egt', strtotime($pay_date)), array('lt', strtotime('+1 day ' . $pay_date)));
        }

        $agent_pay = M('agent_pay_shen');
        $count = $agent_pay->where($where)->count();
        $page = $this->pages($count, $this->reqnum);
        //2016.4.14加下载
        if($down_type){
            $where['down_type']='N';
            $list = $agent_pay->order(array('paymark' => 'asc', 'id' => 'desc'))->where($where)
                ->select();
        }else{
            $list = $agent_pay->order(array('paymark' => 'asc', 'id' => 'desc'))->where($where)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        }

        $city_res = $this->_getCategoryList('city');
        if ($list) {
            $agent_user_ids = $agent_city_ids = array();
            foreach ($list as &$v) {
                if (isset($v['apply_agent_id']) && trim($v['apply_agent_id'])) {
                    $agent_user_ids[$v['apply_agent_id']] = $v['apply_agent_id'];
                }
                if (isset($v['city_id']) && trim($v['city_id'])) {
                    $agent_city_ids[$v['city_id']] = $v['city_id'];
                }
            }
            unset($v);
            $agent_user_info = array();
            if ($agent_user_ids) {
                $agent_user_where = array(
                    'id' => array('in',array_keys($agent_user_ids)),
                );
                $agent_user_info = M('user')->where($agent_user_where)->getField('id,username,mobile', true);
            }

            $agent_city_info = array();
            if($agent_city_ids){
                $agent_city_where = array(
                    'id' => array('in',array_keys($agent_city_ids)),
                );
                $agent_city_info = M('category')->where($agent_city_where)->getField('id,name,agent_bank_no,agent_bank_name,agent_bank_user', true);
            }

            foreach ($list as &$v) {
                $v['agent_user_name'] = ternary($agent_user_info[$v['apply_agent_id']]['username'], '');
                $v['agent_user_mobile'] = ternary($agent_user_info[$v['apply_agent_id']]['mobile'], '');
                $v['agent_bank_no'] = ternary($agent_city_info[$v['city_id']]['agent_bank_no'], '');
                $v['agent_bank_name'] = ternary($agent_city_info[$v['city_id']]['agent_bank_name'], '');
                $v['agent_bank_user'] = ternary($agent_city_info[$v['city_id']]['agent_bank_user'], '');
                $v['city_name'] = ternary($city_res[$v['city_id']]['name'], '');
                $agent_pay_ids[] = ternary($v['id'], '');
            }
            unset($v);
        }
        if($down_type && is_array($agent_pay_ids)){
            $model = M();
            $model->startTrans();

            // 更新状态
            $res = $agent_pay->where(array('id' => array('in', $agent_pay_ids)))->save(array('down_type' => 'Y'));
            if (!$res && intval($res) != count($agent_pay_ids)) {
                $model->rollback();
                $this->redirect_message(U('Financial/agentSettlement'), array('error' => base64_encode('下载失败，稍后重试！')));
            }
            // 记录操作日志
            $res = $this->addOperationLogs("操作：下载代理打款信息,partner_bank_id:[" . implode(',', $agent_pay_ids) . "],数据条数:" . count($agent_pay_ids) . ",");
            if (!$res) {
                $model->rollback();
                $this->redirect_message(U('Financial/agentSettlement'), array('error' => base64_encode('下载失败，稍后重试！')));
            }
            $model->commit();

            $head = array(
                'agent_bank_no' => '账号',
                'agent_bank_user' => '户名',
                'money' => '金额',
                'yongtu' => '用途',
            );
            if ($list) {//
                foreach ($list as $k=>&$v) {
                    $v['yongtu']=$v['city_name'].'代理费用结算';
                }
                unset($v);
            }
            $file_name = '代理结算' . date('YmdHis');
            partner_down_xls($list, $head, $file_name);
            exit();
        }
        $data = array(
            'list' => $list,
            'page' => $page->show(),
            'count' => $count,
            'pay_date' => $pay_date,
            'citys' => $city_res,
        );

        $this->assign($data);

        $this->display();
    }
    /**
     * 发票快递查看
     */
    public function express_view() {
        $agent_pay_id = I('get.agent_pay_id', '', 'trim');

        $agent_pay_info = M('agent_pay')->where(array('id' => $agent_pay_id))->field('id,express_id,express_no,paymark')->find();
        if ($agent_pay_info) {
            $express_res = $this->_getCategoryList('express');
            $agent_pay_info['express_name'] = ternary($express_res[$agent_pay_info['express_id']]['name'], '');
            $data = array(
                'agent_pay_info' => $agent_pay_info
            );
            $this->assign($data);
        }

        $this->display();
    }

    /**
     * 代理发票快递查看物流
     */
    public function expressLogisticsView() {
        $agent_pay_id = I('get.agent_pay_id', '', 'trim');

        $agent_pay_info = M('agent_pay')->where(array('id' => $agent_pay_id))->field('id,express_id,express_no')->find();
        if ($agent_pay_info) {
            $express_res = $this->_getCategoryList('express');
            $agent_pay_info['express_name'] = ternary($express_res[$agent_pay_info['express_id']]['name'], '');


            $type = ternary($express_res[$order_res['express_id']]['ename'], '');
            $express_query = new \Common\Org\ExpressQuery();
            $data = array();
            $res = $express_query->express_query($type, $order_res['express_no']);
            if (isset($res['status']) && $res['status'] == 200 && isset($res['data'])) {
                $data = $res['data'];
            }

            $r_data = array(
                'agent_pay_info' => $agent_pay_info,
                'list' => $data
            );
            $this->assign($r_data);
        }

        $this->display();
    }

    /**
     * 异常设置
     */
    public function abnormal_submit() {
        $agent_pay_id = I('get.agent_pay_id', '', 'trim');

        if (IS_POST) {
            $agent_pay_id = I('post.agent_pay_id', '', 'trim');
            $money = I('post.money', '', 'trim');
            $content = I('post.content', '', 'trim');

            if (!$agent_pay_id) {
                $this->ajaxReturn(array('code' => -1, 'error' => '申请记录id不能为空！'));
            }

            $agent_pay_info = M('agent_pay')->where(array('id' => $agent_pay_id))->field('money')->find();
            if (!$agent_pay_info) {
                $this->ajaxReturn(array('code' => -1, 'error' => '修改的申请记录不存在！'));
            }

            if (!$money || doubleval($money) <= 0) {
                $this->ajaxReturn(array('code' => -1, 'error' => '非法金额！'));
            }

            if (!$content) {
                $this->ajaxReturn(array('code' => -1, 'error' => '备注不能为空！'));
            }

            $where = array(
                'id' => $agent_pay_id,
            );
            $up_data = array(
                'money' => $money,
                'old_money' => ternary($agent_pay_info['money'], '0.00'),
                'content' => $content,
            );
            $res = M('agent_pay')->where($where)->save($up_data);
            if ($res === false) {
                $this->ajaxReturn(array('code' => -1, 'error' => '异常设置失败！'));
            }
            $this->addOperationLogs("操作：代理结算异常处理,将原金额:[{$agent_pay_info['money']}]改为[{$money}]");
            $this->ajaxReturn(array('code' => 0));
            exit;
        }

        $agent_pay_info = M('agent_pay')->where(array('id' => $agent_pay_id))->field('id,money,content,paymark')->find();
        if ($agent_pay_info) {
            $data = array(
                'agent_pay_info' => $agent_pay_info
            );
            $this->assign($data);
        }
        $this->display();
    }

    /**
     * 代理结算操作
     */
    public function agentDoSettlement() {
        $agent_pay_id = I('get.agent_pay_id', '', 'trim');
        if (!$agent_pay_id) {
            $this->ajaxReturn(array('code' => -1, 'error' => '记录id不能为空！'));
        }
        $agent_pay_count = M('agent_pay')->where(array('id' => $agent_pay_id))->count();
        if (!$agent_pay_count || $agent_pay_count <= 0) {
            $this->ajaxReturn(array('code' => -1, 'error' => '要结算的记录不存在！'));
        }

        $data = array(
            'paymark' => 1,
            'handle_admin_id'=>$this->user['id'],
            'pay_time' => time(),
        );
        $res = M('agent_pay')->where(array('id' => $agent_pay_id))->save($data);
        if (!$res) {
            $this->ajaxReturn(array('code' => -1, 'error' => '代理结算失败！'));
        }
        $this->addOperationLogs("操作：代理结算操作,agent_pay_id:{$agent_pay_id}");
        $this->ajaxReturn(array('code' => 0));
    }

    /**
     * 代理结算查看明细
     */
    public function agentSettlementView() {
        $agent_pay_id = I('get.agent_pay_id', '', 'trim');
        $agent_pay_info = M('agent_pay')->where(array('id' => $agent_pay_id))->find();
        if ($agent_pay_info) {
            $order = D('Order');

            $cityId = $agent_pay_info['city_id'];

            $city_res = $this->_getCategoryList('city');

            $agent_pay_info['city_name'] = ternary($city_res[$cityId]['name'], '');

            $apply_begin_time = $agent_pay_info['apply_time'];

            $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));

            //1.  本月交易利润
            $month_profit = array();
            //$month_profit['all_money'] = $order->get_month_all_profit($cityId, $apply_begin_time, $apply_end_time);
            $month_profit['all_money'] = $order->reception_profit_res($cityId, $apply_begin_time, $apply_end_time);

            // 2. 支付费用统计 Payment of fees
            $payment_fees = $order->get_payment_fees($cityId, $apply_begin_time, $apply_end_time);

            // 3. 短信费用
            $sms_charges = $order->get_SMS_charges($cityId, $apply_begin_time, $apply_end_time);

            $data = array(
                'month' => $apply_begin_time,
                'agent_info' => $agent_pay_info,
                'plat_rate' => ternary($agent_pay_info['platform_rate'], 0),
                'all_profit_money' => ternary($month_profit['all_money']['profit_money'], 0),
                'month_profit' => $month_profit,
                'payment_fees' => $payment_fees,
                'sms_charges' => $sms_charges,
            );

            if ($data['plat_rate'] < 0.3) {
                $data['all_profit_money'] = sprintf("%.2f", $data['all_profit_money'] - $payment_fees['all_rate_money'] - $sms_charges['sms_money']);
            }
            $data['net_profit_money'] = sprintf("%.2f", $data['all_profit_money'] * (1 - $data['plat_rate']));
            $data['platform_rate_money'] = sprintf("%.2f", $data['all_profit_money'] * $data['plat_rate']);
            $this->assign($data);
        }
        $this->display();
    }

    /**
     *  本月交易利润  查看全部
     */
    public function withdrawals_profit_all() {
        $agent_pay_id = I('get.agent_pay_id', '', 'trim');
        $agent_pay_info = M('agent_pay')->where(array('id' => $agent_pay_id))->find();

        if ($agent_pay_info) {
            $cityId = $agent_pay_info['city_id'];
            $apply_begin_time = $agent_pay_info['apply_time'];
            $apply_end_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_begin_time));
            $data = $this->get_month_profit($cityId, $apply_begin_time, $apply_end_time);
            $this->assign($data);
        }
        $this->display();
    }

    /**
     *  获取本月利润表
     * @param type $city_id
     * @param type $apply_begin_time
     * @param type $apply_end_time
     * @param type $limit
     * @return type
     */
    protected function get_month_profit($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0, $limit = 20) {

        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }

        if (!$limit) {
            $limit = 20;
        }

        $where = array(
            'city_id' => $city_id,
            'partner_id' => array('gt', 0),
            '_string' => "rstate='berefund' or state='pay'",
            'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );

        $count = M('order')->where($where)->field('count(distinct(partner_id)) as count_order')->find();
        $count = isset($count['count_order']) && $count['count_order'] > 0 ? $count['count_order'] : 0;
        $page = $this->pages($count, $limit);
        $limit = $page->firstRow . ',' . $page->listRows;
        $list = M('order')->where($where)->group('partner_id')->limit($limit)->getField('partner_id,count(id) as order_count', true);
        if ($list) {
            $partner_ids = array_keys($list);

            // 获取商户名称
            $partner_info = M('partner')->where(array('id' => array('in', $partner_ids)))->getField('id,username', true);

            // 订单数（已减去退款） 交易金额（已减去退款）
            $order_where = array(
                'partner_id' => array('in', $partner_ids),
                'city_id' => $city_id,
                'rstate' => 'normal',
                'state' => 'pay',
                'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
            );

            $order_info = M('order')->where($order_where)->group('partner_id')->getField('partner_id,count(id) as order_count,sum(origin) as order_sum_money', true);

            // 退款
            $order_where['rstate'] = 'berefund';
            $order_where['state'] = 'unpay';
            $order_refund_info = M('order')->where($order_where)->group('partner_id')->getField('partner_id,count(id) as order_refund_count,sum(origin) as order_refund_sum_money', true);

            // 接待量
            $reception_profit_where = array(
                'team.city_id' => $city_id,
                'partner_income.partner_id' => array('in', $partner_ids),
                'partner_income.create_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
            );
            $reception_profit_info = M('partner_income')->where($reception_profit_where)
                            ->join('inner join team on team.id=partner_income.team_id')->group('partner_income.partner_id')->getField('partner_income.partner_id,count(partner_income.id) as reception_count,sum(team.team_price-team.ucaii_price) as profit_money', true);

            $_list = array();
            foreach ($list as $k => $v) {
                $_list[$k] = array(
                    'partner_id' => $k,
                    'partner_username' => ternary($partner_info[$k], ''),
                    'partner_order_count' => ternary($order_info[$k]['order_count'], 0),
                    'partner_order_sum_money' => ternary($order_info[$k]['order_sum_money'], 0),
                    'partner_order_refund_count' => ternary($order_refund_info[$k]['order_refund_count'], 0),
                    'partner_order_refund_sum_money' => ternary($order_refund_info[$k]['order_refund_sum_money'], 0),
                    'partner_reception_count' => ternary($reception_profit_info[$k]['reception_count'], 0),
                    'partner_profit_money' => ternary($reception_profit_info[$k]['profit_money'], 0),
                );
            }
            $list = $_list;
        }

        $data = array(
            'count' => $count,
            'page' => $page->show(),
            'list' => $list,
        );
        return $data;
    }
    
    /**
     * 区分农行非农行商家结算下载
     */
    public function settlementInfoDown(){
        $bank_type = I('post.bank_type','','trim');
        $is_bank_large_no = I('post.is_bank_large_no','','trim');
        $down_type = I('post.down_type','','trim');
        
        if(!$down_type){
            $this->display();
            exit;
        }
        
        
        $where = array(
            'partner_bank.down_type' => 'N',
        );
        
        // 是否农行卡
        if($bank_type){
            $bank_where = "partner.bank_name not like '%农%' OR partner.sbank not like '%农%'";
            if($bank_type=='Y'){
                $bank_where = "partner.bank_name like '%农%' OR partner.sbank like '%农%'";
            }
            if($bank_where){
                if(isset($where['_string']) && trim($where['_string'])){
                    $where['_string'] = "({$where['_string']}) and ({$bank_where})";
                }else{
                    $where['_string'] = $bank_where;
                }
            } 
        }
        
        // 是否有大额行号
        if($is_bank_large_no){
            $where['partner.bank_large_no'] = array('eq','');
            if($is_bank_large_no=='Y'){
                $where['partner.bank_large_no'] = array('neq','');
            }
        }
        
        $partner_bank = M('partner_bank');
        $partner_bank_res = $partner_bank->field('partner_bank.*,partner.bank_large_no')
                ->where($where)
                ->join('inner join partner on partner.id=partner_bank.partner_id')
                ->select();
        if (!$partner_bank_res || empty($partner_bank_res)) {
            $this->redirect_message(U('Financial/settlementInfoDown'), array('error' => base64_encode('暂时没有数据！请处理后再下载！')));
        }

        // 结果处理
        $partner_bank_ids = array();
        $partner_bank_data = array();
        foreach ($partner_bank_res as $k=>$v) {
            $partner_bank_data[] = array(
                'index'=>$k+1,
                'account'=>strtr($v['account'],array(' ' => '')),
                'username'=>$v['username'],
                'bank_large_no'=>$v['bank_large_no'],
                'money'=>$v['money'],
                'remark'=>"{$v['partner_username']}[{$v['partner_id']}]-青团网结款"
            );
            $partner_bank_ids[] = ternary($v['id'], '');
        }
        unset($v);

        $model = M();
        $model->startTrans();

        // 更新状态
        $res = $partner_bank->where(array('id' => array('in', $partner_bank_ids)))->save(array('down_type' => 'Y'));
        if (!$res && intval($res) != count($partner_bank_ids)) {
            $model->rollback();
            $this->redirect_message(U('Financial/settlementInfoDown'), array('error' => base64_encode('下载失败，稍后重试！')));
        }
        // 记录操作日志
        $res = $this->addOperationLogs("操作：区分农行非农行商家下载打款信息,partner_bank_id:[" . implode(',', $partner_bank_ids) . "],数据条数:" . count($partner_bank_ids) . ",是否农行:[{$bank_type}],是否有大额行号:[{$is_bank_large_no}]");
        if (!$res) {
            $model->rollback();
            $this->redirect_message(U('Financial/settlementInfoDown'), array('error' => base64_encode('下载失败，稍后重试！')));
        }
        $model->commit();

        $head = array(
            'index' => '序号',
            'account' => '账号',
            'username' => '户名',
            'money' => '金额',
            'remark' => '备注',
        );
        $file_name = '商家结款下载表';
        if($bank_type == 'Y'){
            $head = array(
                'index' => '序号',
                'account' => '账号',
                'username' => '户名',
                'bank_large_no' => '大额行号',
                'money' => '金额',
                'remark' => '备注',
            );
            $file_name = '农行卡商家结款下载表';
            if($is_bank_large_no == 'Y'){
                $file_name = '农行卡有大额行号商家结款下载表';
            }elseif($is_bank_large_no == 'N'){
                $file_name = '农行卡无大额行号商家结款下载表';
            }
        }elseif($bank_type == 'N'){
            $file_name = '非农行卡商家结款下载表';
            if($is_bank_large_no == 'Y'){
                $file_name = '非农行卡有大额行号商家结款下载表';
            }elseif($is_bank_large_no == 'N'){
                $file_name = '非农行卡无大额行号商家结款下载表';
            }
        }
        
        $_file_name = date('Y-m-d') . $file_name. date('YmdHis');
        download_csv($partner_bank_data, $head, $_file_name);
    }

    /**
     * 查看商家近10笔打款记录
     */
    public function selectPartnerSettleData(){
        $id = I('get.id',0,'int');
        $info = M('partner_pay')->find($id);
        if(!$id){
            $error = "请求参数不合法！";
            $this->assign('error',$error);
        }else{
            $data = M('partner_bank')->where(array('partner_id'=>$info['partner_id']))->order('id desc')->limit(10)->select();
            $this->assign('info',$info);
            $this->assign('data',$data);
        }
        $this->display();
    }

}
