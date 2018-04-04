<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/6/12
 * Time: 16:11
 */

namespace Admin\Controller;

/**
 * 管理控制器
 * Class CategoryController
 * @package Admin\Controller
 */
class ManageController extends CommonController {

    /**
     * 热门分类最大值配置
     */
    const TYPE_MAX_NUM = 8;

    protected $getOperationName = array(
        'index'           => '首页',
        'team'            => '团单',
        'order'           => '订单',
        'coupon'          => '青团券',
        'user'            => '用户',
        'partner'         => '商户',
        'customerservice' => '客服',
        'manage'          => '管理',
        'financial'       => '财务',
        'dingzuo'         => '订座',
        'market'          => '营销',
        'encyclopedias'   => '百科公告',
        'public'          => '公共',
        'feedback'        => '代理反馈',
        'pointsteam'        => '积分'
    );

    protected $type = array('city','district','class','group','partner','station','public','express','grade','feedback');

    /**
     * 管理项目分类
     */
    public function index(){
        redirect(U('Manage/groupType'));
    }

    /**
     * 管理员操作日志
     */
    public function adminLog(){
        $key = I('get.key','','strval');
        $controller_name = I('get.controller_name','','strval');
        $start_time = I('start_time','','strval');
        $end_time   = I('end_time','','strval');
        $where = array();
        $displayWhere = array();
        //关键字筛选
        if($key) {
            $where['admin_username|content'] = array('like',"%{$key}%");
            $displayWhere['key'] = $key;
        }

        // 操作模块筛选
        if($controller_name) {
            $where['controller_name'] = $controller_name;
            $displayWhere['controller_name'] = $controller_name;
        }

        // 时间筛选
        if($start_time && $end_time){
            $where['create_time'] = array('between',array(strtotime($start_time),strtotime($end_time)));
            $displayWhere['start_time'] = $start_time;
            $displayWhere['end_time'] = $end_time;
        }else{
            if($start_time){
                $where['create_time'] = array('lt',strtotime($start_time));
                $displayWhere['start_time'] = $start_time;
            }
            if($end_time){
                $where['create_time'] = array('lt',strtotime($end_time));
                $displayWhere['end_time'] = $end_time;
            }
        }
        $Model = D('AdminOperationLogs');
        $count = $Model->getTotal($where);
        $page  = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        $data = $Model->getList($where, 'id desc', $limit);
        $this->assign('data', $data);
        $this->assign('action', $this->getOperationName);
        $this->assign('where', $displayWhere);
        $this->display();
    }

    /**
     * 城市分类
     */
    public function cityType(){
        $this->_getData('city');
        $this->display();
    }

    /**
     * 团单分类
     */
    public function groupType(){
        $this->_getData('group');
        $this->display();
    }

    /**
     * 商圈分类
     */
    public function districtType(){
        $this->_getData('district');
        $this->display();
    }

    /**
     * 订座分类
     */
    public function classType(){
        $this->_getData('class');
        $this->display();
    }

    /**
     * 商户子商圈分类
     */
    public function stationType(){
        $this->_getData('station');
        $this->display();
    }

    /**
     * 讨论区分类
     */
    public function publicType(){
        $this->_getData('public');
        $this->display();
    }

    /**
     * 快递分类
     */
    public function expressType(){
        $this->_getData('express');
        $this->display();
    }

    /**
     * 订座分类
     */
    public function partnerType(){
        $this->_getData('partner');
        $this->display();
    }

    /**
     * 用户等级分类
     */
    public function gradeType(){
        $this->_getData('grade');
        $this->display();
    }

    /**
     * 问题反馈
     * 分类
     */
    public function feedbackType(){
        $this->_getData('feedback');
        $this->display();
    }


    /**
     * 获取分类数据
     * @param $where
     */
    protected function _getData($type){
        $paramArray = array(
            array('czone',''),
            array('name',''),
            array('ename','')
        );
        $where = $this->createSearchWhere($paramArray);
        $where['zone'] = $type;
        $Model = D('Category');
        $count = $Model->getTotal($where);
        $this->_writeDBErrorLog($count, $Model);
        $page  = $this->pages($count, $this->reqnum, '', 7);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this->assign('pages', $page->show());
        if($type == 'group') {
            $data = $Model->where($where)->index('id')->order('sort_order desc,id desc')->select();
            foreach ($data as $key=>&$val){
                if($val['fid'] == 0){
                    $val['czone'] = '一级大类';
                }else{
                    $val['czone'] = $Model->getFieldById($val['fid'],'name');
                    $data[$val['fid']]['sub'][$val['id']] = $val;
                    unset($data[$key]);
                }
            }
            unset($val);
        } else {
            $data = $Model->getList($where, 'sort_order desc,id desc', $limit);
        }
        $this->_writeDBErrorLog($data, $Model);
        $this->assign('zone',$type.'Type');
        $this->assign('displayWhere',$where);
        $this->assign('data',$data);
    }

