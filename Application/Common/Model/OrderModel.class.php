<?php

/**
 * Order 订单模型
 * Created by JetBrains PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 15-3-19
 * Time: 下午16:26
 */

namespace Common\Model;

use Common\Model\CommonModel;

class OrderModel extends CommonModel {

    /**
     *  获取用户的消费记录
     *  @param array $where  : 获取数据信息的条件
     *  @param string $field : 获取数据信息字段名
     *
     *  @return array $list   : 返回数据信息
     */
    public function getUserTransaction($where, $limit, $orderby = 'id DESC', $field = '*') {
        $list = $this->field($field)->where($where)->limit($limit)->order($orderby)->select();
        if ($list === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $list;
    }

    /**
     *  通过筛选条件在 Order 表中找出符合条件的 User_id
     *  @param array $where  : 获取数据信息的条件
     *
     *  @return string $str   : 返回数据信息
     */
    public function getUserBuy($where, $having = NULL, $field = 'user_id') {
        $user_idArr = $this->field($field)->where($where)->group('user_id')->having($having)->select();
        if ($user_idArr === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        $user_ids = '';
        if ($user_idArr) {
            foreach ($user_idArr as $val) {
                if ($val['user_id']) {
                    $user_ids.=$val['user_id'] . ',';
                }
            }
            return substr($user_ids, 0, -1);
        } else {
            return false;
        }
    }

    /**
     *  	获取订单列表
     *  	@param array  $where : 获取数据信息的条件
     * 	@param string $limit : 分页
     * 	@param string $field : 需要查询的数据字段
     *  	@return array | bool : 返回数据信息
     */
    public function getOrders($where, $limit, $orderby = 'id DESC', $field = '*') {
        $data = $this->alias('o')
                ->field($field)
                ->join(array('left join `team` t ON o.team_id = t.id', 'left join `user` u ON o.user_id = u.id'))
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
     * 	获取订单详情
     * 	@param $id 			 : 团单id
     * 	@return array | bool : 返回数据信息
     */
    public function getOrderDetail($id) {
        $field = 'o.id,o.pay_id,o.remark,o.address,o.team_id,o.user_id,o.express,t.product,o.state,o.rstate,o.allowrefund,o.quantity,o.credit,o.money,o.service,o.yuming,o.optional_model,o.mobile,u.username,u.email,o.pay_time,o.create_time,o.refund_ptime,o.rereason';
        $info = $this->alias('o')
                ->field($field)
                ->join(array('left join `team` t ON o.team_id = t.id', 'left join `user` u ON o.user_id = u.id'))
                ->where("o.id=$id")
                ->find();
        if ($info == false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        if (!empty($info)) {
            $coupon = M('coupon')->field('id,consume,consume_time')->where("order_id=$id")->select();
            if ($coupon === false) {
                $this->errorInfo['info'] = $this->getDbError();
                $this->errorInfo['sql'] = $this->_sql();
            }
            foreach ($coupon as $key => $val) {
                $info['coupon'][] = $val;
            }
        }
        return $info;
    }

    /**
     * 分析表达式
     * @access protected
     * @param array $options 表达式参数
     * @return array
     */
    protected function _parseOptions($options = array()) {
        if (is_array($options))
            $options = array_merge($this->options, $options);

        if (!isset($options['table'])) {
            // 自动获取表名
            $options['table'] = $this->getTableName();
            $fields = $this->fields;
        } else {
            // 指定数据表 则重新获取字段列表 但不支持类型检测
            $fields = $this->getDbFields();
        }

        // 数据表别名
        if (!empty($options['alias'])) {
            $options['table'] = '`' . $options['table'] . '`' . ' ' . $options['alias'];
        }
        // 记录操作的模型名称
        $options['model'] = $this->name;

        // 字段类型验证
        if (isset($options['where']) && is_array($options['where']) && !empty($fields) && !isset($options['join'])) {
            // 对数组查询条件进行字段类型检查
            foreach ($options['where'] as $key => $val) {
                $key = trim($key);
                if (in_array($key, $fields, true)) {
                    if (is_scalar($val)) {
                        $this->_parseType($options['where'], $key);
                    }
                } elseif (!is_numeric($key) && '_' != substr($key, 0, 1) && false === strpos($key, '.') && false === strpos($key, '(') && false === strpos($key, '|') && false === strpos($key, '&')) {
                    if (!empty($this->options['strict'])) {
                        E(L('_ERROR_QUERY_EXPRESS_') . ':[' . $key . '=>' . $val . ']');
                    }
                    unset($options['where'][$key]);
                }
            }
        }
        // 查询过后清空sql表达式组装 避免影响下次查询
        $this->options = array();
        // 表达式过滤
        $this->_options_filter($options);
        return $options;
    }

    /**
     * 订单条数统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function orderNumCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['create_time'][] = array('egt', $stime);
        $where['create_time'][] = array('lt', $etime);
        //$where['_string']       = "(state='pay' OR (state='unpay' && rstate='normal'))";    //去掉退款的订单
        if ($type == 'daytotal') {
            //$where['state'] = 'pay';
            $data = $this->where($where)->count();
            return $data;
        } else {
            $data = $this->field('LEFT(FROM_UNIXTIME(create_time), ' . $len . ') ct,state,count(*) num')
                    ->where($where)
                    ->group('ct,state')
                    ->order('null')
                    ->select();
        }
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['state']][$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 订单成交额统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function orderAmountCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['create_time'][] = array('egt', $stime);
        $where['create_time'][] = array('lt', $etime);
        $data = $this->field('LEFT(FROM_UNIXTIME(create_time),' . $len . ') ct,sum(origin) num')
                ->where($where)
                ->group('ct')
                ->order('null')
                ->select();

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 订单金额统计
     * @param $city_id 城市id
     * @param $stime 开始时间
     * @param $etime 结束时间
     * @param string $type 统计类型 年/月/周 y/m/w
     * @return array
     */
    public function orderMoneyCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['create_time'][] = array('egt', $stime);
        $where['create_time'][] = array('lt', $etime);
        $data = $this->field('LEFT(FROM_UNIXTIME(create_time),' . $len . ') ct,state,sum(origin) num')
                ->where($where)
                ->group('ct,state')
                ->order('null')
                ->select();

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['state']][$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 订单支付类型统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function orderSourceCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['pay_time'][] = array('egt', $stime);
        $where['pay_time'][] = array('lt', $etime);
        $where['_string'] = "(state='pay') OR (state='unpay' && rstate='berefund')";
        if ($type == 'daytotal') {
            $data = $this->field('service,count(*) num')
                    ->where($where)
                    ->group('`service`')
                    ->order('null')
                    ->select();
        } else {
            $data = $this->field('LEFT(FROM_UNIXTIME(pay_time),' . $len . ') ct,service,count(*) num')
                    ->where($where)
                    ->group('ct,`service`')
                    ->order('null')
                    ->select();
        }
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                if (isset($row['ct'])) {
                    $list[$row['service']][$row['ct']] = $row['num'];
                } else {
                    $list[$row['service']][date('Y-m-d', $stime)] = $row['num'];
                }
            }
            return $list;
        }
    }

    /**
     * 订单分类占比统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return mixed
     */
    public function orderCategoryCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['o.city_id'] = $city_id;
        }
        $where['o.pay_time'][] = array('egt', $stime);
        $where['o.pay_time'][] = array('lt', $etime);
        $where['o.state'] = 'pay';

