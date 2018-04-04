<?php

/**
 * Team 项目表模型
 * Created by JetBrains PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 15-3-21
 * Time: 下午17:30
 * To change this template use File | Settings | File Templates.
 */

namespace Common\Model;

use Common\Model\CommonModel;

class Firend_report_allModel extends CommonModel {

   /**
     * 获取数据列表
     * @param $where
     * @param $order
     * @param string $limit
     * @param string $field
     * @return mixed
     */
    public function getUserListReport($where, $order = '', $limit = '', $field = '*') {
        if (empty($order)) {
            $order = $this->getPk() . ' DESC';
        }		
		
        $data = $this->field($field)->where($where)->order($order)->limit($limit)->select();
		
		foreach($data as &$v){ 
            
            $user = M('user')->where('id='.$v['userid'])->find();            
            $v['avatar'] = getImagePath($user['avatar']);               
            $v['username'] = $user['username'];
			
			$art = M('firend')->where($map_art)->find();			
			$v['catid'] = $art['catid']; 
			$map_userid = array(
                'id' => $art['userid'],
            );
		      
			$v['art_title'] = $art['title'];
			$_city = array(
                'catid' => $art['catid'],
            );
			$city_id = M('firend_zone')->where($_city)->getField('city_id');
			$v['city_id'] = $city_id;
			
			$map_city = array(
                'id' => $city_id,
            );
            $map_city = M('category')->where($map_city)->getField('name');
            $v['city'] = $map_city;
        } 
		
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }
}
