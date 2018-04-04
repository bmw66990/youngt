<?php
/**
 * object 在线旅游门票模型
 * Created by JetBrains PhpStorm.
 * User: zhoombin@126.com
 * Date: 16-5-17
 * Time: 上午10:00
 */

namespace Common\Model;

use Common\Model\CommonModel;

class OtaModel extends CommonModel {

    private $oteam;
    private $object;
    private $status;

    public function __construct() {
        parent::__construct();
        $this->oteam  = C('OTA');
        $this->object = new \Common\Org\Ota();
        $this->status = array(
            'F' => 'F:已释放订单',
            'B' => 'B:作废',
            'R' => 'R:取消',
            'N' => 'N:未付款',
            'S' => 'S:已付款',
            'G' => 'G:已改签',
            'H' => 'H:已取纸质票',
            'T' => 'T:线上已检',
            'O' => 'O:线下已检',
            'M' => 'M:已退款',
            'E' => 'E:退款审核中',
            'A' => 'A:全部退票',
            'P' => 'P:部分退票'
        );
    }

    /**
     * 是否是OAT团单
     * @param $tid 团单号
     * @return mix
     */
    public function tmCheck($tid) {
        $ids = array_keys($this->oteam);
        if (in_array($tid,$ids)) {
            return $this->oteam[$tid];
        }
        return false;
    }

    /**
     * OAT获取产品
     * @param $parkcode 景区编号
     * @return mix
     */
    public function products($parkcode) {
        $res = $this->object->products($parkcode);
        $this->_log(var_export($res,true) , '获取产品结果');
        if ($res->IsTrue == 1 && $res->ResultCode == '00') {
            $rdata = json_decode($res->ResultJson);
            foreach ($rdata as $k => $v) {
                $v = (array)$v;
                $v['StartTime'] = $this->_getTime($v['StartTime']);
                $v['EndTime']   = $this->_getTime($v['EndTime']);
                $rdata[$k] = $v;
            }
            return $rdata;
        }
        $this->_log($res->ResultMsg);
        return false; 
    }

    /**
     * OAT订单锁定
     * @param $product 产品
     * @param $order   订单
     * @return boolen
     */
    public function orderLock($product, $order) {
        $res = $this->object->orderLock($product, $order);
        $this->_log(var_export($res,true), '下单结果');
        if ($res->IsTrue == 1 && $res->ResultCode == '00') {
            // 保存第三方订单信息
            $this->orderSave($product, $order);
            // 更新本地订单信息
            $this->localOrderUpdate($product, $order['order_id']);
            // 更新团单信息
            $this->teamInfoUpdate($product, $order);
            return true;
        }
        $this->_log($res->ResultMsg);
        return false;
    }

    /**
     * OAT订单支付完成
     * @param $order_id 订单号
     * @param $parkcode 景区编号
     * @return mix
     */
    public function orderEnd($order_id, $parkcode){
        $res = $this->object->orderEnd($order_id, $parkcode);
        $this->_log(var_export($res,true), '支付结果');
        if ($res->IsTrue && $res->ResultCode == '00') {
            $rdata = array_pop(json_decode($res->ResultJson));
            // 支付成功，补充订单信息
            $this->_afterPayUpdate((array)$rdata);
            return $rdata;
        }
        $this->_log($res->ResultMsg);
        return false;
    }

    // ItemType    string  M   明细状态（F：已释放订单，B：作废，R：取消，N:未付款，S:已付款，G：已改签  H：已取纸质票，T:线上已检，O：线下已检M 已退款,E退款审核中，A全部退票，P部分退票）
    // ECode   string  M   电子串码

    // 更新订单信息
    private function _afterPayUpdate($info) {
        $map = array(
            'order_id' => $info['OrderNo'],
            'team_id'  => $info['ItemID']
        );
        $data = array(
            'status'    => $this->status[$info['ItemType']],
            'ecode'     => $info['ECode'],
            'ecode_url' => $info['UrlECode']
        );
        $this->where($map)->save($data);
    }

    /**
     * OAT释放订单
     * @return boolen
     */
    public function orderRelease($order_id, $parkcode){
        $res = $this->object->orderRelease($order_id, $parkcode);
        $this->_log(var_export($res,true), '订单释放结果');
        if ($res->IsTrue && $res->ResultCode == '00') {
            return true;
        }
        $this->_log($res->ResultMsg);
        return false;
    }

    /**
     * OAT退款
     * @return boolen
     */
    public function orderRefund($order_id) {
        $info = $this->where(array('order_id'=>$order_id))->find();
        if (!$info) {
            return false;
        }
        $res = $this->object->orderRefund($info);
        $this->_log(var_export($res,true), '退票结果');
        if ($res->IsTrue && $res->ResultCode == '00') {
            $rdata = array_pop(json_decode($res->ResultJson));
            $this->_afterRefundUpdate((array)$rdata);
            return true;
        }
        $this->_log($res->ResultMsg);
        return false;
    }

