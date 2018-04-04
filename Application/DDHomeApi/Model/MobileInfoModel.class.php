<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/14
 * Time: 8:59
 */

namespace Api\Model;
use \Common\Model\CommonModel;

class MobileInfoModel extends CommonModel{
    protected $_validate = array(
        array('platfrom', 'require', '请配置手机来源'),
        array('clientver', 'require', '请配置客户端版本号'),
        array('model', 'require', '请设置手机型号'),
        array('imei', 'require', '请设置手机识别号'),
        array('mac', 'require', '请设置设备mac地址'),
        array('source', 'require', '请设置推广渠道'),
    );
}