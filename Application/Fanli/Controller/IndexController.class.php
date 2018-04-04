<?php
namespace Fanli\Controller;

class IndexController extends CommonController{    

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
    }

    /**
     * 选择城市
     */
    public function city() {
        $hotCity = $this->_hotCity();
        $this->assign('hotCity', array_chunk($hotCity, 4));
        $cityList = $this->_getCategoryList('city');
        $letter = array();
        foreach($cityList as $row) {
            if(!in_array(strtoupper($row['letter']), $letter)) {
                $letter[] = strtoupper($row['letter']);
            }
        }
        sort($letter, SORT_STRING);

        $this->assign('letter', $letter);
        $this->assign('cname', cookie(C('SAVE_CITY_KEY')));
        $this->display();
    }

    /**
     * 城市定位
     */
    public function locationCity() {
        // 接收参数
        $this->_checkblank(array('lng', 'lat'));
        $lng = I('get.lng', 0, 'doubleval');
        $lat = I('get.lat', 0, 'doubleval');
        $distance = I('get.distance ', 5, 'doubleval');
        
        // 定位城市
        $team = D('Team');
        $lngLatSquarePoint = $team->returnSquarePoint($lng, $lat, $distance);
        $_where = array(
            'partner.city_id' => array('GT', 0),
            'partner.`long`'  => array('EGT', $lngLatSquarePoint['left-top']['lng']),
            'partner.`lat`'   => array('EGT', $lngLatSquarePoint['left-bottom']['lat']),
        );
        $where = "partner.`long`<={$lngLatSquarePoint['right-bottom']['lng']} AND partner.`lat`<={$lngLatSquarePoint['left-top']['lat']}";
        $distanceField = $team->getMysqlDistanceField($lat, $lng);
        $field = array(
            'city_id' => 'city_id',
            $distanceField => 'distance',
        );

       $query_table = M('partner')->where($_where)->where($where)->field($field)->order('distance asc')->limit(20)->select(false);
        $res = M('partner')->table("($query_table) as t")->field(array('t.city_id'=>'city_id','count(t.city_id)'=>'city_id_count'))->group('t.city_id')->order('city_id_count desc')->find();
        $city_id   = 0;
        $city_name = '';
        if (isset($res['city_id']) && trim($res['city_id'])) {
            $city_res = $this->_getCategoryList('city');
            $city_id = $res['city_id'];
            $city_name = ternary($city_res[$city_id]['name'], '');
            $city_url = U('Index/changeCity', array('id' => $city_id));
        }
        $data = array(
            'status'    => 1,
            'city_id'   => $city_id,
            'city_name' => $city_name,
            'url'       => $city_url
        );
        $this->ajaxReturn($data);
    }

    /*
    *  主页
    */
    public function index() {  
        $fpartner = M('fanli_partner'); 
        //新店入住       
        $threedays = strtotime("-5 days");
        $time = array(                
            'create_time'=>array('gt',$threedays),
        );
        $fpdata = $fpartner->where($time)->order('create_time desc')->limit(3)->select();       
        $partner = array();
        foreach ($fpdata as $k => $v) {
            $partner[$k]['id'] = $v['id'];
            $partner[$k]['img'] = $v['image'];
            $partner[$k]['title'] = $v['title'];
            $partner[$k]['address'] = $v['address'];
            $fandis = M('fanli_dis');
            $dis = $fandis->where('partner_id='.$v['id'])->getField('ratio');            
            $partner[$k]['dis'] = (1-$dis)*0.5*100;
        }
        //推荐商家
        $threedays = strtotime("-5 days");
        $time = array(                
            'posid'=>'1',
        );
        $fptui = $fpartner->where($time)->order('create_time desc')->limit(10)->select();       
        $tuijian = array();
        foreach ($fptui as $k => $v) {
            $tuijian[$k]['id'] = $v['id'];
            $tuijian[$k]['img'] = $v['image'];
            $tuijian[$k]['title'] = $v['title'];
            $tuijian[$k]['address'] = $v['address'];
            $fandis = M('fanli_dis');
            $fpdis = $fandis->where('partner_id='.$v['id'])->getField('ratio');
            
            $tuijian[$k]['dis'] = (1-$fpdis)*0.5*100;
        }

        $this->assign('data',$partner);
        $this->assign('tuijian',$tuijian);
        $this->display();
    }    
    
    /*
    *   商家详情页
    */
    public function PartDetail() {
        $id = I('get.id','','intval'); 
        $time = array(                
            'id'=>$id,
        );
        //当前商家信息
        $fpartner = M('fanli_partner'); 
        $partner = $fpartner->where($time)->find();  
        $fandis = M('fanli_dis');
        $aratio = $fandis->where('partner_id='.$id)->getField('ratio'); 
        $partner['ratio'] = (1-$aratio)*0.5*100;

        //商家相册图片数量
        $fpic = M('fanli_picture');
        $where = array(
            'pid'=>$id,
        );
        $partner['count'] = $fpic->where($where)->count('id');

        //推荐商家信息
        $fptime = array(                
            'posid'=>'1',
        );
        $fptui = $fpartner->where($fptime)->order('create_time desc')->limit(10)->select();       
        $tuijian = array();
        foreach ($fptui as $k => $v) {
            $tuijian[$k]['id'] = $v['id'];
            $tuijian[$k]['img'] = $v['image'];
            $tuijian[$k]['title'] = $v['title'];
            $tuijian[$k]['address'] = $v['address'];
            $fandis = M('fanli_dis');
            $fpdis = $fandis->where('partner_id='.$v['id'])->getField('ratio');
            $tuijian[$k]['dis'] = (1-$fpdis)*0.5*100;
        }

        $this->assign('tuijian',$tuijian);
        $this->assign('data',$partner);
        $this->display();
    }  

    /*
    *  商家相册
    */
    public function search() {        
        $query = urldecode(I('post.keyword','','trim'));
        //商家相册图片
        $fpart = M('fanli_partner');
        $where = array();
        if($query){
            $where['_string'] = "`title` like '%{$query}%'";
        }
        $fdata= $fpart->where($where)->select();
        $result = array();
        foreach ($fdata as $k => $v) {
            $result[$k]['id'] = $v['id'];
            $result[$k]['img'] = $v['image'];
            $result[$k]['title'] = $v['title'];
            $result[$k]['address'] = $v['address'];
            $fandis = M('fanli_dis');
            $fpdis = $fandis->where('partner_id='.$v['id'])->getField('ratio');            
            $result[$k]['dis'] = (1-$fpdis)*0.5*100;
        }
        if(!$result){
            $fptui = $fpart->order('create_time desc')->select();       
            $tuijian = array();
            foreach ($fptui as $k => $v) {
                $tuijian[$k]['id'] = $v['id'];
                $tuijian[$k]['img'] = $v['image'];
                $tuijian[$k]['title'] = $v['title'];
                $tuijian[$k]['address'] = $v['address'];
                $fandis = M('fanli_dis');
                $fpdis = $fandis->where('partner_id='.$v['id'])->getField('ratio');
                $tuijian[$k]['dis'] = (1-$fpdis)*0.5*100;
            }
            $this->assign('tuijian',$tuijian);
        }else{
            $this->assign('data',$result);
        }           
        $this->display();
    } 
    

    /*
    *  商家相册
    */
    public function merchantsAlbum() {
        $id = I('get.id','','intval'); 
        //商家相册图片
        $fpic = M('fanli_picture');
        $where = array(
            'pid'=>$id,
        );
        $picture= $fpic->where($where)->select();       

        $this->assign('picture',$picture);        
        $this->display();
    } 
    
    /*
    *  所有商家
    */
    public function more() {        
        //商家相册图片
        $fpart = M('fanli_partner');
        $all_part= $fpart->order('create_time desc')->select();       

        $tuijian = array();
        foreach ($all_part as $k => $v) {
            $tuijian[$k]['id'] = $v['id'];
            $tuijian[$k]['img'] = $v['image'];
            $tuijian[$k]['title'] = $v['title'];
            $tuijian[$k]['address'] = $v['address'];
            $fandis = M('fanli_dis');
            $fpdis = $fandis->where('partner_id='.$v['id'])->getField('ratio');            
            $tuijian[$k]['dis'] = (1-$fpdis)*0.5*100;
        }

        $this->assign('data',$tuijian);        
        $this->display();
    } 


}