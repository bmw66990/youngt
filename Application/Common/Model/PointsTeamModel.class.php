<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-07-21
 * Time: 16:10
 */
namespace Common\Model;

class PointsTeamModel extends CommonModel {

    protected $_validate = array(
        array('name', 'require', '请填写兑换商品名'),
        array('city_id', 'require', '请选择城市'),
        array('partner_id', 'require', '商家必须选择'),
        array('begin_time', 'require', '请填写开始时间'),
        array('end_time', 'require', '请填写结束时间'),
        array('expire_time', 'require', '请填写过期时间'),
        array('score', 'require', '请填写花费积分'),
    );

    protected $_auto = array(
        array('begin_time', 'strtotime', 3, 'function'),
        array('end_time', 'strtotime', 3, 'function'),
        array('expire_time', 'strtotime', 3, 'function'),
    );

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getDetail($id){
        $info = $this->alias('pt')->field('pt.*,p.title,p.address,p.phone')->join('left join partner p ON p.id = pt.partner_id')->where(array('pt.id'=>$id))->find();
        if($info === false){
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql'] = $this->_sql();
        }
        return $info;
    }
}