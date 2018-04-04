<?php
/**
 * Created by PhpStorm.
 * User: daipingshan  <4914906399@qq.com>
 * Date: 2015-03-26
 * Time: 17:46
 */
namespace Common\Model;
use Common\Model\CommonModel;

class AdmanageModel extends CommonModel {
    /**
     * 自动验证
     * @var array
     */
    protected $_validate = array(
        array('cityid', 'require', '请选择广告展示城市'),
        //array('linkarr', 'require', '广告链接必须填写'),
        array('textarr', 'require', '广告标题必须填写'),
        //array('end_time', 'require', '广告的结束时间必须填写'),
    );

    /**
     * 自动完成
     */
    protected $_auto = array(
        array('end_time', 'strtotime', 3, 'function'),
        array('begin_time', 'strtotime', 3, 'function'),
    );

    public function getAdManageList($city_id, $type='pc'){
        $map['size'] = 'Y';
        $map[]       = "cityid = $city_id  OR city_ids LIKE '%@$city_id@%' OR cityid = 957";
        $map[]       = 'end_time > ' . time();
        $map['type'] = $type;
        $data = $this->field('linkarr,picarr,textarr')->where($map)->order('sort_order desc')->limit(20)->select();
        if($data === false){
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $data;
    }
    
  /**
     * 获取城市相关活动
     */
    public function getActivitiesList($city='',$is_begin_time=true){
        
        $now_time = time();
        
        $where = array(
            'type'=>'activities',
            'cityid'=>957,
            'end_time' => array('gt', $now_time)
        );
        if($is_begin_time===true){
            $where['begin_time'] = array('lt', $now_time);
        }
        if(trim($city)){
            $where['cityid']=array('in',array(intval($city),957));
        }
        
        $Model = M('admanage');
        $data = $Model->field('id,picarr as image,pic,linkarr as href,textarr as title,show_type,begin_time,cityid')->where($where)->order('sort_order desc')->select();
        if($data){
            foreach($data as &$v){
                $v['image'] = getImagePath($v['image']);
                $v['pic'] = getImagePath($v['pic']);
            }
            unset($v);
        }
        return $data ? $data:array();
    }
    
    /**
     *  活动是否存在
     * @param type $where
     */
    public function isExistActivities($where = array()){
        if(!$where){
            return false;
        }
        if(!isset($where['type']) || !trim($where['type'])){
            $where['type']='activities';
        }
        
        $activities = $this->where($where)->count();
        if($activities && $activities>0){
            return true;
        }
        return false;
       
    }
    
    /**
     * 根据条件获取活动列表
     * @param string $where
     * @return type
     */
    public function getActivitiesListByWhere($where=array()){
        if(!$where){
            return array();
        }
        if(!isset($where['type']) || !trim($where['type'])){
            $where['type']='activities';
        }
        
        $data = $this->field('id,picarr as image,linkarr as href,textarr as title,show_type,begin_time,end_time')->where($where)->order('sort_order desc')->select();
        if($data){
            foreach($data as &$v){
                $v['image'] = getImagePath($v['image']);
            }
            unset($v);
        }
        return $data ? $data:array();
        
    }

}