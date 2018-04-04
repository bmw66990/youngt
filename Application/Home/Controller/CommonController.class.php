<?php

/**
 * Created by PhpStorm.
 * User: daipingshan
 * Date: 2015/4/23
 * Time: 15:42
 */

namespace Home\Controller;

use Common\Controller\CommonBusinessController;

/**
 * PC公共控制器
 * Class CommonController
 * @package Home\Controller
 */
class CommonController extends CommonBusinessController {

    /**
     * @var bool 是否验证city
     */
    protected $cityCheck = true;

    /**
     * @var bool 是否验证登陆
     */
    protected $userCheck = false;

    /**
     * @var bool 检测来源是否为手机
     */
    protected $fromCheck = true;

    /**
     * @var int 用户id
     */
    protected $uid = 0;

    /**
     * 构造方法
     */
    public function __construct() {
        parent:: __construct();
        
        // 过滤请求数据 防止xss攻击
        $_GET && safeFilter($_GET);
        
        // 如果客户端为移动设备则为其切换移动主题
        if (is_mobile() && $this->fromCheck === true) {
             $request_uri = strtolower($_SERVER['REQUEST_URI']);
             $host_url = 'http://m.youngt.com';
             if(strpos($request_uri, 'team-') !==false){
                 list($_,$team_id)=  explode('-', $request_uri);
                 $team_id = intval($team_id);
                  $host_url = "http://m.youngt.com/Team/detail/id/{$team_id}.html";
             }
            redirect($host_url);
        }
        //检测是否选择城市
        if ($this->cityCheck) {
            $this->_checkCity();
        }
        //检测用户是否登陆
        if ($this->userCheck) {
            $this->_checkUser();
        }
        if ($this->_getUserId()) {
            $member = $this->_getUserInfo();
            $this->assign('member', $member);
            $this->assign('isLogin', 1);
        } else {
            $this->assign('isLogin', 0);
        }
        // 保存客户来源地址
        if (!cookie('referer') && $_SERVER['HTTP_REFERER']) {
            cookie('referer', $_SERVER['HTTP_REFERER'], 86400);
        }
                
        $city_info = cookie('city');
        $this->assign('cityInfo', $city_info);
        
        // 首页热门城市
        $periphery_city  = $this->_peripheryCity($city_info['czone'],$city_info['id']);
        $this->assign('periphery_city',$periphery_city);

        // 错误信息统一收集
        $error = I('get.error', '', 'strval');
        if(trim($error)){
            $this->assign('error', base64_decode(str_replace(array('%2b',' '),'+',urldecode($error))));
        }
    }

    /**
     * 检测城市是否存在
     */
    protected function _checkCity() {
        $City = new \Home\Model\CityModel();
        $site = I('get.site', '', 'strval');
        // 如果请求的是分站,从数据库中获取城市
        $city = $City->getCityByEname($site);
        if(!$city){
            $city = $City->getCityByEname('yangling');
        }
        $cookie_city = cookie('city');
        if ($site) {
            if($site == 'www' && APP_DOMAIN == 'youngt.com'){
                if ($city['id'] != $cookie_city['id']) {
                    cookie('city', $city);
                }
                redirect('http://'.$city['ename'].'.'.APP_DOMAIN);
            } else {
                if($city['id'] != $cookie_city['id']){
                    cookie('city', $city);
                }
            }
        } else {
            // 如果cookie中没有城市信息，通过ip定位城市
            if (!$cookie_city) {
                $city = $City->getCityByIp();
                if ($city) {
                    cookie('city', $city);
                } else {
                    cookie('city', $city);
                }
            }
        }
    }

    protected function _checkUser() {
        // 检测用户是否登陆
        $session_uid = session(C('MEMBER_AUTH_KEY')) ? session(C('MEMBER_AUTH_KEY')) : 0;
        if ($session_uid == 0) {
            $cookie_uid = cookie(C('MEMBER_AUTH_KEY')) ? cookie(C('MEMBER_AUTH_KEY')) : 0;
            if ($cookie_uid == 0) {
                if (IS_AJAX) {
                    //TODO 根据后期需求修改
                    $this->error('请登录');
                    exit();
                } else {
                    redirect(U('Public/login'));
                }
            } else {
                session(C('MEMBER_AUTH_KEY'), $cookie_uid);
                $this->uid = $cookie_uid;
            }
        } else {
            $this->uid = $session_uid;
        }
    }

    /**
     * 获取用户基本信息
     * @return mixed
     */
    protected function _getUserInfo() {
        $this->_checkUser();
        $uid = $this->uid;
        $field = 'id,username,mobile,score,money,email,unid,sns';
        return D('User')->info($uid, $field);
    }

