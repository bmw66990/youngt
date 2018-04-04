<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-07-27
 * Time: 15:34
 */
namespace Common\Model;

class FeedbackQuestionModel extends CommonModel {

    protected $_validate = array(
        array('content', 'require', '内容必须填写'),
        array('cid', 'require', '分类必须选择'),
        array('city_id', 'require', '城市id必须填写', 1),
        array('user_id', 'require', '用户id必须填写', 1),
    );

    protected $_auto = array(
        array('add_time', 'time', 1, 'function'),
    );

    /**
     * @param $where
     * @param string $order
     * @param string $limit
     * @param string $field
     *
     * @return mixed
     */
    public function getAllList($where,$order,$limit,$field='*'){
        $data = $this->alias('f')->join(array('LEFT JOIN user u ON f.user_id = u.id', 'LEFT JOIN category c ON c.id = f.cid'))->field($field)->where($where)->order($order)->limit($limit)->select();
        if($data===false){
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $data;
    }

}