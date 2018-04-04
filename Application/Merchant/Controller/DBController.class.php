<?php

namespace Merchant\Controller;

use Merchant\Controller\CommonController;

/**
 * 后台首页
 * Class IndexController
 * @package Manage\Controller
 */
class DBController extends CommonController {

    public function index() {

        // 查询条件
        $db = D('Partner')->getDBUser($this->partner_id);
        $data = array(
            'db' => $db, 
        );
        $this->assign($data);
        $this->display('Partner/db_index');
    }

}
