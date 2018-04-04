<?php

/**
 * Created by PhpStorm.
 * User: daipingshan  <4914906399@qq.com>
 * Date: 2015-03-26
 * Time: 17:46
 */

namespace Common\Model;

use Common\Model\CommonModel;

class AddressModel extends CommonModel {

    /**
     * 添加收货地址
     */
    public function addUserAddress($uid, $address = array()) {

        // 检测用户是否登录
        if (!trim($uid)) {
            return array('error' => '用户未登录，不能添加地址!');
        }

        if (!isset($address['province']) || !trim($address['province'])) {
            return array('error' => '请选择所在省!');
        }
        if (!isset($address['area']) || !trim($address['area'])) {
            return array('error' => '请选择所在市!');
        }
        if (!isset($address['city']) || !trim($address['city'])) {
            $address['city']=$address['area'];
           // return array('error' => '请选择所在城市!');
        }
        if (!isset($address['street']) || !trim($address['street'])) {
            return array('error' => '请填写详细地址!');
        }
        if (!isset($address['name']) || !trim($address['name'])) {
            return array('error' => '请填写收货人姓名!');
        }
        if (!isset($address['mobile']) || !trim($address['mobile'])) {
            return array('error' => '请填写联系电话!');
        }
        if (!checkMobile($address['mobile'])) {
            return array('error' => '联系电话格式错误!');
        }

        // 地址添加不能超过5个
        $address_count = M('address')->where(array('user_id' => $uid))->count();
        if ($address_count && $address_count >= 5) {
            return array('error' => '每人最多添加五个地址!');
        }
      
        $model = M();
        $model->startTrans();
        $data = array(
            
            'province' => htmlspecialchars($address['province']),
            'area' => htmlspecialchars($address['area']),
            'city' => htmlspecialchars($address['city']),
            'street' => htmlspecialchars($address['street']),
            'zipcode' => htmlspecialchars($address['zipcode']),
            'name' => htmlspecialchars($address['name']),
            'mobile' => htmlspecialchars($address['mobile']),
            'user_id' => $uid,
            'default' => 'N',
            'create_time' => time(),
        );
        $address_id = M('address')->add($data);
        if (!$address_id) {
            $model->rollback();
            return array('error' => '地址添加失败!');
        }
        if (isset($address['default_type']) && $address['default_type'] == 'Y') {
            $res = M('address')->where(array('user_id' => $uid))->save(array('default' => 'N'));
            if ($res === false) {
                $model->rollback();
                return array('error' => '设置为默认地址失败!');
            }
            $res = M('address')->where(array('id' => $address_id))->save(array('default' => 'Y'));
            if ($res === false) {
                $model->rollback();
                return array('error' => '设置为默认地址失败!');
            }
        }
        $model->commit();
        return $address_id;
    }

    /**
     * 编辑收货地址
     */
    public function editUserAddress($uid, $address = array()) {

        // 检测用户是否登录
        if (!trim($uid)) {
            return array('error' => '用户未登录，不能添加地址!');
        }

        if (!isset($address['address_id']) || !trim($address['address_id'])) {
            return array('error' => '修改的地址id不能为空!');
        }
        if (!isset($address['province']) || !trim($address['province'])) {
            return array('error' => '请选择所在省!');
        }
        if (!isset($address['area']) || !trim($address['area'])) {
            return array('error' => '请选择所在市!');
        }
        if (!isset($address['city']) || !trim($address['city'])) {
             $address['city']=$address['area'];
            //return array('error' => '请选择所在城市!');
        }
        if (!isset($address['street']) || !trim($address['street'])) {
            return array('error' => '请填写详细地址!');
        }
        if (!isset($address['name']) || !trim($address['name'])) {
            return array('error' => '请填写收货人姓名!');
        }
        if (!isset($address['mobile']) || !trim($address['mobile'])) {
            return array('error' => '请填写联系电话!');
        }
        if (!checkMobile($address['mobile'])) {
            return array('error' => '联系电话格式错误!');
        }

        $addres_res = M('address')->where(array('id' => $address['address_id']))->field('user_id')->find();
        if (!$addres_res) {
            return array('error' => '需要修改的地址不存在!');
        }
        if (!isset($addres_res['user_id']) || intval($addres_res['user_id']) !== intval($uid)) {
            return array('error' => '修改的地址不是自己的地址!');
        }

        $model = M();
        $model->startTrans();

        $data = array(
            'province' => htmlspecialchars($address['province']),
            'area' => htmlspecialchars($address['area']),
            'city' => htmlspecialchars($address['city']),
            'street' => htmlspecialchars($address['street']),
            'zipcode' => htmlspecialchars($address['zipcode']),
            'name' => htmlspecialchars($address['name']),
            'mobile' => htmlspecialchars($address['mobile']),
            'user_id' => $uid,
            'default' => 'N',
            'create_time' => time(),
        );
        $res = M('address')->where(array('id' => $address['address_id']))->save($data);
        if ($res === false) {
            $model->rollback();
            return array('error' => '修改地址失败!');
        }
        if (isset($address['default_type']) && $address['default_type'] == 'Y') {
            $res = M('address')->where(array('user_id' => $uid))->save(array('default' => 'N'));
            if ($res === false) {
                $model->rollback();
                return array('error' => '设置为默认地址失败!');
            }
            $res = M('address')->where(array('id' => $address['address_id']))->save(array('default' => 'Y'));
            if ($res === false) {
                $model->rollback();
                return array('error' => '设置为默认地址失败!');
            }
        }
        $model->commit();
        return $address['address_id'];
    }

    /**
     * 删除收货地址
     */
    public function deleteUserAddress($uid, $address_id) {

        // 检测用户是否登录
        if (!trim($uid)) {
            return array('error' => '用户未登录，不能删除地址!');
        }

        if (!$address_id) {
            return array('error' => '删除的地址id不能为空!');
        }

        $addres_res = M('address')->where(array('id' => $address_id))->field('user_id')->find();
        if (!$addres_res) {
            return array('error' => '需要删除的地址不存在!');
        }
        if (!isset($addres_res['user_id']) || intval($addres_res['user_id']) !== intval($uid)) {
            return array('error' => '删除的地址不是自己的地址!');
        }
        $res = M('address')->where(array('id' => $address_id))->delete();
        if (!$res) {
            return array('error' => '删除地址失败!');
        }
        return $address_id;
    }

    /**
     * 设置默认地址
     * @param type $uid
     * @param type $address_id
     */
    public function setDefaultAddress($uid, $address_id) {
        // 检测用户是否登录
        if (!trim($uid)) {
            return array('error' => '用户未登录，不能删除地址!');
        }

        if (!$address_id) {
            return array('error' => '设置的地址id不能为空!');
        }
        $model = M();
        $model->startTrans();

        $res = M('address')->where(array('user_id' => $uid))->save(array('default' => 'N'));
        if ($res === false) {
            $model->rollback();
            return array('error' => '设置为默认地址失败!');
        }
        $res = M('address')->where(array('id' => $address_id))->save(array('default' => 'Y'));
        if ($res === false) {
            $model->rollback();
            return array('error' => '设置为默认地址失败!');
        }
        $model->commit();
        return $address['address_id'];
    }

}
