<?php

/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-18
 * Time: 09:26
 */

namespace Common\Model;

class CardModel extends CommonModel {

    /**
     * 是否可生成代金券
     * @param $order
     * @param null $type
     * @return array|bool
     */
    public function isCreateCard($order, $type = null) {
        $config = C('VOUCHERS');
        if ($config['createState'] === false) {
            $error = array(
                'error' => '活动已结束',
                'code' => -1
            );
        } else {
            if (is_numeric($order)) {
                $order = D('Order')->info($order);
            }

            if ($order['state'] == 'unpay') {
                if ($order['rstate'] == 'normal') {
                    return array(
                        'error' => '请付款后换取代金券',
                        'code' => -1
                    );
                } else if ($order['rstate'] == 'berefund') {
                    return array(
                        'error' => '该订单已退款，无法生成代金券',
                        'code' => -1
                    );
                }
            }

            if (empty($type)) {
                $type = M('Team')->where('id=' . $order['team_id'])->getField('team_type');
            }
            if (!empty($config['createCitys']) && !in_array($order['city_id'], $config['createCitys'])) {
                $error = array(
                    'error' => '此城市不支持代金券',
                    'code' => -1
                );
            } else {
                if (!empty($config['createTeamType']) && !in_array($type, $config['createTeamType'])) {
                    $error = array(
                        'error' => '此团单不支持代金券',
                        'code' => -1
                    );
                } else {
                    $vPrice = 0;
                    if (isset($config['createRules']) && $config['createRules']) {
                        $now_time = time();
                        $price = ternary($order['origin'], 0);
                        foreach ($config['createRules'] as $v) {
                            if ($price >= $v['min'] && $price < $v['max']) {
                                if (isset($v['begin_time']) && trim($v['begin_time']) && isset($v['end_time']) && trim($v['end_time'])) {
                                    $begin_time = strtotime($v['begin_time']);
                                    $end_time = strtotime($v['end_time']);
                                    if ($begin_time && $end_time && $now_time > $begin_time && $now_time < $end_time) {
                                       $vPrice = $v['money'];
                                       break;
                                    }
                                    continue;
                                }


                                $vPrice = $v['money'];
                                break;
                            }
                        }
                    }
                    if($vPrice > 0){
                        return true;
                    }
                    return false;
                }
            }
        }
        return $error;
    }

    /**
     * 生成代金券
     * @param $user_id
     * @param $order
     * @param string $type
     * @return array
     */
    public function createCard($user_id, $order, $type = 'get') {
        if ($type == 'get') {
            $map = array(
                'order_id' => $order['id'],
                'type' => 'get'
            );
            if ($this->getTotal($map) > 0) {
                return array(
                    'error' => '该订单已生成代金券',
                    'code' => -1
                );
            }
        }

        $now_time = time();
        $code = $now_time . $user_id;
        $end_time = $now_time + 7 * 86400;
        $credit = $min = 0;
        $config = C('VOUCHERS');

        foreach ($config['createRules'] as $val) {
            if ($val['min'] <= $order['origin'] && $val['max'] > $order['origin']) {

                if (isset($val['begin_time']) && trim($val['begin_time']) && isset($val['end_time']) && trim($val['end_time'])) {
                    $begin_time = strtotime($val['begin_time']);
                    $end_time_ = strtotime($val['end_time']);
                    if ($begin_time && $end_time_ && $now_time > $begin_time && $now_time < $end_time_) {
                        $min = $val['min'];
                        $credit = $val['money'];
                        if(isset($val['expire_time']) && trim($val['expire_time'])){
                            $expire_time = strtotime($val['expire_time']);
                            if($expire_time && $expire_time>$end_time_){
                                $end_time = $expire_time;
                            }
                        }
                        break;
                    }
                    continue;
                }

                $min = $val['min'];
                $credit = $val['money'];
                break;
            }
        }
        if ($credit == 0) {
            return array(
                'error' => '对不起，您的订单不符合规则',
                'code' => -1
            );
        }
        $data = array(
            'code' => $code,
            'user_id' => $user_id,
            'team_id' => $order['team_id'],
            'city_id' => $order['city_id'],
            'order_id' => $order['id'],
            'begin_time' => $now_time,
            'end_time' => $end_time,
            'consume' => 'N',
            'credit' => $credit,
            'mobile' => $order['mobile'] ? $order['mobile'] : '',
            'type' => $type
        );
        $id = $this->add($data);
        if ($id) {
            $data['id'] = $id;
            return $data;
        } else {
            return array(
                'error' => '代金券获取失败',
                'code' => -1
            );
        }
    }

    /**
     * 判断是否可用抵金券
     * @param type $city_id
     * @param type $team_type
     * @return boolean
     */
    public function isUseCard($city_id, $team_type) {

        if (!trim($city_id) || !trim($team_type)) {
            return false;
        }

        $vouchers = C('VOUCHERS');
        if (!isset($vouchers['useState']) || !$vouchers['useState']) {
            return false;
        }
        if (!isset($vouchers['useCitys']) || !isset($vouchers['useTeamType'])) {
            return false;
        }
        if (in_array($city_id, $vouchers['useCitys']) && in_array($team_type, $vouchers['useTeamType'])) {
            return true;
        }
        return false;
    }

    /**
     * 获取抵金券
     * @param type $id
     * @param type $money
     * @return boolean
     */
    public function getCardList($id, $money = '') {

        if (!trim($id)) {
            return false;
        }

        $where = array(
            'user_id|mobile' => $id,
            'end_time' => array('GT', time()),
            'consume' => 'N');
        if (trim($money)) {
            $where['credit'] = array('ELT', $money);
        }
        $field = 'id,code,credit,begin_time,end_time,consume';
        $res = $this->where($where)->field($field)->select();
        if (!$res) {
            return false;
        }

        $vouchers = C('VOUCHERS');
        // 获取抵金券使用最小金额
        foreach ($res as &$v) {
            $min = 0;

            if (isset($vouchers['useRules'])) {
                foreach ($vouchers['useRules'] as $_v) {
                    if (isset($_v['money']) && isset($v['credit']) && $v['credit'] <= $_v['money']) {
                        $min = strval(ternary($_v['min'], 0));
                        break;
                    }
                }
            }
            $v['min'] = $min;
        }
        return $res;
    }

    /**
     * 根据订单金额 获取抵金券金额的范围
     * @param type $money
     */
    public function getMoneyUseRules($money) {

        if (!trim($money)) {
            return false;
        }
        $vouchers = C('VOUCHERS');
        if (!isset($vouchers['useState']) || !$vouchers['useState'] || !isset($vouchers['useRules']) || !is_array($vouchers['useRules'])) {
            return false;
        }
        $_money = '';
        foreach ($vouchers['useRules'] as $v) {
            if (!isset($v['min']) || !isset($v['max']) || !isset($v['money'])) {
                continue;
            }
            if ($money > $v['min'] && $money <= $v['max']) {
                $_money = $v['money'];
                break;
            }
        }
        return $_money;
    }

    public function getList($where, $order, $limit, $field = '*') {
        if ($order == '') {
            $order = 'id desc';
        }
        $data = $this->alias('c')->join('left join team t ON c.team_id = t.id')->field($field)->where($where)->order($order)->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

}
