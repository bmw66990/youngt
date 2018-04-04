<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-04
 * Time: 10:43
 */
namespace Manage\Model;

use Common\Model\CommonModel;

class RuleModel extends CommonModel {

    protected $trueTableName = 'auth_rule';

    protected $_validate = array(
        array('name', 'require', '规则唯一标识不能为空', 1),
        array('title', 'require', '规则名称必须填写', 1),
        array('status', array(0, 1), '状态值错误', 1, 'IN')
    );


}