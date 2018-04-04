<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/5/5
 * Time: 15:00
 */

namespace Home\Controller;

/**
 * Class HelpController
 * @package Home\Controller
 */
class HelpController extends CommonController {


    /**
     * 除友情链接外,所有链接
     */
    public function otherLinks() {
        $otherLinks =  ternary($_GET['tab'],1);
        $this->assign('otherLinks', $otherLinks);
        $this->_getWebTitle(array('title'=>'其他'));
        $this->display();
    }

    //友情链接
    public function link() {
        $this->_getWebTitle(array('title'=>'友情链接'));
        $this->display();
    }

    //新闻链接
    public function news() {
        $News        = M('news');
        $map['type'] = 1;
        $id = I('get.id', 0, 'intval');
        if (!$id) $this->error("请求错误");
        $up_where = array('type'=>1,'id'=>array('lt',$id));
        $next_where = array('type'=>1,'id'=>array('gt',$id));
        $new = $News->find($id);
        $proNews = $News->field('id,title')->where($up_where)->limit(1)->order('id desc')->find();
        $nextNews= $News->field('id,title')->where($next_where)->limit(1)->order('id')->find();
        if (!$new) $this->error('新闻不存在');
        $this->assign('new', $new);
        $username = M('user')->where(array('id' => $new['user_id']))->getField('username');
        $this->assign('username', $username);
        $proNews  = $proNews ? $proNews : array();
        $nextNews = $nextNews ? $nextNews : array();
        $this->assign('uper', $proNews);
        $this->assign('downer', $nextNews);
        $this->_getWebTitle(array('title'=>'新闻'));
        $this->display();
    }
}