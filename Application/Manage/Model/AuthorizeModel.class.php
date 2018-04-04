<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-04
 * Time: 11:00
 */
namespace Manage\Model;

use Common\Model\CommonModel;

class AuthorizeModel extends CommonModel {

    protected $tableName = 'auth_group_access';
    const  RULE = 'auth_group';


    public function getCount($map) {
        $count = $this->alias('ga')->join(self::RULE . ' g on ga.group_id=g.id')->where($map)->count();
        return $count;
    }

    public function getAuthList($id, $module, $limit) {
        $map = array(
            'ga.group_id'   => $id,
            'g.module' => $module
        );
        $list = $this->alias('ga')->join(self::RULE . ' g on ga.group_id=g.id')->where($map)->limit($limit)->select();
        return $list;
    }
}