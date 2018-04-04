<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/4/21
 * Time: 17:35
 */

namespace Common\Model;
use Common\Model\CommonModel;

/**
 * 收藏model
 * Class CollectModel
 * @package Common\Model
 */
class CollectModel extends CommonModel{
    /**
     * 自动验证
     * @var array
     */
    protected $_validate = array(
        array('user_id', 'require', '请登录后再收藏',1),
        array('team_id', 'require', '请选择项目后再收藏',1),
    );
    /**
     * 自动完成
     */
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
    );

    /**
     * 检测团单是否已经收藏
     * @param $team_id
     * @param $user_id
     * @return bool
     */
    public function checkIsCollect($team_id, $user_id) {
        $map = array(
            'team_id' => $team_id,
            'user_id' => $user_id
        );
        $count = $this->getTotal($map);
        if($count > 0) {
            return true;
        } else {
            return false;
        }
    }

}