        if ($type == 'daytotal') {
            $data = $this->alias('o')
                    ->join('team t on o.team_id=t.id')
                    ->field('t.group_id cid,count(o.id) num')
                    ->where($where)
                    ->group('cid')
                    ->order('null')
                    ->select();
            return $data;
        } else {
            $data = $this->alias('o')
                    ->join('team t on o.team_id=t.id')
                    ->field('LEFT(FROM_UNIXTIME(o.pay_time),' . $len . ') ct,t.group_id cid,count(o.id) num')
                    ->where($where)
                    ->group('ct,cid')
                    ->order('null')
                    ->select();
        }

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['cid']][$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 支付金额统计
     * @param $city_id 城市id
     * @param $stime 开始时间
     * @param $etime 时间
     * @param string $type 类型
     * @return array|bool
     */
    public function payMoneyCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['pay_time'][] = array('egt', $stime);
        $where['pay_time'][] = array('lt', $etime);
        $where['state'] = 'pay';
        $data = $this->field('LEFT(FROM_UNIXTIME(pay_time),' . $len . ') ct,sum(credit) balance,sum(money) online,sum(origin) paytotal')
                ->where($where)
                ->group('ct')
                ->order('null')
                ->select();

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list['balance'][$row['ct']] = $row['balance'];
                $list['online'][$row['ct']] = $row['online'];
                $list['paytotal'][$row['ct']] = $row['paytotal'];
            }
            return $list;
        }
    }

    /**
     * 来源统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function sourcePayCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['pay_time'][] = array('egt', $stime);
        $where['pay_time'][] = array('lt', $etime);
        $where['_string'] = "(state='pay' OR (state='unpay' && rstate='berefund'))";
        $data = $this->field('LEFT(FROM_UNIXTIME(create_time), ' . $len . ') ct,yuming,sum(origin) num')
                ->where($where)
                ->group('ct,yuming')
                ->order('null')
                ->select();

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['yuming']][$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 订单来源数量统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function sourceNumCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['pay_time'][] = array('egt', $stime);
        $where['pay_time'][] = array('lt', $etime);
        $where['_string'] = "(state='pay' OR (state='unpay' && rstate='berefund'))";
        $data = $this->field('LEFT(FROM_UNIXTIME(create_time), ' . $len . ') ct,yuming,count(id) num')
                ->where($where)
                ->group('ct,yuming')
                ->order('null')
                ->select();

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['yuming']][$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 支付宝支付订单统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function aliPayCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['pay_time'][] = array('egt', $stime);
        $where['pay_time'][] = array('lt', $etime);
        $where['service'] = 'alipay';
        $where['_string'] = "(state='pay') OR (state='unpay' && rstate='berefund')";
        $data = $this->field('LEFT(FROM_UNIXTIME(create_time), ' . $len . ') ct,count(*) num')
                ->where($where)
                ->group('ct')
                ->order('null')
                ->select();

        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['ali']][$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 获取记录总条数
     * @param $where
     * @return mixed
     */
    public function getCount($where) {
        $condition = 'o.user_id=u.id';
        $count = $this->table('order o,user u')->where($condition)->where($where)->count();
        if ($count === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $count;
    }

    /**
     * 申请退款统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return mixed
     */
    public function orderRefundCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['state'] = 'pay';
        $where['retime'][] = array('egt', $stime);
        $where['retime'][] = array('lt', $etime);
        $where['rstate'] = 'askrefund';

        if ($type == 'daytotal') {
            unset($where['retime']);
            $where['team_id'] = array('gt', 0);
            $data = $this->where($where)->count();
            return $data;
        } else {
            $data = $this->field('LEFT(FROM_UNIXTIME(retime),' . $len . ') ct,count(*) num')
                    ->where($where)
                    ->group('ct')
                    ->order('null')
                    ->select();
        }
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 获取现金支付列表
     * @param $city_id 城市id
     * @param $limit 分页信息
     * @return array
     */
    public function getCashOrder($city_id, $limit) {
        $where['o.state'] = 'pay';  //已支付
        $where['o.service'] = 'cash';   //现金
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $condition = 'o.user_id=u.id';
        $data = $this->table('order o,user u')->field(' o.*,u.email,u.username')->where($condition)->where($where)->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        $team = array();
        foreach ($data as $row) {
            $team[] = $row['team_id'];
        }
        $map['id'] = array('in', array_unique($team));
        $teamList = M('Team')->where($map)->getField('id,product', true);
        if ($teamList === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return array('data' => $data, 'team' => $teamList);
    }

    // 订单状态统计
    public function stateCount($city_id,$stime,$etime,$len,$status='') {
        if (!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['create_time'][] = array('egt', $stime);
        $where['create_time'][] = array('elt',  $etime);
        switch ($status) {
            case 'pay':
                $where['state']  = 'pay';
                // $where['rstate'] = 'normal'; 
                break;
            case 'unpay':
                $where['state']  = 'unpay';
                $where['rstate'] = 'normal';
                break;
            case 'refund':
                $where['state']  = 'unpay';
                $where['rstate'] = 'berefund';
                break;
            default:
                // 统计所有订单
                break;
        }
        $data = $this->field('LEFT(FROM_UNIXTIME(create_time), '.$len.') ct, count(id) num')
                ->where($where)
                ->group('ct')
                ->select();
        // echo $this->getLastSql();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    // 劵号验证统计
    public function vCouponCount($city_id,$stime,$etime,$len) {
        if (!empty($city_id)) {
            $where['o.city_id'] = $city_id;
        }
        $where['o.pay_time'][] = array('egt', $stime);
        $where['o.pay_time'][] = array('lt',  $etime);
        $where['c.consume'] = 'Y';
        $data = $this->field('LEFT(FROM_UNIXTIME(o.create_time), '.$len.') ct, count(c.id) num')
                ->alias('o')
                ->join('LEFT JOIN coupon c ON c.order_id=o.id')
                ->where($where)
                ->group('ct')
                ->select();
        // echo $this->getLastSql();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    /**
     * 获取全部订单
     * @param $where
     * @param $sort
     * @param $limit
     * @return bool
     */
    public function getAllOrders($where, $sort, $limit, $field) {
        $data = $this->field($field)->where($where)->order($sort)->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        return array_map(array($this,'getOrderStatus'), $data);

        // $orderId = '';
        // foreach ($data as $key => $row) {
        //     if ($row['state'] == 'pay') {
        //         if ($row['rstate'] == 'askrefund') {
        //             $row['info'] = '申请退款';
        //             $row['status'] = 'applyrefund';
        //         } else if ($row['rstate'] == 'normal') {
        //             $orderId .= $row['id'] . ',';
        //         }
        //     } else {
        //         $row = $this->_getUnpayOrder($row);
        //     }
        //     unset($data[$key]);
        //     $data[$row['id']] = $row;
        // }
        // if ($orderId != '') {
        //     $orderId = substr($orderId, 0, -1);
        //     $data = $this->_getOrderState($orderId, $data);
        // }
        // return $data;
    }

    /**
     * 获取订单状态改进版
     * @param $order 订单数据
     */
    public function getOrderStatus($order) {
        if ($order['state'] != 'pay') {
        // 未付款
            if ($order['rstate'] == 'normal') {
                $order['info'] = '待付款';
                $order['status'] = 'unpay';
            } else if ($order['rstate'] == 'berefund') {
                $order['info'] = '已退款';
                $order['status'] = 'refund';
            }
        } else {
        // 已付款
            if ($order['rstate'] == 'askrefund') {
                $order['info'] = '申请退款';
                $order['status'] = 'applyrefund';
            } else if ($order['rstate'] == 'normal') {
                $coupons = M('Coupon')->where(array('order_id'=>$order['id']))->select();
                if ($coupons && count($coupons) > 0) {
                // 有劵号
                    $order['use_num'] = 0;
                    foreach ($coupons as $i => $coupon) {
                        if ($coupon['consume'] == 'Y') {
                            $order['use_num']++;
                        }
                    }
                    $order['unuse_num'] = count($coupons) - $order['use_num'];
                    if ($order['unuse_num'] == 0) {
                        $comment = M('Comment')->where(array('order_id'=>$order['id']))->getField('order_id,is_comment', true);                        
                        if ($comment[$order['id']] == 'Y') {
                            $order['status'] = 'review';
                            $order['info'] = '已评论';
                        } else {
                            $order['status'] = 'unreview';
                            $order['info'] = '未评论';
                        }
                    } else {
                        $row = array_pop($coupons);
                        if ($row['expire_time'] < strtotime(date('Y-m-d'))) {
                            $order['info'] = '已过期';
                            $order['status'] = 'expired';
                        } else {
                            $order['info'] = '未使用';
                            $order['status'] = 'unuse';
                            $order['expire_time'] = $row['expire_time'];
                        }
                    }
                } else {
                // 没劵号
                    $comment = M('Comment')->where(array('order_id'=>$order['id']))->getField('order_id,is_comment', true);
                    if ($comment[$order['id']] == 'Y') {
                        $order['status'] = 'review';
                        $order['info'] = '已评论';
                    } else {
                        $order['status'] = 'unreview';
                        $order['info'] = '未评论';
                    }
                    // 检测OTA
                    $ota = D('Ota');
                    if ($ota->tmCheck($order['team_id'])) {
                        $res = $ota->where(array('order_id'=>$order['id']))->find();
                        if ($res) {
                            if ($res['status']) {
                                $order['info'] = substr($res['status'], 2);
                            } else {
                                $order['info'] = '未知状态';
                            }
                            $order['pro_sdate'] = $res['pro_sdate'];
                            $order['pro_edate'] = $res['pro_edate'];
                        } else {
                            $order['info'] = '暂无信息';
                        }
                    }
                }
            }
        }
        return $order;
    }

    /**
     * 获取未支付/退款订单状态信息
     * @param $row
     * @return mixed
     */
    // 废弃--功能改进查看 _getOrderStatus()方法 -----------------------------------------------------------------------
    protected function _getUnpayOrder($row) {
        if ($row['rstate'] == 'normal') {
            $row['info'] = '待付款';
            $row['status'] = 'unpay';
        } else if ($row['rstate'] == 'berefund') {
            $row['info'] = '已退款';
            $row['status'] = 'refund';
        }
        return $row;
    }

    /**
     * 获取订单状态信息
     * @param $order
     * @param $orderList
     * @return mixed
     */
    // 废弃--功能改进查看 _getOrderStatus()方法 -----------------------------------------------------------------------
    protected function _getOrderState($order, $orderList) {
        //获取未使用的订单
        $map = array(
            'order_id' => array('IN', $order),
            'consume' => 'N'
        );

        $data = M('Coupon')->where($map)->group('order_id')->order('NULL')->getField('order_id,expire_time', true);
        $data = !empty($data) ? $data : array();
        $order = explode(',', $order);
        $used = array_diff($order, array_keys($data));
        $comment = array();
        if (!empty($used)) {
            $where['order_id'] = array('IN', $used);
            $comment = M('Comment')->where($where)->getField('order_id,is_comment', true);
        }
        if (!empty($data)) {
            // 获取已消费和未消费的总数
            $condition['order_id'] = array('IN', array_keys($data));
            $isUse = M('Coupon')->field('order_id,consume,count(*) num')->where($condition)->group('order_id,consume')->order('NULL')->select();
            $isUseList = array();
            foreach ($isUse as $row) {
                $isUseList[$row['order_id']][$row['consume']] = $row['num'];
            }
        }

        foreach ($orderList as $key => $row) {
            if (in_array($row['id'], $order)) {
                if (in_array($row['id'], array_keys($data))) {
                    $row['expire_time'] = $data[$row['id']];
                    if ($row['expire_time'] < strtotime(date('Y-m-d'))) {
                        $row['info'] = '已过期';
                    } else {
                        $row['info'] = '未使用';
                    }
                    $row['status'] = 'unuse';
                    $row['use_num'] = ternary($isUseList[$row['id']]['Y'], 0);
                    $row['unuse_num'] = ternary($isUseList[$row['id']]['N'], 0);
                } else if (isset($comment[$row['id']])) {
                    $row['info'] = '已使用';
                    if ($comment[$row['id']] == 'Y') {
                        $row['status'] = 'review';
                    } else {
                        $row['status'] = 'unreview';
                    }
                }
                $orderList[$key] = $row;
            }
        }
        return $orderList;
    }


    /**
     * 获取是否使用订单
     * @param $state
     * @param $limit
     * @return mixed
     */
    public function getIsUseOrders($user_id, $state, $where, $sort, $limit, $field = '', $isCount = false) {
        $map = array(
            'user_id' => $user_id,
            'consume' => 'N',
        );
        $coupon = M('Coupon')->where($map)->group('order_id')->order('NULL')->getField('order_id,expire_time', true);
        if ($coupon === false) {
            $this->errorInfo['info'] = M('Coupon')->getDbError();
            $this->errorInfo['sql'] = M('Coupon')->_sql();
            return false;
        }
        if (empty($coupon)) {
            if ($isCount)
                return 0;
            return array();
        }
        if ($state == 'Y') {
            //计算出总的order_id,去除已经未使用的,剩下的就是已经使用的order_id
            $total = M('Coupon')->where('user_id=' . $user_id)->group('order_id')->order('NULL')->getField('order_id', true);
            if ($total === false) {
                $this->errorInfo['info'] = M('Coupon')->getDbError();
                $this->errorInfo['sql'] = M('Coupon')->_sql();
                return false;
            }
            $consume = array_diff($total, array_keys($coupon));
            $total = count($total) - count($coupon);
            if (isset($where['_string']) && $where['_string']) {
                $where['_string'] .= ' && id IN(' . implode(',', $consume) . ')';
            } else {
                $where['_string'] = 'id IN(' . implode(',', $consume) . ')';
            }
        } else {
            if (isset($where['_string']) && $where['_string']) {
                $where['_string'] .= ' && id IN(' . implode(',', array_keys($coupon)) . ')';
            } else {
                $where['_string'] = 'id IN(' . implode(',', array_keys($coupon)) . ')';
            }
            $total = count(array_keys($coupon));
        }

        if ($isCount) {
            return $total;
        }

        if (empty($total)) {
            return array();
        }
        $field = $field ? $field : 'id,team_id,state,rstate,price,quantity,origin,allowrefund,create_time,pay_time';
        $data = $this->field($field)
                ->where($where)
                ->order($sort)
                ->limit($limit)
                ->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        if (empty($data)) {
            return array();
        }
        $orderId = array();
        foreach ($data as $li) {
            $orderId[] = $li['id'];
        }

        // 获取已消费和未消费的总数
        $condition['order_id'] = array('IN', $orderId);
        $isUse = M('Coupon')->field('order_id,consume,count(*) num')->where($condition)->group('order_id,consume')->order('NULL')->select();

        $isUseList = array();
        foreach ($isUse as $row) {
            $isUseList[$row['order_id']][$row['consume']] = $row['num'];
        }

        //如果已使用,判断是否已评价
        if ($state == 'Y') {
            $comment = M('Comment')->where($condition)->getField('order_id,is_comment', true);
        }
        foreach ($data as &$val) {
            if ($state == 'N') {
                $val['status'] = 'unuse';
                $val['expire_time'] = $coupon[$val['id']];
                if ($val['expire_time'] >= strtotime(date('Y-m-d'))) {
                    $val['info'] = '未使用';
                } else {
                    $val['info'] = '已过期';
                }
                $val['use_num'] = ternary($isUseList[$val['id']]['Y'], 0);
                $val['unuse_num'] = ternary($isUseList[$val['id']]['N'], 0);
            } else {
                $val['is_comment'] = ternary($comment[$val['id']], 'Y');
                $val['status'] = 'used';
                $val['info'] = '已使用';
            }
        }
        unset($val);
        return $data;
    }

    /**
     * 新版APP获取是否使用订单
     * @param $user_id
     * @param $state
     * @param $lastid
     * @param $sort
     * @param $limit
     * @return array
     */
    public function getNewIsUseOrders($user_id, $state, $lastid, $sort, $limit) {
        $map = array(
            'o.user_id' => $user_id,
            'o.state' => 'pay',
            'o.rstate' => 'normal',
            'c.consume' => $state
        );
        if ($lastid && $lastid > 0) {
            $map['o.' . $sort] = array('LT', $lastid);
        }

        $field = 'o.id,o.team_id,o.state,o.rstate,o.price,o.quantity,o.origin,o.allowrefund,o.create_time,o.pay_time';
        $having = '';
        if ($state == 'Y') {
            $field .= ",COUNT(c.id) t,SUM(c.`consume`='N') num";
            $having = 'num=0 AND t>0';
        }

        $data = $this->table('coupon c,`order` o')
                ->field($field)
                ->where('o.id=c.order_id')
                ->where($map)
                ->group('o.id')
                ->having($having)
                ->order('o.' . $sort . ' DESC')
                ->limit($limit)
                ->select();
        //die($data);
        if ($data && is_array($data)) {
            $order_id = array_column($data, 'id');
            $where = array(
                'order_id' => array('IN', $order_id)
            );
            $comment = M('Comment')->where($where)->getField('order_id,is_comment');
            foreach ($data as &$row) {
                $row['is_comment'] = ternary($comment[$row['id']], 'N');
                unset($row['num'], $row['t']);
            }
            unset($row);
        }
        return $data;
    }

    /**
     * 获取是否评价订单
     * @param $user_id
     * @param $state
     * @param $where
     * @param $sort
     * @param $limit
     * @param $is_comment
     * @return bool
     */
    public function getIsReviewOrders($user_id, $state, $where, $sort, $limit, $field = '', $is_comment = false) {
        $map = array(
            'user_id' => $user_id,
            'is_comment' => $state,
            'consume' => 'Y',
        );

        $comment = M('Comment')->where($map)->getField('order_id,create_time,comment_num,content,partner_content,is_pic', true);

        if ($comment === false) {
            $this->errorInfo['info'] = M('Comment')->getDbError();
            $this->errorInfo['sql'] = M('Comment')->_sql();
            return false;
        }
        if (empty($comment)) {
            return array();
        }

        if (isset($where['_string']) && $where['_string']) {
            $where['_string'] .= ' && id IN(' . implode(',', array_keys($comment)) . ')';
        } else {
            $where['_string'] = 'id IN(' . implode(',', array_keys($comment)) . ')';
        }

        $field = $field ? $field : 'id,team_id,state,rstate,price,quantity,origin,create_time';
        $data = $this->field($field)
                ->where($where)
                ->order($sort)
                ->limit($limit)
                ->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        if ($state == 'Y') {
            foreach ($data as &$val) {
                $val['review_time'] = $comment[$val['id']]['create_time'];
                if ($is_comment) {
                    $val['comment_num'] = $comment[$val['id']]['comment_num'];
                    $val['comment_sum'] = $comment[$val['id']]['comment_num'] * 20 . '%';
                    $val['content'] = $comment[$val['id']]['content'];
                    $val['partner_content'] = $comment[$val['id']]['partner_content'];
                    $val['is_pic'] = $comment[$val['id']]['is_pic'];
                    $val['score'] = ceil($val['origin']) * ($val['is_pic'] == 'Y' ? 2 : 1);
                }
            }
        }
        return $data;
    }

    /**
     * 检查订单是否存在
     * @param $id
     * @param null $uid
     * @return bool|mixed
     */
    public function isExistOrder($id, $uid = null) {
        if (is_null($uid)) {
            $data = $this->info($id);
        } else {
            $map = array(
                'id' => $id,
                'user_id' => $uid,
            );
            // field('id,mobile,create_time,quantity,price,city_id,team_id,state,rstate')
            $data = $this->where($map)->find();
        }
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        return $data;
    }

    /**
     * 检查订单是否可退款
     * @param $order
     * @return mixed
     */
    public function checkIsRefund($order) {
        if (is_numeric($order)) {
            $order = $this->info($order);
        }
        if ($order['allowrefund'] == 'N') {
            return array(
                'error' => '订单不允许退款',
                'code' => 1007
            );
        }
        if ($order['state'] == 'pay' && $order['rstate'] == 'askrefund') {
            return array(
                'error' => '订单已申请退款',
                'code' => 1008
            );
        }

        if ($order['state'] == 'unpay' && $order['rstate'] == 'berefund') {
            return array(
                'error' => '订单已退款',
                'code' => 1009
            );
        }

        if ($order['state'] == 'unpay') {
            return array(
                'error' => '订单未支付',
                'code' => 1009
            );
        }

        $teamRes = M('team')->where(array('id' => $order['team_id']))->field('team_type')->find();
        if (isset($teamRes['team_type']) && $teamRes['team_type'] == 'goods') {
            //            if ($order['mail_order_pay_state'] != 2) {
            //                return array(
            //                    'error' => '订单未确认收货，无法退款',
            //                    'code' => -1
            //                );
            //            }
            //
        //            // 商家结算的不能申请退款
            //            $partner_income_where = array(
            //                'is_express' => 'Y',
            //                'coupon_id' => $order['id'],
            //                'pay_id' => array('neq', 0),
            //            );
            //            $partner_income_count = M('partner_income')->where($partner_income_where)->count();
            //            if ($partner_income_count && $partner_income_count > 0) {
            //                return array(
            //                    'error' => '该订单商家已经结算，无法申请退款，详情联系客服！',
            //                    'code' => -1
            //                );
            //            }
        } else {
            // 获取OTA信息
            $ota = D('Ota');
            if (!$ota->tmCheck($order['team_id'])) {
                $map = array(
                    'consume' => array('neq', 'Y'),
                    'order_id' => $order['id'],
                );
                $coupon = M('Coupon')->where($map)->count();
                if (empty($coupon)) {
                    return array(
                        'error' => '订单已消费',
                        'code' => 1010
                    );
                }
            }
        }
        return true;
    }

    /**
     * 点击确认收货
     */
    public function orderConfirmReceipt($order_id, $uid = 0) {

        if (!$order_id) {
            return array('error' => '订单id不能为空！');
        }

        $where = array(
            'id' => $order_id,
            'rstate' => 'normal',
        );
        $order_res = M('order')->where($where)->field('user_id,state,mail_order_pay_state,team_id,partner_id,origin,ucaii_price,fare,quantity,team_type')->find();
        if (!$order_res) {
            return array('error' => '订单不存在！');
        }
        if ($uid && (!isset($order_res['user_id']) || intval($order_res['user_id']) !== intval($uid))) {
            return array('error' => '该订单不是自己的订单，不能点击收货！');
        }
        if (!isset($order_res['state']) || trim($order_res['state']) != 'pay') {
            return array('error' => '该订单未支付，不能点击收货！');
        }
        if (isset($order_res['mail_order_pay_state'])) {
            if (intval($order_res['mail_order_pay_state']) == 0) {
                return array('error' => '该订单未发货，不能点击收货！');
            }
            if (intval($order_res['mail_order_pay_state']) == 2) {
                return array('error' => '该订单已经收货，不能重复点击收货！');
            }
        }

        $model = M();
        $model->startTrans();

        $res = M('order')->where($where)->save(array('mail_order_pay_state' => 2, 'user_receipt_time' => time()));
        if ($res === false) {
            $model->rollback();
            return array('error' => '确认收货失败！');
        }
        $res = M('comment')->where(array('order_id' => $order_id))->save(array('consume' => 'Y'));
        if ($res === false) {
            $model->rollback();
            return array('error' => '确认收货失败！');
        }

        $money = ($order_res['ucaii_price'] * $order_res['quantity']) + $order_res['fare'];
        $money = sprintf("%.2f", $money);
        // 添加partner_income
        if (isset($order_res['team_type']) && trim($order_res['team_type']) == 'cloud_shopping') {
            $money = M('team')->where(array('id' => $order_res['team_id']))->getField('team_price');
        }
        if ($money > 0) {
            $data = array(
                'partner_id' => $order_res['partner_id'],
                'team_id' => $order_res['team_id'],
                'money' => $money,
                'coupon_id' => $order_id,
                'is_express' => 'Y',
                'create_time' => time(),
                'pay_id' => 0,
            );
            $res = M('partner_income')->add($data);
            if (!$res) {
                $model->rollback();
                return array('error' => '确认收货失败！');
            }
        }
        $model->commit();

        return array('message' => '收货成功！');
    }

    /**
     * 一元云购 点击确认领奖 （邮购类型领奖）
     */
    public function confirm_receive_prize($uid, $order_id, $address_id, $d_time, $address = array()) {
        if (!$uid) {
            return array('error' => '用户未登录！');
        }
        if (!$order_id) {
            return array('error' => '领奖的团单为空！');
        }
        if (!$address_id) {
            return array('error' => '请选择领奖地址！');
        }
        if ($address_id == 'newaddress' && !$address) {
            return array('error' => '请填写新地址！');
        }
        if (!$d_time) {
            return array('error' => '请选择送货时间！');
        }

        // 检验用户中奖信息是否正确
        $where = array(
            'winning_user_id' => $uid,
            'winning_order_id' => $order_id,
        );
        $cloud_shoping_result = M('cloud_shoping_result')->where($where)->field('status')->find();
        if (!$cloud_shoping_result) {
            return array('error' => '没有你的中奖信息，领奖失败！');
        }
        if (isset($cloud_shoping_result['status'])) {
            $cloud_shoping_result_status = intval($cloud_shoping_result['status']);
            if ($cloud_shoping_result_status == 0) {
                return array('error' => '未开奖，不能领取奖品！');
            }
            if ($cloud_shoping_result_status == 2) {
                return array('error' => '你已经领取奖品，不能重复领取！');
            }
        }

        // 收货地址处理
        if ($address_id == 'newaddress') {
            $address_id = D('Address')->addUserAddress($uid, $address);
            if (isset($address_id['error']) && trim($address_id['error'])) {
                return $address_id;
            }
        }
        $where = array(
            'user_id' => $uid
        );
        $address_info = D('Address')->where(array('id' => $address_id))->find();
        if (!$address_info) {
            return array('error' => '该地址无效，请重新选择地址！');
        }
        if (isset($address_info['user_id']) && $address_info['user_id'] != $uid) {
            return array('error' => '该地址不是本人地址，请重新选择地址！');
        }
        $address_info = @json_encode($address_info);

        $model = M();
        $model->startTrans();

        $data = array(
            'address' => $address_info,
            'address_id' => $address_id,
            'delivery_time' => $d_time,
        );
        $res = M('order')->where(array('id' => $order_id, 'user_id' => $uid))->save($data);
        if ($res === false) {
            $model->rollback();
            return array('error' => '领奖失败！');
        }

        $where = array(
            'winning_user_id' => $uid,
            'winning_order_id' => $order_id,
        );
        $res = M('cloud_shoping_result')->where($where)->save(array('status' => 2));
        if ($res === false) {
            $model->rollback();
            return array('error' => '领奖失败！');
        }
        $model->commit();

        return true;
    }
    
    /**
     *  我的众筹 列表
     * @param type $where
     * @param string $order_by
     * @param type $limit
     * @return type
     */
    public function cloud_shopping_order($where = array(),$order_by='',$limit=20){
        
        if(!$where){
            return array();
        }
        
        if(!$order_by){
            $order_by = 'order.pay_time desc';
        }
        $uid = 0;
        if(isset($where['order.user_id']) && trim($where['order.user_id'])){
            $uid = $where['order.user_id'];
        }
        if(!$uid){
            return array();
        }
        
        $order = M('order');
        $field = array(
            'order.team_id' => 'order_team_id',
            'order.now_periods_number' => 'order_now_periods_number',
            'sum(order.quantity)' => 'order_pay_count',
            'max(order.pay_time)' => 'order_last_pay_time',
            'max(order.id)' => 'order_last_id',
            'min(order.pay_time)' => 'order_min_pay_time',
            'min(order.id)' => 'order_min_id',
            'order.now_periods_number' => 'order_now_periods_number',
            'csr.status' => 'csr_status',
            'csr.winning_cloud_code' => 'csr_winning_cloud_code',
            'csr.winning_user_id' => 'csr_winning_user_id',
            'csr.begin_time' => 'csr_begin_time',
            'csr.max_number' => 'csr_max_number',
            'csr.winning_order_id' => 'csr_winning_order_id',
            'csr.status' => 'csr_status',
        );
        $order_list = $order->where($where)
                        ->join('left join cloud_shoping_result as csr on csr.team_id=order.team_id and order.now_periods_number=csr.periods_number')
                        ->field($field)->limit($limit)
                        ->group('order.team_id,order.now_periods_number')
                        ->order('order.pay_time desc')->select();
        if ($order_list) {
            $comment_ids = $user_ids = $order_ids = $team_ids = array();
            foreach ($order_list as &$v) {

                if (isset($v['order_team_id']) && trim($v['order_team_id'])) {
                    $team_ids[$v['order_team_id']] = $v['order_team_id'];
                }

                if (isset($v['csr_winning_user_id']) && trim($v['csr_winning_user_id'])) {
                    $user_ids[$v['csr_winning_user_id']] = $v['csr_winning_user_id'];
                }

                if (isset($v['csr_winning_user_id']) && $v['csr_winning_user_id'] == $uid) {
                    if (isset($v['csr_winning_order_id']) && trim($v['csr_winning_order_id'])) {
                        $order_ids[$v['csr_winning_order_id']] = $v['csr_winning_order_id'];
                    }
                }

                if (isset($v['csr_winning_user_id']) && $v['csr_winning_user_id'] == $uid) {
                    if (isset($v['csr_status']) && intval($v['csr_status']) > 1) {
                        if (isset($v['csr_winning_order_id']) && trim($v['csr_winning_order_id'])) {
                            $comment_ids[$v['csr_winning_order_id']] = $v['csr_winning_order_id'];
                        }
                    }
                }
            }
            unset($v);
            $team_info_res = array();
            if ($team_ids) {
                $team_info_res = M('team')->where(array('id' => array('in', array_keys($team_ids))))->field('id,product,title,image,max_number')->index('id')->select();
            }
            $user_info_res = array();
            if ($user_ids) {
                $user_info_res = M('user')->where(array('id' => array('in', array_keys($user_ids))))->field('id,username')->index('id')->select();
            }
            $order_info_res = array();
            if ($order_ids) {
                $order_info_res = $order->where(array('id' => array('in', array_keys($order_ids))))->field('id,address,mail_order_pay_state')->index('id')->select();
            }
            $comment_info_res = array();
            if ($comment_ids) {
                $comment_where = array(
                    'is_comment' => 'Y',
                    'order_id' => array('in', array_keys($comment_ids)),
                    'user_id' => $uid,
                );
                $comment_info_res = M('comment')->where($comment_where)->group('order_id')->getField('order_id,count(id) as comment_count');
            }

            foreach ($order_list as &$v) {
                // 中奖用户名
                $v['csr_winning_user_username'] = ternary($user_info_res[$v['csr_winning_user_id']]['username'], '');
                $v['csr_winning_user_username_hide'] = '';
                if (checkMobile($v['csr_winning_user_username'])) {
                    $v['csr_winning_user_username_hide'] = substr($v['csr_winning_user_username'], 0, 4) . '****' . substr($v['csr_winning_user_username'], -4, 4);
                } else {
                    $v['csr_winning_user_username_hide'] = cutStr($v['csr_winning_user_username'], 1, 0, 0) . '**';
                }

                // 团单名称和图片
                $v['team_product'] = ternary($team_info_res[$v['order_team_id']]['title'], '');
                $v['team_max_number'] = ternary($team_info_res[$v['order_team_id']]['max_number'], '');
                $v['team_image'] = ternary($team_info_res[$v['order_team_id']]['image'], '');
                if ($v['team_image']) {
                    $v['team_image'] = getImagePath($v['team_image']);
                }

                // 修改状态
                $v['csr_status_name'] = '进行中';
                if (!isset($v['csr_status']) || !$v['csr_status']) {
                    $v['csr_status'] = 0;
                }
                if (isset($v['csr_status']) && $v['csr_status'] == 1) {
                    $v['csr_status_name'] = '已揭晓';
                }
                $v['csr_my_status'] = $v['csr_status'];
                if (isset($v['csr_status']) && intval($v['csr_status']) > 0) {
                    // 是自己中的奖
                    if (isset($v['csr_winning_user_id']) && $v['csr_winning_user_id'] == $uid) {
                        // 中奖但未领奖
                        if ($v['csr_status'] == 1) {
                            $v['csr_my_status'] = 3;
                        }
                        $_order_res = ternary($order_info_res[$v['csr_winning_order_id']], array());
                        if ($v['csr_status'] == 2 && isset($_order_res['mail_order_pay_state'])) {
                            $address = @json_decode($_order_res['address'], true);
                            $v['order_address'] = array();
                            if ($address) {
                                $v['order_address'] = $address;
                            }
                            if (intval($_order_res['mail_order_pay_state']) == 0) {
                                $v['csr_status_name'] = '待发货';
                                $v['csr_my_status'] = 4;
                            }
                            if (intval($_order_res['mail_order_pay_state']) == 1) {
                                $v['csr_status_name'] = '待签收';
                                $v['csr_my_status'] = 5;
                            }
                            if (intval($_order_res['mail_order_pay_state']) == 2) {
                                $v['csr_status_name'] = '待晒单';
                                $v['csr_my_status'] = 6;
                            }
                            if (isset($comment_info_res[$v['csr_winning_order_id']]) && trim($comment_info_res[$v['csr_winning_order_id']])) {
                                $v['csr_status_name'] = '完成';
                                $v['csr_my_status'] = 7;
                            }
                        }
                    }
                }
            }
            unset($v);
        }
        
        return $order_list;
    }
    
    
    /**
     *  获取结算到的时间
     * @param type $city_id
     * @return type
     */
    public function get_withdrawals_time($city_id = 0) {

        if (!$city_id) {
            return time();
        }

        $apply_max_time = @strtotime(C('WITHDRAWALS_BEGIN_TIME'));
        $apply_max_time_res = M('agent_pay')->where(array('city_id' => $city_id))->max('apply_time');
        if ($apply_max_time_res && $apply_max_time_res >= $apply_max_time) {
            $apply_max_time = @strtotime('+1 month ' . date('Y-m-d H:i:s', $apply_max_time_res));
            if($apply_max_time>time()){
                $this->ajaxReturn(array('code' => -1, 'error' => '不能体现当月利润！'));
            }
        }

        return $apply_max_time;
    }

    /**
     * 本月交易利润
     * @param type $city_id
     * @param type $apply_begin_time
     * @param type $apply_end_time
     * @return type
     */
    public function get_month_all_profit($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0) {
        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }

        $where = array(
            'city_id' => $city_id,
            'rstate' => 'normal',
            'state' => 'pay',
            'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );

        // 订单数（已减去退款） 交易金额（已减去退款）
        $field = array(
            'count(id)' => 'order_count',
            'sum(origin)' => 'order_sum_money',
        );
        $all_partner_res = M('order')->where($where)->field($field)->find();

        // 退款
        $where['rstate'] = 'berefund';
        $where['state'] = 'unpay';
        $field = array(
            'count(id)' => 'order_refund_count',
            'sum(origin)' => 'order_refund_sum_money',
        );
        $refund_all_partner_res = M('order')->where($where)->field($field)->find();

        // 接待量
        $where = array(
            'partner_income.create_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
            'team.city_id' => $city_id,
        );
        /*$field = array(
            'count(partner_income.id)' => 'reception_count',
            'sum(team.team_price-team.ucaii_price)' => 'profit_money',
        );
        $reception_profit_res = M('partner_income')->where($where)->field($field)
                        ->join('inner join team on team.id=partner_income.team_id')->find();
        */
        //
        $field1 = array(
            'count(partner_income.id)' => 'reception_count',
            'sum(coupon.team_price-coupon.ucaii_price)' => 'profit_money',
        );
        $reception_profit_res = M('partner_income')->where($where)->field($field1)
            ->join(array('left join team on team.id=partner_income.team_id', 'left join coupon on coupon.id=partner_income.coupon_id'))->find();
            //->join('left join team on team.id=partner_income.team_id', 'left join coupon on coupon.id=partner_income.coupon_id')->find();
        //SELECT count(partner_income.id) AS `reception_count`,sum(coupon.team_price-coupon.ucaii_price) AS `profit_money` FROM `partner_income` left join team on team.id=partner_income.team_id left join coupon on coupon.id=partner_income.coupon_id WHERE team.city_id = '308' AND ( partner_income.create_time >= 1456761600 AND partner_income.create_time < 1459440000 ) LIMIT 1
        return array_merge($all_partner_res, $refund_all_partner_res, $reception_profit_res);
    }
    //2016.5.16增加异步返回
    /**
     * @param int $city_id
     * @param int $apply_begin_time
     * @param int $apply_end_time
     * @return mixed
     * 返回 订单数（已减去退款） 交易金额（已减去退款）
     */
    public function all_partner_res($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0){
        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }

        $where = array(
            'city_id' => $city_id,
            'rstate' => 'normal',
            'state' => 'pay',
            'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );

        // 订单数（已减去退款） 交易金额（已减去退款）
        $field = array(
            'count(id)' => 'order_count',
            'sum(origin)' => 'order_sum_money',
        );
        $all_partner_res = M('order')->where($where)->field($field)->find();
        return $all_partner_res;
    }

    /***
     * @param int $city_id
     * @param int $apply_begin_time
     * @param int $apply_end_time
     * @return mixed
     *退款数量
     */
    public function refund_all_partner_res($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0){
        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }

        $where = array(
            'city_id' => $city_id,
            'rstate' => 'normal',
            'state' => 'pay',
            'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );
        $where['rstate'] = 'berefund';
        $where['state'] = 'unpay';
        $field = array(
            'count(id)' => 'order_refund_count',
            'sum(origin)' => 'order_refund_sum_money',
        );
        $refund_all_partner_res = M('order')->where($where)->field($field)->find();
        return $refund_all_partner_res;
    }

    /***
     * @param int $city_id
     * @param int $apply_begin_time
     * @param int $apply_end_time
     * @return mixed
     * 计算本月费用
     */
    public function reception_profit_res($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0){
        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }
        // 接待量
        /*$where = array(
            'partner_income.create_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
            'team.city_id' => $city_id,
        );

        $field1 = array(
            'count(partner_income.id)' => 'reception_count',
            'sum(coupon.team_price-coupon.ucaii_price)' => 'profit_money',
        );
        $reception_profit_res = M('partner_income')->where($where)->field($field1)
            ->join(array('left join team on team.id=partner_income.team_id', 'left join coupon on coupon.id=partner_income.coupon_id'))->find();
        //SELECT count(partner_income.id) AS `reception_count`,sum(coupon.team_price-coupon.ucaii_price) AS `profit_money` FROM `partner_income` left join team on team.id=partner_income.team_id left join coupon on coupon.id=partner_income.coupon_id WHERE team.city_id = '308' AND ( partner_income.create_time >= 1456761600 AND partner_income.create_time < 1459440000 ) LIMIT 1
        //2016.5.25新加
        */
        $where = array(
            'coupon.consume_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
            'team.city_id' => $city_id,
        );

        $field1 = array(
            'count(coupon.id)' => 'reception_count',
            // 'sum(coupon.team_price-coupon.ucaii_price)' => 'profit_money',
            'sum(CASE coupon.team_price WHEN 0 THEN (team.team_price-team.ucaii_price) ELSE (coupon.team_price-coupon.ucaii_price) END)' => 'profit_money',
        );
        $reception_profit_res = M('coupon')->where($where)->field($field1)
            ->join(array('left join team on team.id=coupon.team_id'))->find();
        //SELECT count(partner_income.id) AS `reception_count`,sum(coupon.team_price-coupon.ucaii_price) AS `profit_money` FROM `partner_income` left join team on team.id=partner_income.team_id left join coupon on coupon.id=partner_income.coupon_id WHERE team.city_id = '308' AND ( partner_income.create_time >= 1456761600 AND partner_income.create_time < 1459440000 ) LIMIT 1
        //2016.5.25新加
        file_put_contents('/tmp/coupon.log',var_export(M('coupon')->getLastSql(), true).'||',FILE_APPEND);
        return $reception_profit_res;
    }
    //2016.5.16异步分会结算结束
  
    /**
     *  支付费用统计
     * @param type $city_id
     * @param type $apply_begin_time
     * @param type $apply_end_time
     */
    public function get_payment_fees($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0) {
        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }

        $where = array(
            'city_id' => $city_id,
            //'_string' => "rstate='berefund' or state='pay'",
            '_string'=>"((state='pay') OR (state='unpay' && rstate='berefund'))",
            'service' => array('not in', array('credit', 'cash')),
            'pay_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );

        $order_payment_money = M('order')->where($where)->group('service')->field('service,sum(origin) as sum_money')->select();
        $all_rate_money = 0;
        $service_rate = array(
            'pcalipay' => array(
                'name' => 'pc支付宝',
                'rate' => '0.007', // 0.7%
            ),
            'appalipay' => array(
                'name' => '手机支付宝',
                'rate' => '0.008', // 0.8%
            ),
            'tenpay' => array(
                'name' => '财付通',
                'rate' => '0.006', // 0.6%
            ),
            'wxpay' => array(
                'name' => '微信',
                'rate' => '0.006', // 0.6%
            ),
            'wechatpay' => array(
                'name' => '微信公众号',
                'rate' => '0.006', // 0.6%
            ),
            'umspay' => array(
                'name' => '全民付',
                'rate' => '0.008', // 0.8%
            ),
            'unionpay' => array(
                'name' => '银联',
                'rate' => '0.008', // 0.8%
            ),
            'lianlianpay' => array(
                'name' => '连连',
                'rate' => '0.006', // 0.6%
            ),
        );
        $service_money = array_combine(array_keys($service_rate), array(0, 0, 0, 0, 0, 0, 0, 0));
        if ($order_payment_money) {
            foreach ($order_payment_money as $v) {
                $service = strtolower(trim($v['service']));
                $money = sprintf("%.2f", $v['sum_money']);
                $_pay_type = '';
                switch ($service) {
                    // 支付宝
                    case 'pcalipay':
                        $_pay_type = 'pcalipay';
                        break;
                    case 'aliwap':
                    case 'wapalipay':
                    case 'aliapp':
                    case 'alipay':
                        $_pay_type = 'appalipay';
                        break;
                    // 财付通
                    case 'tenpay':
                    case 'tenwap':
                    case 'tenapp':
                    case 'pctenpay':
                    case 'waptenpay':
                        $_pay_type = 'tenpay';
                        break;
                    // 微信支付
                    case 'wechatpay':
                    case 'wxpay':
                        $_pay_type = 'wxpay';
                        break;
                    case 'wapwechatpay':
                    case 'pcwxpaycode':
                        $_pay_type = 'wechatpay';
                        break;

                    // 全民付
                    case 'chinabank':
                    case 'umspay':
                    case 'wapumspay':
                        $_pay_type = 'umspay';
                        break;

                    // 银联
                    case 'unionpay':
                    case 'wapunionpay':
                        $_pay_type = 'unionpay';
                        break;

                    // 连连
                    case 'lianlianpay':
                        $_pay_type = 'lianlianpay';
                        break;

                    // 其他忽略
                    default:
                        break;
                }
                if (trim($_pay_type)) {
                    $service_money[$_pay_type] = $service_money[$_pay_type] + $money;
                }
            }
            $_list = array();
            foreach ($service_rate as $k => $v) {
                $_list[$k] = array(
                    'type' => $k,
                    'name' => ternary($v['name'], ''),
                    'rate' => sprintf("%.2f", (ternary($v['rate'], '0') * 100)) . "%",
                    'money' => ternary($service_money[$k], '0.00'),
                    'rate_money' => sprintf("%.2f", ternary($service_money[$k] * $v['rate'], '0.00')),
                );
                $all_rate_money = sprintf("%.2f", $all_rate_money + $_list[$k]['rate_money']);
            }

            $order_payment_money = $_list;
        }
        $data = array(
            'all_rate_money' => $all_rate_money,
            'list' => $order_payment_money,
        );
        return $data;
    }

    /**
     *  获取短信费用
     * @param type $city_id
     * @param type $apply_begin_time
     * @param type $apply_end_time
     */
    public function get_SMS_charges($city_id = 0, $apply_begin_time = 0, $apply_end_time = 0) {
        if (!$city_id || !$apply_begin_time || !$apply_end_time) {
            return array();
        }
        $apply_end_time=$apply_end_time-1;
        $where = array(
            'city_id' => $city_id,
            //'_string' => "rstate='berefund' or state='pay'",
            //'_string'=>"((state='pay') OR (state='unpay' && rstate='berefund'))",
            //'service' => array('not in', array('credit,crash')),
            //'laiyuan' => array('in', array('wap,pc')),
            '_string' => "laiyuan='wap' or laiyuan='pc'",
            'create_time' => array(array('egt', $apply_begin_time), array('lt', $apply_end_time)),
        );

        $order_count = M('order')->where($where)->field('sum(quantity) as num')->select();
        if($order_count){
            $order_count=$order_count[0]['num'];
        }else{
            $order_count=0;
        }
        $data = array(
            'sms_count' => $order_count * 2,
            'rate' => '0.05元/条',
            'sms_money' => sprintf("%.2f", ($order_count * 2 * 0.05))
        );
        return $data;
    }
}
