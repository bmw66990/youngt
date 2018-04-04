<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-05-07
 * Time: 10:33
 */
namespace Common\Model;

class CartModel extends CommonModel {

    /**
     * 团单最大数量
     * @var int
     */
    protected static $_maxNum = 50;

    /**
     * 购物车最大数量
     * @var int
     */
    protected static $_maxLen = 10;

    /**
     * 购物车
     * @param $user_id
     * @param $team_id
     * @param $num
     * @param $is_cover
     * @return bool|mixed
     */
    public function createCart($user_id, $team_id, $num, $is_cover = true) {
        if($this->checkIsExist($user_id, $team_id)) {
            return $this->_updateCart($user_id, $team_id, $num, $is_cover);
        } else {
            return $this->_addCart($user_id, $team_id, $num);
        }
    }

    /**
     * 将cookie存储购物车更新到数据库
     * @param $user_id
     * @param $data
     * @return mixed
     */
    public function updateCartWithCookie($user_id, $data) {
        if(empty($data)) return array();
        $map = array(
            'user_id' => $user_id
        );
        $ownList = $this->where($map)->getField('team_id,num', true);
        $teamIds = array_keys($ownList);
        $newData = array();
        foreach($data as $row) {
            if(in_array($row['team_id'], $teamIds)) {
                $where = array(
                    'user_id' => $user_id,
                    'team_id' => $row['team_id']
                );
                $curCart = $this->where($where)->getField('num');
                if(($curCart + $row['num']) > self::$_maxNum) {
                    $num = self::$_maxNum;
                } else {
                    $num = $curCart + $row['num'];
                }
                $saveData = array(
                    'num'   => $num,
                    'state' => 'Y'
                );
                $this->where($where)->save($saveData);
            } else {
                $row['user_id']     = $user_id;
                $row['create_time'] = time();
                $newData[]          = $row;
            }
        }
        if(!empty($newData)) {
            $total = $this->getUserTotal($user_id);
            if ($total < self::$_maxLen) {
                // 控制购物车最大数量
                $newData = array_slice($newData, 0, (self::$_maxLen - $total));
                $res     = $this->addAll($newData);
                if ($res === false) {
                    $this->errorInfo['info'] = $this->getDbError();
                    $this->errorInfo['sql']  = $this->_sql();
                }
                return $res;
            }
        }
        return true;
    }

    /**
     * 检查购物车中是否存在
     * @param $user_id
     * @param $team_id
     * @return bool
     */
    public function checkIsExist($user_id, $team_id) {
        $map = array(
            'user_id' => $user_id,
            'team_id' => $team_id
        );

        if($this->getTotal($map) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加到购物车
     * @param $user_id
     * @param $team_id
     * @param $num
     * @return mixed
     */
    protected function _addCart($user_id, $team_id, $num) {
        if($this->getUserTotal($user_id) > self::$_maxLen) {
            return array(
                'error' => '购物车最大可以添加' . self::$_maxLen,
                'code'  => -1
            );
        }
        $data = array(
            'user_id'     => $user_id,
            'team_id'     => $team_id,
            'num'         => $num,
            'create_time' => time()
        );
        $res = $this->add($data);
        if($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $res;
    }

    /**
     * 更新购物车
     * @param $user_id
     * @param $team_id
     * @param $num
     * @return bool
     */
    protected function _updateCart($user_id, $team_id, $num, $is_cover) {
        $map = array(
            'user_id' => $user_id,
            'team_id' => $team_id
        );
        $info = $this->where($map)->find();
        if(!$is_cover) {
            $num += $info['num'];
        }

        if($num > self::$_maxNum) {
            return array(
                'error' => '团单数量不能超过' . self::$_maxNum,
                'code'  => -1
            );
        }
        $data = array(
            'num'   => $num,
            'state' => 'Y'
        );
        $res = $this->where($map)->save($data);
        if($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $res;
    }

    /**
     * 从购物车中删除
     * @param $user_id
     * @param $team_id
     * @return bool
     */
    public function delCart($user_id, $team_id) {
        if(is_numeric($team_id)) {
            $team_id = array($team_id);
        }
        $map = array(
            'user_id' => $user_id,
            'team_id' => array('IN', $team_id)
        );
        $res = $this->where($map)->delete();
        if($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $res;
    }

    /**
     * 清空购物车
     * @param $user_id
     * @return mixed
     */
    public function cleanCart($user_id) {
        $map['user_id'] = $user_id;
        $count = $this->getTotal($map);
        if(empty($count)) {
            return array(
                'error' => '购物车已清空，请浏览其他团单',
                'code'  => -1
            );
        }
        $res = $this->where($map)->delete();
        if($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $res;
    }

    /**
     * 获取用户购物车团单数
     * @param $user_id
     * @return mixed
     */
    public function getUserTotal($user_id) {
        $map = array(
            'user_id' => $user_id
        );
        return $this->getTotal($map);
    }

    /**
     * 数据库获取购物车列表
     * @param $user_id
     * @return mixed
     */
    public function getCartList($user_id) {
        $map = array(
            'c.user_id' => $user_id
        );

        $data = $this->alias('c')->field('c.num,c.state,c.team_id,t.product,t.image,t.team_price')->join('team t on c.team_id=t.id')->where($map)->order('c.id DESC')->select();

        if($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $data;
    }

    /**
     * 从cookie中读取购物车列表
     * @param $data
     * @return mixed
     */
    public function getCartListWithCookie($data) {
        if(empty($data)) return array();
        $teamId = array();
        foreach($data as $row) {
            $teamId[] = $row['team_id'];
        }
        $map = array(
            'id' => array('IN', $teamId)
        );

        $team = M('Team')->where($map)->getField('id,image,product,team_price', true);
        foreach($data as &$row) {
            $row = array_merge($row, $team[$row['team_id']]);
        }
        return $data;
    }

    /**
     * 获取用户的购物车
     * @param $user_id
     * @return mixed
     */
    public function getUserCart($user_id) {
        $map = array(
            'user_id' => $user_id
        );
        $data = $this->where($map)->select();
        if($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $data;
    }

    /**
     * 获取选择团单的数量和总价
     * @param $user_id
     * @return bool
     */
    public function getSelectCart($user_id) {
        $map = array(
            'c.user_id' => $user_id,
            'c.state'   => 'Y'
        );
        $data = $this->alias('c')->field('count(*) num,sum(c.num*t.team_price) total')->join('team t on c.team_id=t.id')->where($map)->find();
        if($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        $data['num']   = ternary($data['num'], 0);
        $data['total'] = ternary($data['total'], '0.00');
        return $data;
    }
}