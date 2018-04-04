<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/7/22
 * Time: 16:36
 */

namespace Common\Model;

class PointsOrderModel extends CommonModel {

    public function getList($where, $order, $limit, $field = "*") {
        if ($order == '') {
            $order = 'po.id desc';
        }
        $data = $this->alias('po')->join('left join points_team pt ON pt.id = po.team_id')->where($where)->field($field)->order($order)->limit($limit)->select();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

    /**
     * 获取兑换码
     * @return type
     */
    public function getPointsOrderCode() {

        $n = 50;
        while (true) {
            $n_id = mt_rand(0, 9999999999);
            $code = sprintf("%010d", $n_id);
            $is_order = $this->getTotal(array('code' => $code));
            if (!$is_order || $is_order <= 0) {
                return $code;
            }
            $n--;
            if ($n <= 0) {
                break;
            }
        }

        $code = time();
        return substr($code, -10);
    }

    /**
     * @param $score
     * @param $action
     * @param $user_score
     * @param int $good_id
     *
     * @return mixed
     */
    public function _addScoreFlow($score, $action, $user_score, $uid, $good_id = 0) {
        $data = array(
            'create_time' => time(),
            'detail_id' => $good_id,
            'user_id' => $uid,
            'score' => $score,
            'action' => $action,
            'sumscore' => $user_score + $score
        );
        return M('credit')->add($data);
    }

    /**
     * 商品兑换
     * @param type $id
     * @param type $num
     * @param type $uid
     * @param type $plat
     * @return type
     */
    public function pointsTeamExchange($id, $num, $uid, $plat) {

        if (!$id) {
            return array('error' => '兑换商品id不能为空！');
        }
        if (!$num) {
            return array('error' => '兑换商品数量不能为空！');
        }
        if (!$uid) {
            return array('error' => '用户未登录，不能兑换！');
        }
        $plat = trim(strtolower($plat));
        if (!$plat) {
            $plat = '未知平台:$plat';
        }
        $goods_info = M('points_team')->find($id);
        $user = M('user')->where(array('id' => $uid))->find();
        if (($goods_info['score'] * $num) > $user['score']) {
            return array('error' => '您的积分不足' . $goods_info['score'] . '分，无法兑换');
        }
        if ($goods_info['limit_num'] > 0 && intval($goods_info['consume_num']+$num) > intval($goods_info['limit_num'])) {
            return array('error' => '商品数量不足，请关注其他产品');
        }
        $count = M('points_order')->where(array('user_id'=>$uid,'team_id'=>$id))->count();
        if ($goods_info['convert_num'] > 0 && intval($count + $num) > intval($goods_info['convert_num'])) {
            return array('error' => '该商品每人限兑' . $goods_info['convert_num'] . '份');
        }
        $model = M();
        $model->startTrans();

        $addData = array(
            'team_id' => $goods_info['id'],
            'user_id' => $uid,
            'city_id' => $goods_info['city_id'],
            'partner_id' => $goods_info['partner_id'],
            'num' => $num,
            'score' => $goods_info['score'],
            'total_score' => $goods_info['score'] * $num,
            'code' => $this->getPointsOrderCode(),
            'consume' => 'N',
            'exchange_plat' => $plat,
            'add_time' => time(),
            'expire_time' => $goods_info['expire_time'],
        );
        $score_order_id = $this->add($addData);
        if (!$score_order_id) {
            $model->rollback();
            return array('error' => '兑换失败，稍后重试！');
        }

        $upData = array(
            'consume_num' => $goods_info['consume_num'] + $num
        );
        $res = M('points_team')->where(array('id' => $goods_info['id']))->save($upData);
        if (!$res) {
            $model->rollback();
            return array('error' => '兑换失败，稍后重试！');
        }
        $res = $this->_addScoreFlow('-' . $addData['total_score'], 'score_goods', $user['score'], $uid, $id);
        if (!$res) {
            $model->rollback();
            return array('error' => '兑换失败，稍后重试！');
        }
        $res = M('user')->where(array('id' => $uid))->save(array('score' => $user['score'] - $addData['total_score']));
        if (!$res) {
            $model->rollback();
            return array('error' => '兑换失败，稍后重试！');
        }
        $model->commit();
        $data = array(
            'points_team_id' => $id,
            'points_team_name' => $goods_info['name'],
            'points_order_id' => $score_order_id,
            'points_order_code' => $addData['code'],
            'score' => $addData['total_score'],
            'expire_time' => $addData['expire_time'],
        );

        return $data;
    }

}
