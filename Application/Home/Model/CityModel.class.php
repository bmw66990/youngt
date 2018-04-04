<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/23
 * Time: 16:05
 */
namespace Home\Model;

class CityModel{
    /**
     *
     * 根据参数获取城市
     *
     * @return array
     */
    public function getCityByEname($ename)
    {
        // 根据参数获取城市
        $condition['ename'] = $ename;
        $condition['zone']='city';
        $city = $this -> __findCity($condition);
        return $city ? $city : null;
    }

    /**
     * getCityByIp
     *
     * 根据定位信息获取城市
     *
     * @return array 城市信息
     */
    public function getCityByIp()
    {
        $city = null;
        $location = $this->__location();
        preg_match('/(.*)省(.*)市/', $location['country'], $matches);
        if(!empty($matches)){
            $condition['zone'] = 'city';
            $condition['czone'] = $matches[1];
            $condition['name'] = $matches[2];
            $city = $this->__findCity($condition);
        }
        return $city ? $city : null;
    }

    /**
     * _findCity
     *
     * 私有方法, 在数据库中查找城市
     *
     * @param array 城市查询条件
     * @return array 城市信息
     */
    private function __findCity($condition)
    {
        $Category = D('Category');
        $city = $Category   ->field('id,zone,czone,name,ename,letter,sort_order,display,relate_data,fid')
                            ->where($condition)
                            ->find();
        return $city;
    }

    /**
     * _location
     *
     * 私有方法, 根据访问者的IP获取定位信息
     *
     * @param string IP地址
     * @param string 地位信息编码
     * @param string IP库文件名
     * @return array 定位信息
     */
    private function __location($ip='',$charset='gbk',$file='QQWry.dat') {
        static $location = array();
        if(!empty($location)) {
            return $location;
        }else{
            $iplocation = new \Common\Org\IpLocation($file);
            $location = $iplocation->getlocation($ip);
        }
        if('utf-8' != $charset) {
            foreach($location as $k => $v){
                $location[$k] = iconv($charset,'utf-8',$v);
            }
        }
        return $location;
    }
}