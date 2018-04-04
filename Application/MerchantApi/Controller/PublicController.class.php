<?php

/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/14
 * Time: 8:55
 */

namespace MerchantApi\Controller;

use MerchantApi\Controller\CommonController;

/**
 * 其他接口数据处理
 * Class PublicController
 * @package Api\Controller
 */
class PublicController extends CommonController {
    protected $checkUser = false;
    /**
     * 团单图文详情H5页面
     */
    public function getTeamInfo() {

        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->assign('error', '获取失败!');
            $this->display();
            exit;
        }
        $team_info = M('team')->getFieldById($id, 'id,detail,systemreview,notice');
        $data = array('detail' => $team_info[$id]['detail'], 'systemreview' => $team_info[$id]['systemreview'], 'notice' => $team_info[$id]['notice']);
        $this->assign('data', $data);
        $this->display();
    }
}
