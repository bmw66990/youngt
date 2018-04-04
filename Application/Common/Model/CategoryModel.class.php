<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-19
 * Time: 15:41
 */
namespace Common\Model;

class CategoryModel extends CommonModel {

    /**
     * 自动验证
     * @var array
     */
    protected $_validate = array(
        array('name', 'require', '请输入分类名称',1),
        array('name', '', '分类名称不能重复',0,'unique',1),
        array('ename', 'require', '请输入分类英文名称',1),
        array('sort_order', 'require', '请输入分类排序',1),
        array('letter', 'require', '请输入分类首字母',1),
    );
}