    /**
     * 获取登陆用户ID
     * @return int|mixed|void
     */
    protected function _getUserId() {
        $this->uid = session(C('MEMBER_AUTH_KEY'));
        return $this->uid;
    }

    /**
     * 设置标题和seo
     * @param $info  array('title' => '标题');
     * @return mixed
     */
    protected function _getWebTitle($info = array()) {
        $Category = M('category');
        $city = Cookie('city');
        $info['city'] = $city['name'];
        $delimiter = "_";
        $cid[] = $_GET['zid'] ? $_GET['zid'] : false;
        $cid[] = $_GET['sid'] ? $_GET['sid'] : false;
        $cid[] = $_GET['gid'] ? $_GET['gid'] : false;
        if ($cid) {
            $cate_gory_res = $this->_getCategoryList('group');
            foreach ($cid as $val) {
                if(isset($cate_gory_res[$val]['name'])){
                     $content[] = $cate_gory_res[$val]['name'];
                } 
            }
            if ($_GET['p'] >= 2) {
                $content[] = '第' . $_GET['p'] . '页';
            }
            $content = htmlspecialchars(implode($delimiter, $content));
        }
        if ($info && $content) {
            $info['title'] = $content . $delimiter . $info['title'] . '青团网' . $info['city'] . '站' . $delimiter . $info['city'] . '团购网站' . $delimiter . '县城本地生活服务,' . $info['city'] . '青团网' . $delimiter . $info['city'] . '团购' . $delimiter . '县城本地生活服务';
            $info['description'] = $content . $delimiter . $info['title'] . '青团网' . $info['city'] . '站 - 县城本地生活服务！为您汇集最全面最优惠的大学城美食娱乐团购打折促销信息！青团网为您精选' . $info['city'] . '内的自助餐、电影票、KTV、美发、足浴特色商家，享尽无敌折扣！';
            $info['keywords'] = $content . $delimiter . $info['title'] . '青团网 ' . $info['city'] . ',青团网' . $info['city'] . '站,' . $info['city'] . '团购,打折,' . $info['city'] . '打折,优惠券,' . $info['city'] . '优惠券,' . $info['city'] . '校园团购';
        } else if ($info) {
            if ($info['title']) {
                $info['title'] = $info['title'] . $delimiter . '青团网' . $info['city'] . '站' . $delimiter . $info['city'] . '团购网站' . $delimiter . '县城本地生活服务,' . $info['city'] . '青团网' . $delimiter . $info['city'] . '团购' . $delimiter . '县城本地生活服务';
                $info['description'] = $info['title'] . $delimiter . '青团网' . $info['city'] . '站 - 县城本地生活服务！为您汇集最全面最优惠的大学城美食娱乐团购打折促销信息！青团网为您精选' . $info['city'] . '内的自助餐、电影票、KTV、美发、足浴特色商家，享尽无敌折扣！';
                $info['keywords'] = $info['title'] . $delimiter . '青团网 ' . $info['city'] . ',青团网' . $info['city'] . '站,' . $info['city'] . '团购,打折,' . $info['city'] . '打折,优惠券,' . $info['city'] . '优惠券,' . $info['city'] . '校园团购';
            } else {
                $info['title'] = '青团网' . $info['city'] . '站' . $delimiter . $info['city'] . '团购网站' . $delimiter . '县城本地生活服务,' . $info['city'] . '青团网' . $delimiter . $info['city'] . '团购' . $delimiter . '县城本地生活服务';
                $info['description'] = '青团网' . $info['city'] . '站 - 县城本地生活服务！为您汇集最全面最优惠的大学城美食娱乐团购打折促销信息！青团网为您精选' . $info['city'] . '内的自助餐、电影票、KTV、美发、足浴特色商家，享尽无敌折扣！';
                $info['keywords'] = '青团网 ' . $info['city'] . ',青团网' . $info['city'] . '站,' . $info['city'] . '团购,打折,' . $info['city'] . '打折,优惠券,' . $info['city'] . '优惠券,' . $info['city'] . '校园团购';
            }
        } else {
            $info['title'] = '青团网_团购网站_县城本地生活服务,青团网|团购|县城本地生活服务';
            $info['description'] = '青团网_团购网站_县城本地生活服务,为您汇集最全面最优惠的大学城美食娱乐团购打折促销信息！青团网为您精选！自助餐、电影票、KTV、美发、足浴特色商家，享尽无敌折扣！';
            $info['keywords'] = '青团网_团购网站_县城本地生活服务,打折、优惠券、校园团购';
        }
        $this->assign($info);
        return $info;
    }

    /**
     * 获取当前城市信息
     * @return array|mixed|void
     */
    protected function _getCityInfo() {
        return cookie('city') ? cookie('city') : array();
    }

}
