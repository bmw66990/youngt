<?php

/**
 * Flow 流水表模型
 * Created by JetBrains PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 15-3-16
 * Time: 下午15:49
 * To change this template use File | Settings | File Templates.
 */

namespace Common\Model;

use Common\Model\CommonModel;

class FlowModel extends CommonModel {
    /*
     *  获取用户交易流水
     *  @param array $where  : 获取数据信息的条件
     *  @param string $field : 获取数据信息字段名
     *
     *  return array $list   : 返回数据信息
     */

    public function getUserFlow($where, $limit, $orderby = 'id DESC', $field = '*') {
        $list = $this->field($field)->where($where)->limit($limit)->order($orderby)->select();
        if ($list === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        if ($list) {
            $team_ids = array();
            foreach ($list as $val) {
                if ($val['detail_id'] && intval($val['detail_id']) > 0) {
                    $team_ids[$val['detail_id']] = $val['detail_id'];
                } elseif ($val['team_id'] && intval($val['team_id']) > 0) {
                    $team_ids[$val['team_id']] = $val['team_id'];
                }
            }
            $team = array();
            if ($team_ids) {
                $team = M('team')->field('id,product')->index('id')->where(array('id' => array('in', array_keys($team_ids))))->select();
            }
            foreach ($list as &$row) {
                if ($row['detail_id'] && is_numeric($row['detail_id'])) {
                    $row['product'] = $team[$row['detail_id']]['product'];
                } else {
                    if ($row['team_id'] && is_numeric($row['team_id'])) {
                        $row['product'] = $team[$row['team_id']]['product'];
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 充值统计
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function rechargeCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);

        if (!empty($city_id)) {
            $where['u.city_id'] = $city_id;
        }
        $where['f.create_time'][] = array('egt', $stime);
        $where['f.create_time'][] = array('lt', $etime);
        $where['f.`action`'] = array('IN', array('recharge', 'store'));
        $data = $this->table('flow f')->field('LEFT(FROM_UNIXTIME(f.create_time), ' . $len . ') ct,f.`action`,sum(f.money) num')->join('LEFT JOIN user u ON u.id=f.user_id')->where($where)->group('ct,f.`action`')->order('null')->select();
        //echo $this->_sql();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach ($data as $row) {
                $list[$row['action']][$row['ct']] = $row['num'];
            }
            return $list;
        }
    }

    public function getCount($where) {
        $condition = 'f.user_id=u.id';
        $count = $this->table('flow f,user u')->where($where)->where($condition)->count();
        if ($count === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $count;
    }

    /**
     * 流水并获取操作员
     * @param $city_id
     * @param $type store/withdraw
     * @param $limit
     * @return array|bool
     */
    public function getFlowWithAdmin($city_id, $type, $limit) {
        $where['f.action'] = $type;
        if (!empty($city_id)) {
            $where['u.city_id'] = $city_id;
        }
        $condition = 'f.user_id=u.id';
        $data = $this->table('flow f,user u')->field('f.*,u.email,u.username')->where($condition)->where($where)->order('f.id DESC')->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        $admin = array();
        foreach ($data as $row) {
            $admin[] = $row['admin_id'];
        }
        $map['id'] = array('in', array_unique($admin));
        $adminList = M('User')->where($map)->getField('id,email,username', true);
        if ($adminList === false) {
            $this->errorInfo['info'] = $this->getError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return array('data' => $data, 'admin' => $adminList);
    }

    /**
     * 流水并获取支付类型
     * @param $city_id
     * @param $type  charge/paycharge/cardstore
     * @param $limit
     * @return array|bool
     */
    public function getFlowWithPay($city_id, $type, $limit) {
        $where['f.action'] = $type;
        if (!empty($city_id)) {
            $where['u.city_id'] = $city_id;
        }
        $condition = 'f.user_id=u.id';
        $data = $this->table('flow f,user u')->field('f.*,u.email,u.username')->where($condition)->where($where)->order('f.id DESC')->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getError();
            $this->errorInfo['sql'] = $this->_sql();
        }

        //获取支付类型信息
        $service = array();
        foreach ($data as $row) {
            $service[] = $row['detail_id'];
        }
        $serviceList = M('Pay')->where(array('id' => array('in', $service)))->getField('id,service', true);
        if ($serviceList === false) {
            $this->errorInfo['info'] = $this->getError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return array('data' => $data, 'service' => $serviceList);
    }

    /**
     * 流水并获取团单信息
     * @param $city_id
     * @param $type refund
     * @param $limit
     * @return array
     */
    public function getFlowWithTeam($city_id, $type, $limit) {
        $where['f.action'] = $type;
        if (!empty($city_id)) {
            $where['u.city_id'] = $city_id;
        }
        $condition = 'f.user_id=u.id';
        $data = $this->table('flow f,user u')->field('f.*,u.email,u.username')->where($condition)->where($where)->order('f.id DESC')->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        //获取团单信息
        $team = array();
        foreach ($data as $row) {
            $team[] = $row['detail_id'];
        }
        $teamList = M('Team')->where(array('id' => array('in', $team)))->getField('id,service', true);
        if ($teamList === false) {
            $this->errorInfo['info'] = $this->getError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return array('data' => $data, 'team' => $teamList);
    }

    /**
     * 获取用户成长值
     * @param $uid
     * @return float
     */
    public function getUserGrowth($uid) {
        $map['user_id'] = $uid;
        $map['action'] = 'buy';
        $buy = $this->where($map)->sum('money');
        if ($buy === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        $map['action'] = 'refund';
        $refund = $this->where($map)->sum('money');
        if ($buy === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }

        $growth = ceil($buy - $refund);
        return $growth;
    }

    /**
     * 获取用户成长记录
     * @param $where
     * @param $sort
     * @param $limit
     * @return bool
     */
    public function getUserGrowthList($where, $sort, $limit) {
        $data = $this->field('id,action,detail_id,team_id,create_time,money')->where($where)->order($sort)->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        $teamId = '';
        foreach ($data as $row) {
            if (is_numeric($row['team_id'])) {
                $teamId .= $row['team_id'] . ',';
            } else {
                $teamId .= $row['detail_id'] . ',';
            }
        }

        if (!empty($teamId)) {
            $map['id'] = array('in', substr($teamId, 0, -1));
            $team = M('Team')->where($map)->getField('id,product', TRUE);
            if ($data === FALSE) {
                $this->errorInfo['info'] = $this->getDbError();
                $this->errorInfo['sql'] = $this->_sql();
                return FALSE;
            }
        }
        //dump($team);
        foreach ($data as $key => $row) {
            if (is_numeric($row['team_id'])) {
                $row['title'] = ternary($team[$row['team_id']], '');
            } else {
                $row['title'] = ternary($team[$row['detail_id']], '');
            }
            if ($row['action'] == 'buy') {
                $data[$key]['title'] = '购买 - ' . $row['title'];
                $data[$key]['money'] = '+' . ceil($row['money']);
            } else if ($row['action'] == 'refund') {
                $data[$key]['title'] = '退款 - ' . $row['title'];
                $data[$key]['money'] = '-' . ceil($row['money']);
            }
            unset($data[$key]['action'], $data[$key]['detail_id']);
        }
        return $data;
    }

}
