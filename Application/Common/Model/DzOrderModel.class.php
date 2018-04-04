<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-18
 * Time: 17:22
 */
namespace Common\Model;

/**
 * 订座订单模型
 * class DzOrderModel
 * @package Common\Model
 */
class DzOrderModel extends CommonModel {

    /**
     * 自动验证
     * @var array
     */
    protected $_validate = array(
        array('dz_id', 'require', '缺少订座信息'),
        array('partner_id','require', '缺少商家信息'),
        array('num', 'require', '请输入就餐人数', ),
        array('create_time','require', '请选择就餐时间'),
        array('username', 'require', '姓名必须填写'),
        array('state', 'require', '请选择订座方式'),
        array('mobile',  'checkMobile', '电话格式不正确', 0, 'function'),
    );

    /**
     * 关联商家获取订座订单总数
     * @param string $where
     * @return mixed
     */
    public function getCount($where) {
        $condition = 'dz.partner_id=p.id';
        $total = $this->table('dz_order dz,partner p')->where($condition)->where($where)->count();
        if($total === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $total;
    }

    /**
     * 获取订座订单列表
     * @param int $partner 商家id
     * @param string $limit
     * @return mixed
     */
    public function getDzOrderList($where, $order = '', $limit = '') {
        $field = 'dz.*,p.title,p.city_id';
        $order = empty($order) ? 'dz.'.$this->getPk() . ' DESC' : $order;
        $condition = 'dz.partner_id=p.id';
        $data = $this->table('dz_order dz,partner p')->field($field)->where($condition)->where($where)->order($order)->limit($limit)->select();
        if($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        //echo $this->_sql();
        return $data;
    }
}