<?php
/**
 * Created by PhpStorm.
 * User: daipingshan   <491906399@Qq.com>
 * Date: 2015/3/27
 * Time: 14:16
 */

namespace Manage\Controller;
use Manage\Controller\CommonController;
/**
 * 新闻控制器
 * Class NewsController
 * @package Manage\Controller
 */
class NewsController extends CommonController {
    /**
     * 获取新闻列表
     */
    public function index(){
        $Model=D('News');
        //获取内部新闻
        $where['type']=0;
        $count=$Model->getTotal($where);
        if($count === false){
            //TODO 错误日志
            $this->_writeDBErrorLog($count, $Model);
        }
        $page  = $this->pages($count, $this->reqnum);
        $limit = $page->firstRow . ',' . $page->listRows;
        $this ->assign('pages', $page->show());
        $this ->assign('count', $count);
        $field='id,title,begin_time';
        $data=$Model->getList($where,'begin_time DESC',$limit,$field);
        if($data===false){
            //TODO 记录日志
            $this->_writeDBErrorLog($data, $Model);
        }
        $this->assign('data',$data);
        $this->display();
    }

    /**
     *  发布新闻
     */
    public function addNews(){
        //TODO 管理员后台完善
    }

    /**
     *  删除新闻
     */
    public function delNews(){
        //TODO 管理员后台完善
    }
}