    /**
     * 添加分类
     */
    public function addType(){
        $type = I('get.action','','strval');
        if(in_array($type,$this->type)){
            if($type == 'group'){
                $Model = D('Category');
                $group = $Model->getList(array('zone'=>'group','fid'=>0),'sort_order desc','','name,id');
                $this->assign('group',$group);
            }
            $this->assign('type',$type);
        }else{
            $this->redirect_message(U('Manage/cityType'),  array('error' => base64_encode('您要添加的分类不合法！')));
        }
        $this->display();
    }

    public function doAddType(){
        if(IS_AJAX){
            $Model = D('Category');
            $type = I('post.zone','','strval');
            if(in_array($type,$this->type) === false){
                $data = array('status'=>-1,'error'=>'非法请求');
            }else{
                if($type == 'city' && trim(I('post.czone')) == ''){
                    $data = array('status'=>-1,'error'=>'请输入自定义分组名称');
                }
                
                $hot_type = strtoupper(I('post.hot_type','N','trim'));
                if($type == 'group' && $hot_type == 'Y'){
                    $count = D('Category')->getTotal(array('zone'=>$type,'hot_type'=>'Y'));
                    if($count >= self::TYPE_MAX_NUM){
                        $data = array('status'=>-1,'error'=>'热门分类已达上线,最大值'.self::TYPE_MAX_NUM.'个');
                    }
                }
            }
            if(isset($data) === false){
                $res = $Model->insert();
                if($res){
                    $this->_saveCateRedis($type);
                    if($type == 'city'){
                        $this->_saveCache();
                    }
                    $this->addOperationLogs("操作：添加分类,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},分类类型:".I('post.zone').",分类id:{$res}");
                    $data = array('status' =>1,'success'=>'添加成功');
                }else{
                    $data = array('status'=>-1,'error'=>$Model->getError());
                }
            }
        }else{
            $data = array('status'=>-1,'error'=>'非法操作');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 编辑分类
     */
    public function editType(){
        $id = I('get.id',0,'intval');
        if($id){
            $Model = D('Category');
            $cate_info = $Model->info($id);
            if($cate_info['zone'] == 'group'){
                $group = $Model->getList(array('zone'=>'group','fid'=>0),'sort_order desc','','name,id');
                $this->assign('group',$group);
            }
            $this->assign('cate_info',$cate_info);
        }else{
            $this->assign('error','您要添加的分类不合法');
        }
        $this->display();
    }

    /**
     * 编辑处理分类
     */
    public function doEditType(){
        if(IS_AJAX){
            $Model = D('Category');
            $zone = I('post.zone','','trim');
            $hot_type = strtoupper(I('post.hot_type','N','trim'));
            if($zone == 'group' && $hot_type == 'Y'){
                $count = D('Category')->getTotal(array('zone'=>$zone,'hot_type'=>'Y'));
                if($count >= self::TYPE_MAX_NUM){
                    $data = array('status'=>-1,'error'=>'热门分类已达上线,最大值'.self::TYPE_MAX_NUM.'个');
                }
            }
            if($zone == 'city'){
                $this->_saveCache();
            }
            if(isset($data) === false){
                $res = $Model->update();
                if($res){
                    $this->_saveCateRedis($zone);
                    $this->addOperationLogs("操作：编辑分类,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},分类类型:".I('post.zone').",分类id:{$res}");
                    $data = array('status' =>1,'success'=>'编辑成功');
                }else{
                    $data = array('status'=>-1,'error'=>$Model->getError());
                }
            }
        }else{
            $data = array('status'=>-1,'error'=>'非法操作');
        }
        $this->ajaxReturn($data);
    }

    public function delCate(){
        $id = I('get.id',0,'intval');
        $url= I('url','cityType','strval');
        if($id){
            $Model = D('Category');
            $count = $Model->getTotal(array('id'=>$id));
            if($count){
                $res = $Model->delete($id);
                if($res){
                    $this->_saveCateRedis(str_replace('Type','',$url));
                    $this->addOperationLogs("操作：删除分类,管理员id:{$this->user['id']},管理员名称:{$this->user['username']},分类类型:".str_replace('Type','',$url).",分类id:{$id}");
                   $this->redirect_message(U("Manage/{$url}"),  array('success' => base64_encode('删除成功！')));
                }else{
                    $this->redirect_message(U("Manage/{$url}"),  array('error' => base64_encode($Model->getError())));
                }
            }else{
                 $this->redirect_message(U("Manage/{$url}"),  array('error' => base64_encode('信息不存在无法删除！')));
            }
        }else{
             $this->redirect_message(U("Manage/{$url}"),  array('error' => base64_encode('非法操作！')));
        }
    }

    /**
     * 清空redis的key值对应数据
     * @param $type
     */
    protected function _saveCateRedis($type){
        $data = S('category');
        if($data[$type]){
            unset($data[$type]);
            S('category',$data);
        }
    }

    /**
     * 更新缓存数据
     */
    protected function _saveCache(){
        $Model = M('system');
        $info = $Model->where(array('keys'=>'update_cache'))->find();
        if($info){
            $upData = array('values'=>$info['values']+1);
            $Model->where(array('keys'=>'update_cache'))->save($upData);
        }else{
            $addData = array('keys'=>'update_cache','values'=>1);
            $Model->add($addData);
        }
    }

}