<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/14
 * Time: 11:08
 */

namespace Common\Model;
use \Common\Model\CommonModel;

class FeedbackModel extends CommonModel{
    /**
     * 自动验证
     * @var array
     */
    protected $_validate  = array(
        array('title', 'require', '标题必须填写',1),
        array('category', 'require', '分类必须选择',1),
        array('contact', 'require', '手机必须填写',1),
        array('address', 'require', '地址必须填写',1),
        array('content', 'require', '内容必须填写',1),
    );
    /**
     * 自动完成
     */
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
    );

    /**
     * @param $where
     * @param string $order
     * @param $limit
     * @param string $field
     * @return mixed
     */
    public function getAllList($where,$order='',$limit,$field="*"){
        if($order == ''){
            $order = 'f.id desc';
        }
        $data = $this->alias('f')->join('left join user u ON u.id = f.user_id')->field($field)->where($where)->order($order)->limit($limit)->select();
        if($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }
}