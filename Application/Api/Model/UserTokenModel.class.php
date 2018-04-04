<?php
/**
 * Created by PhpStorm.
 * User: runtoad
 * Date: 15-4-8
 * Time: 下午4:58
 */
namespace Api\Model;
use \Think\Model;
/**
 * Class UserTokenModel
 */
class UserTokenModel extends Model
{
    /**
     * 用户token绑定
     *
     * @param string     $token    用户token
     * @param            $deviceid 用户设备号
     *
     * @return bool|mixed
     */
    public function bind($token,  $deviceid)
    {

        $data = array(
            'token'    => $token,
            'deviceid' => $deviceid,
            'bindtime' => time()
        );
        if ($this->counts($token,  $deviceid) == 0) {
            $result = $this->add($data);
            if ($result === false) {
                $this->errorInfo['info'] = $this->getDbError();
                $this->errorInfo['sql']  = $this->_sql();
            }

            return $result;
        }

        return true;
    }

    /**
     *绑定验证
     * @param $token    用户token
     * @param $deviceid 设备号
     *
     * @return mixed
     */
    public function verify($token,  $deviceid)
    {
        return $this->counts($token,  $deviceid);
    }

    /**
     * 绑定验证（不使用设备号）
     * @param $token 用户token
     * @param $uid   用户id
     *
     * @return mixed
     */
    public function verifyWithoutDevice($token, $uid)
    {
        return $this->counts($token, $uid);
    }

    /**
     * 获取数量
     * @param     $token    用户token
     * @param     $uid      用户id
     * @param int $deviceid 设备号
     *
     * @return mixed
     */
    public function counts($token, $deviceid = 0)
    {

        $mapping = array(
            'token' => $token
        );

        if ($deviceid !== 0) {
            $mapping['deviceid'] = $deviceid;
        }

        $result = $this->where($mapping)->count();

        if ($result === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }

        return $result;
    }

} 