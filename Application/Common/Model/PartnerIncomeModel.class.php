<?php
/**
 * Created by PhpStorm.
 * User: daipingshan
 * Date: 2015/4/22
 * Time: 9:22
 */

namespace Common\Model;
use Common\Model\CommonModel;

/**
 * 商家结算表模型
 * Class PartnerIncomeModel
 * @package Common\Model
 */
class PartnerIncomeModel extends CommonModel{
    /**
     * @param $where 条件
     * @param $order 排序
     * @param $limit 分页
     */
    public function dayPayCount($where,$limit,$order){
        $field="FROM_UNIXTIME(create_time,'%Y-%m-%d') as consume_date,count(team_id) as num,team_id";
        $data=$this->field($field)->where($where)->order($order)->group('consume_date')->limit($limit)->select();
        if($data===false){
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
            return false;
        }
        return $data;
    }
}