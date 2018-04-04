<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-04
 * Time: 10:16
 */
namespace Manage\Model;

use Common\Model\CommonModel;

class GroupModel extends CommonModel {

    protected $trueTableName = 'auth_group';

    protected $_validate = array(
        array('title', 'require', '名称必须填写', 1),
        array('status', array(0, 1), '状态值错误', 1, 'IN')
    );


}