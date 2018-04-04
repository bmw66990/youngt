<?php
/**
 * Created by PhpStorm.
 * User: daipingshan  <491906399@qq.com>
 * Date: 2015-03-26
 * Time: 17:57
 */
namespace Common\Model;
use Common\Model\CommonModel;

class CreditModel extends CommonModel {

	/**
	*  	获取积分列表
	*  	@param array  $where : 获取数据信息的条件
	*	@param string $limit : 分页
	*	@param string $field : 需要查询的数据字段
	*  	@return array | bool : 返回数据信息
	*/
    public function getCredits($where,$limit,$orderby='c.id desc',$field='*') {
		$data =$this->alias('c')
					->field($field)
					->join('inner join user u ON c.user_id = u.id')
                    ->force('PRIMARY')
					->where($where)
					->order($orderby)
					->limit($limit)
					->select();
		if($data===false){
			$this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
		}
		return $data;
    }

    /**
	*  	获取积分总记录数
	*  	@param array  $where : 获取数据信息的条件
	*  	@return int | bool : 返回总记录数
	*/
    public function getCreditCount($where) {
		$count =$this->alias('c')
					->join('left join user u ON c.user_id = u.id')
					->where($where)
					->count('c.id');
		if($count===false){
			$this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
		}
		return $count;
    }

}