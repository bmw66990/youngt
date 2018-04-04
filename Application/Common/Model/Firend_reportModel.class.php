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

class Firend_reportModel extends CommonModel {

	/**
     * 获取数据总条数
     * @param $where
     * @return mixed
     */
    public function getTotalReport($where) {
        $count = M('Firend_report')->group("art_id")->where($where)->select();
        $counte = count($count);
        if ($counte === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
		
        return $counte;
    }

   /**
     * 获取数据列表
     * @param $where
     * @param $order
     * @param string $limit
     * @param string $field
     * @return mixed
     */
    public function getListReport($where, $order = '', $limit = '', $field = '*') {
        if (empty($order)) {
            $order = $this->getPk() . ' DESC';
        }		
		
        $data = $this-> group("art_id")->field($field)->where($where)->order($order)->limit($limit)->select();
		
		foreach($data as &$v){ 
            $map_user = array(
                'id' => $v['userid'],
            );
            $map_user = M('user')->where($map_user)->getField('username');
            $v['report_name'] = $map_user;            
			$map_art = array(
                'id' => $v['art_id'],
            );
            $count = M('Firend_report')->where('art_id='.$v['art_id'])->count('id');
            $v['hits']= $count;
			$art = M('firend')->where($map_art)->find();			
			$v['catid'] = $art['catid']; 
			$map_userid = array(
                'id' => $art['userid'],
            );
			$user = M('user')->where($map_userid)->find();			
			$v['art_avatar'] = getImagePath($user['avatar']);			
			$v['art_username'] = $user['username']; 			
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
            $map_art = array(
                'id' => $v['art_id'],
            );
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
