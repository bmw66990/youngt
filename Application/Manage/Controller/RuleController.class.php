<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-04
 * Time: 10:42
 */
namespace Manage\Controller;

class RuleController extends CommonController {

    public function index() {
        $where = array(
            array('title', '', 'like'),
            array('status', '')
        );

        $model = D('Rule');
        $list = $this->_getList($model, $where);
        $this->assign('list', $list);
        $this->display();
    }
}