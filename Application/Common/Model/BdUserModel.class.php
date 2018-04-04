<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-18
 * Time: 17:02
 */
namespace Common\Model;
/**
 * BD用户模型
 * class BdUserModel
 *
 * @package Common\Model
 */
class BdUserModel extends CommonModel {

    /**
     * 自动验证
     * @var array
     */
    protected $_validate = array(
        array('db_username', 'require', '登陆名必须填写'),
        array('db_username', '', '登陆帐号已经存在！', 0, 'unique', 1),
        array('db_pwd', 'require', '登陆密码必须填写', 1, '', 1),
        array('db_pwd', 'checkPwd', '密码必须是6~20位数字和字母组成', 2, 'function'),
        array('db_name', 'require', '姓名必须填写'),
        array('db_phone', 'require', '电话必须填写'),
        array('db_phone', 'checkMobile', '电话格式不正确', 0, 'function'),
    );

    /**
     * 自动填充
     * @var array
     */
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
        array('db_pwd', 'encryptPwd', 3, 'function'),
        array('db_pwd', '', 2, 'ignore'),
    );

}