    // 退票后更新订单
    private function _afterRefundUpdate($info) {
        $status = $this->_getOrderStatus($info['OrderNo']);
        if (!$status) {
            return false;
        }
        $map = array(
            'order_id' => $info['OrderNo'],
        );
        $data = array(
            'status'  => $this->status[$status]
        );
        $this->where($map)->save($data);
    }

    // 入园回调处理
    // {"otaOrderNO":"2599979","timestamp":"2016-06-01 04:27:20","postOrder":{"otaOrderNO":"2599979","OrderCode":"999176040106","SumCount":1,"InCount":1,"OrderStatus":"O"}}
    public function ecodeUsed($merCode,$params,$sign) {
        $res = $this->object->checkData($merCode,$params,$sign);
        if ($res === true) {
            $data = json_decode($params);
            // 更新订单
            if (false === $this->_afterUsedUpdate((array)$data->postOrder)) {
                $this->_log(var_export($params,true), '修改订单失败');
                return array('ResultCode' => '10','ResultMsg'  => 'fail');
            }
            return array('ResultCode' => '00','ResultMsg'  => 'success');
        }
        return $res;
    }

    // 入园后更新订单信息
    public function _afterUsedUpdate($info) {
        $map = array(
            'order_id' => $info['otaOrderNO'],
            'ecode'    => $info['OrderCode']
        );
        $data = array(
            'incount' => $info['InCount'],
            'status'  => $this->status[$info['OrderStatus']]
        );
        return $this->where($map)->save($data);
    }

    // 获取订单状态
    public function _getOrderStatus($order_id) {
        $order = $this->_getOrder($order_id);
        $res = $this->object->getOrderStatus($order_id, $order['parkcode'], $order['ecode']);
        $this->_log(var_export($res,true), '查询结果');
        if ($res->IsTrue && $res->ResultCode == '00') {
            $rdata = array_pop(json_decode($res->ResultJson));
            return $rdata->OrderStatus;
        }
        $this->_log($res->ResultMsg);
        return false;
    }

    // 转换时间
    private function _getTime($stamp) {
        $res = preg_match('/\(([0-9]*)\)/', $stamp, $match);
        if ($res) {
            $date = $match[1];
            return substr($date, 0, 10);
        }
        return false;
    }

    // 获取订单
    private function _getOrder($order_id) {
        return $this->where(array('order_id'=>$order_id))->find();
    }

    // 缓存表单信息
    public function saveTmpinfo($data) {
        session('ota_order_info',$data);
    }
    // 获取缓存信息
    public function getTmpinfo() {
        return session('ota_order_info');
    }
    // 清除临时数据
    public function clearTmpinfo() {
        session('ota_order_info', null);
    }

    // 保存ota信息
    public function orderSave($product, $order) {
        $data = array(
            'team_id'   => $order['team_id'],
            'parkcode'  => $product['ParkCode'],
            'link_name' => $order['link_name'],
            'link_phone'=> $order['mobile'],
            'link_cno'  => $order['link_cno'],
            'pro_id'    => $product['ProductID'],
            'pro_code'  => $product['ProductCode'],
            'pro_name'  => $product['ProductName'],
            'pro_price' => $product['ProductPrice'],
            'pro_sellprice' => $product['ProductSellPrice'],
            'pro_count' => $order['quantity'],
            'pro_sdate' => $order['use_date'],
            'pro_edate' => $order['use_date']
        );
        $map = array('order_id'=>$order['order_id']);
        $id = $this->where($map)->getField('id');
        if ($id) {
            $this->where($map)->save($data);
        } else {
            $data['order_id'] = $order['order_id'];
            $this->add($data);
        }
    }

    // 更新本地订单信息
    public function localOrderUpdate($product, $order_id) {
        $allowrefund = $product['CanReFund'] == 1 ? 'Y' : 'N';
        M('order')->where(array('order_id'=>$order_id))->setField('allowrefund', $allowrefund);
    }

    // 更新团单信息
    public function teamInfoUpdate($product, $order) {
        M('team')->where(array('id'=>$order['team_id']))->setField('expire_time', strtotime($order['use_date']) + 86400);
    }

    private function _log($content, $title = '', $level = 'INFO') {
        \Think\Log::write($title."OTA-------------------------------------------START\n",$level);
        \Think\Log::write($content, $level);
        \Think\Log::write($title."OTA---------------------------------------------END\n",$level);
    }

}
