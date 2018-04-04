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

class Firend_zoneModel extends CommonModel {

   /**
     * 获取数据列表
     * @param $where
     * @param $order
     * @param string $limit
     * @param string $field
     * @return mixed
     */
    public function getList($where, $order = '', $limit = '', $field = '*') {
        if (empty($order)) {
            $order = $this->getPk() . ' DESC';
        }
        $data = $this->field($field)->where($where)->order($order)->limit($limit)->select();
        foreach($data as &$v){  
            $map = array(
                'catid' => $v['catid'],
            );
			
            $number = M('Firend')->distinct(true)->where($map)->getField('userid',true);
			$numbercont = count($number);
            $v['count'] = $numbercont;
            $map_city = array(
                'id' => $v['city_id'],
            );
            $map_city = M('Category')->where($map_city)->getField('name');
            $v['city'] = $map_city;
        }       
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }
}
