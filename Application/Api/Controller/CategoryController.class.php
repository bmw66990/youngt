<?php
/**
 * Created by PhpStorm.
 * User: daipingshan
 * Date: 2015/4/10
 * Time: 11:22
 */

namespace Api\Controller;

use Api\Controller\CommonController;

class CategoryController extends CommonController {

    /*
     * 获取城市列表信息
     */
    public function getCityList() {
        $city_id = I('get.city_id', '0', 'intval');
        if ($city_id) {
            $zone  = "district";
            $where = array('fid' => $city_id);
            $data  = $this->_getCategoryList($zone, $where);
            if ($data === false) {
                $this->outPut(null, 1002);
            }
            foreach ($data as &$val) {
                $zone           = "station";
                $where          = array('fid' => $val['id']);
                $val['station'] = array_values($this->_getCategoryList($zone, $where));
            }
            $data = array_values($data);
        } else {
            $zone    = "city";
            $list    = $this->_getCategoryList($zone);
            if ($list === false) {
                $this->outPut(null, 1002);
            }
            $hotCity = $this->_hotCity();
            $data    = array('allCity' => array_values($list), 'hotCity' => array_values($hotCity));
        }
        $this->outPut($data, 0);
    }

    /**
     * 获取团购类型
     */
    public function getCateList() {
        $zone = "group";
        $list = $this->_getCategoryList($zone);
        if ($list === false) {
            $this->outPut(null, 1002);
        }
        $res = array();
        foreach ($list as $key => $value) {
            if ($value['fid'] == '0') {
                $value['imgurl']    = $this->_getImgUrl($value['id']);
                $value['subClasss'] = array();
                $res[$value['id']]  = $value;
            } else {
                if ($value['fid'] != 1618) {
                    $res[$value['fid']]['subClasss'][] = $value;
                }
            }
        }
        unset($res['1847']);
        $data = array_values($res);
        $this->outPut($data, 0);
    }

    /**
     * 首页分类信息
     * @return string
     */
    public function getMoreCate(){
        $city_id = I('get.city_id', '0', 'intval');
        $app_ver = I('get.ver', '', 'trim');
        if(!$city_id) {
            $this->outPut(null, 1001);
        }
        $Model   = M('app_category');
        $cate = $Model->where('city_id='.$city_id)->find();
        if($cate){
            $data = unserialize($cate['cate']);
            foreach ($data as $key => &$val){
                $val['url']=$this->_getImgUrl($val['id'],$app_ver);
                /*if($key>7){
                    $this->_getImgUrl($val['id']);
                }else{
                    $val['url'] ="http://pic.youngt.com/static/38/{$key}.png";//$this->_getImgUrl($val['id']); //准备3.8号加
                }*/
                $val['id']  = strval($val['id']);
                $val['fid'] = strval($val['fid']);
            }
        }else{
            $data = $this->_initCate($app_ver);
        }
        $this->outPut($data,0);
    }

    /**
     * 默认分类
     * @return string
     */
    protected function _initCate($ver=''){
        return array(
            array('id'=>'255','fid'=>'0','name'=>'美食','sort'=>'8','url'=>$this->_getImgUrl(255,$ver)),
            array('id'=>'420','fid'=>'12','name'=>'电影','sort'=>'7','url'=>$this->_getImgUrl(420,$ver)),
            array('id'=>'404','fid'=>'0','name'=>'旅游酒店','sort'=>'6','url'=>$this->_getImgUrl(404,$ver)),
            array('id'=>'256','fid'=>'12','name'=>'KTV','sort'=>'5','url'=>$this->_getImgUrl(256,$ver)),
            array('id'=>'12','fid'=>'0','name'=>'娱乐','sort'=>'4','url'=>$this->_getImgUrl(12,$ver)),
            array('id'=>'418','fid'=>'255','name'=>'火锅','sort'=>'3','url'=>$this->_getImgUrl(418,$ver)),
            array('id'=>'1560','fid'=>'0','name'=>'代金券','sort'=>'2','url'=>$this->_getImgUrl(1560,$ver)),
            array('id'=>'72','fid'=>'0','name'=>'其他','sort'=>'1','url'=>$this->_getImgUrl(72,$ver)),
        );
    }
    /*
     * 获取分类对应图标
     */
    protected function _getImgUrl($id,$ver='') {
        $url = "http://pic.youngt.com/static/cateimg1";
        $url = "http://pic.youngt.com/static/cateimg/spring_image";
        if($ver){
            $url = "http://pic.youngt.com/static/newcate";
        }
        switch ($id) {
            case 255:
                $img = "{$url}/meishi.png";
                break;
            case 1560:
                $img = "{$url}/daijingquan.png";
                break;
            case 12:
                $img = "{$url}/xiuxianyule.png";
                break;
            case 419:
                $img = "{$url}/shenghuofuw.png";
                break;
            case 14:
                $img = "{$url}/meirongbaojian.png";
                break;
            case 15:
                $img = "{$url}/jiaoyupeixun.png";
                break;
            case 16:
                $img = "{$url}/gouwu.png";
                break;
            case 404:
                $img = "{$url}/lvyoujiudian.png";
                break;
            case 72:
                $img = "{$url}/qita.png";
                break;
            case 587:
                $img = "{$url}/zizhucan.png";
                break;
            case 589:
                $img = "{$url}/shaokao.png";
                break;
            case 418:
                $img = "{$url}/huoguo.png";
                break;
            case 256:
                $img = "{$url}/ktv.png";
                break;
            case 412:
                $img = "{$url}/sheying.png";
                break;
            case 417:
                $img = "{$url}/xican.png";
                break;
            case 608:
                $img = "{$url}/flower.png";
                break;
            case 420:
                $img = "{$url}/dianying.png";
                break;
            case 425:
                $img = "{$url}/zuyu.png";
                break;
            case 427:
                $img = "{$url}/xiaochi.png";
                break;
            case 2060:
                $img = "{$url}/kuaican.png";
                break;
            default :
                $img = "{$url}/qita.png";
                break;
        }
        return $img;
    }

}

