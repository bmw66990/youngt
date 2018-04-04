<?php
/**
 * Created by PhpStorm.
 * User: wzb
 * Date: 2015-03-19
 * Time: 10:57
 */
namespace Common\Model;

class CouponModel extends CommonModel {

    /**
     * 团购券总数
     */
    public function getCount($where) {
        $data = $this->alias('c')->join('partner p on p.id=c.partner_id')->where($where)->count();
        if ($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $data;
    }

    /**
     * 接待详情总数
     * @param $where
     * @return mixed
     */
    public function getReceptionCount($where) {
        $data  = $this->alias('c')->field('c.id')->where($where)->group('c.team_id')->order('null')->select();
        if ($data === FALSE) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        //echo $this->_sql();exit;
        $data = count($data);
        return $data;
    }

    /**
     * 获取接待详情
     * @param $where
     * @param string $limit
     * @return mixed
     */
    public function getReceptionDetail($where, $limit = '') {
        //$field = 'sum(c.team_price - c.ucaii_price) profit,c.team_price,c.ucaii_price,count(c.id) num,t.title';
        $field = 'sum(t.team_price - t.ucaii_price) profit,t.team_price,t.ucaii_price,count(c.id) num,t.product title';
        $data  = $this->alias('c')->force('consume_time')->field($field)->join('LEFT JOIN team t on c.team_id=t.id')->where($where)->group('c.team_id')->order('null')->limit($limit)->select();
        if ($data === FALSE) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
        }
        return $data;
    }

    /**
     * 利润统计(new)
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function profitCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if(!empty($city_id)) {
            $where['city_id'] = $city_id;
        }
        $where['consume_time'][] = array('egt', $stime);
        $where['consume_time'][] = array('lt', $etime);
        $data = $this->field('LEFT(FROM_UNIXTIME(consume_time),'. $len .') ct,sum(team_price - ucaii_price) profit')->where($where)->group('ct')->select();
        if($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach($data as $row) {
                $list[$row['ct']]  = $row['profit'];
            }
            return $list;
        }
    }

    /**
     * 利润统计(old)
     * @param $city_id
     * @param $stime
     * @param $etime
     * @param string $type
     * @return array|bool
     */
    public function oldProfitCount($city_id, $stime, $etime, $type = 'week') {
        $len = getCountTypeLen($type);
        if(!empty($city_id)) {
            $where['t.city_id'] = $city_id;
        }
        $where['c.consume_time'][] = array('egt', $stime);
        $where['c.consume_time'][] = array('lt', $etime);
        $condition = 't.id=c.team_id';
        $data = $this->table('coupon c,team t')->field('LEFT(FROM_UNIXTIME(c.consume_time),'. $len .') ct,sum(t.team_price - t.ucaii_price) profit')->where($condition)->where($where)->group('ct')->select();
        if($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        } else {
            $list = array();
            foreach($data as $row) {
                $list['profit'][$row['ct']]  = $row['profit'];
            }
            return $list;
        }
    }

	/**
	*  	获取券号列表
	*  	@param array  $where : 获取数据信息的条件
	*	@param string $limit : 分页
	*	@param string $field : 需要查询的数据字段
	*  	@return array | bool : 返回数据信息
	*/
    public function getCoupons($where,$limit,$orderby='create_time DESC',$field='*') {
        $data = $this->alias('c')
                ->field($field)
                ->force('PRIMARY')
                ->join('left join team t ON c.team_id = t.id')
                ->where($where)
                ->order($orderby)
                ->limit($limit)
                ->select();
        if($data === false){
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        }
        if($data){
            $userId = array();
            foreach($data as $row) {
                $userId[] = $row['user_id'];
            }
            $userList = M('User')->index('id')->field('id,username,mobile')->where(array('id' => array('IN', $userId)))->select();
            foreach($data as &$row) {
                $row['username']   = ternary($userList[$row['user_id']]['username'], '');
                $row['mobile']     = ternary($userList[$row['user_id']]['mobile'], '');
            }
        }
		return $data;
    }

    /**
	*  	获取券号总数
	*  	@param array  $where : 获取数据信息的条件
	*  	@return int | bool : 返回总记录数
	*/
    public function getCouponCount($where) {
		$count = $this->alias('c')
					->join('left join team t ON c.team_id = t.id')
					->where($where)
					->count('c.id');
		if($count===false){
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
		}
		return $count;
    }

    /**
     * 获取用户团购券列表
     * @param $where
     * @param $sort
     * @param $limit
     * @return mixed
     */
    public function getUserCoupons($where, $sort, $limit) {
        $data = $this->where($where)->group('order_id')->order('NULL')->getField('order_id,expire_time', true);
        if($data===false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        }
        if(empty($data)) {
            return array();
        }
        $map['o.id']     = array('in', array_keys($data));
        $map['o.state']  = 'pay';
        $map['o.rstate'] = 'normal';
        $order = D('Order')->alias('o')
                    ->field('o.id,o.id order_id,o.origin,o.allowrefund,o.price,o.quantity,t.product title,t.image')
                    ->join('LEFT JOIN team t on o.team_id=t.id')
                    ->where($map)
                    ->order($sort)
                    ->limit($limit)
                    ->select();
        if($order === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        }
        foreach($order as &$row) {
            $row['expire_time'] = ternary($data[$row['id']], 0);
            $row['image']       = getImagePath($row['image']);
            $row['origin']      = $row['origin'] > 0 ? rtrim(rtrim($row['origin'], '0'), '.') : '0';
        }
        return $order;
    }

    /**
     * 验证券号是否存在
     * @param $id
     * @param $partner_id
     * @return array|bool
     */
    public function checkIsExist($id, $partner_id) {
        $partner = D('Partner')->info($partner_id, 'fid');
        if(empty($partner['fid']) || $partner['fid'] == $partner_id) {
            $map = array(
                'id'         => $id,
                'partner_id' => $partner_id
            );
        } else {
            $map = array(
                'id'         => $id,
                'partner_id' => array('IN', array($partner_id, $partner['fid']))
            );
        }

        $coupon = $this->where($map)->find();
        if($coupon === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        }

        if(empty($coupon)) {
            return array(
                'error' => '券号不存在',
                'code'  => -1,
            );
        }

//        if($coupon['consume'] == 'Y') {
//            return array(
//                'error' => '券号已消费',
//                'code' => -1,
//            );
//        }

        if($coupon['expire_time'] < strtotime(date('Y-m-d'))) {
            return array(
                'error' => "券号已过期，过期时间为：" . date('Y-m-d', $coupon['expire_time']),
                'code'  => -1,
            );
        }
        return $coupon;
    }

    /**
     * 检查券号是否可消费
     * @param $id
     * @return array|bool
     */
    public function checkIsConsume($id, $partner_id) {
        $partner = D('Partner')->info($partner_id, 'fid');
        if(!empty($partner['fid']) && $partner['fid'] != $partner_id) {
            $partner = array($partner_id, $partner['fid']);
        } else {
            $partner = array($partner_id);
        }
        $map = array(
            'id' => array('IN', $id),
        );
        $data = $this->where($map)->select();
        if($data === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        }

        $str     = '';
        $orderId = array();
        foreach($data as $row) {
            if($row['consume'] == 'Y') {
                $str .= $row['id'] . '已消费 ';
            } else if ($row['expire_time'] < strtotime(date('Y-m-d'))) {
                $str .= $row['id'] . '已过期 ';
            }
            if(!in_array($row['partner_id'], $partner)) {
                $str .= $row['id'] . '不是本店券号 ';
            }
            $orderId[] = $row['order_id'];
        }

        if($str == '') {
            if(count(array_unique($orderId)) != 1) {
                return array(
                    'error' => '券号错误',
                    'code'  => -1
                );
            } else {
                return array(
                    'list'     => $data,
                    'order_id' => $orderId[0]
                );
            }
        }
        return array(
            'error' => $str,
            'code'  => -1
        );
    }

    /**
     * 验证券号更新操作
     * @param $coupon  array('id'=>array(券号id), 'list'=>array(券号列表), 'order_id'=>'订单id')
     * @param $partner_id
     * @return bool
     */
    public function consumeCoupon($coupon, $partner_id, $from = '手机客户端', $isOther = true) {
        $map = array(
            'id' => array('IN', $coupon['id'])
        );
        if(!isset($coupon['list'])) {
            $coupon['list'] = $this->where($map)->select();
        }
        if(!isset($coupon['order_id'])) {
            $coupon['order_id'] = $coupon['list'][0]['order_id'];
        }
        if(count($coupon['id']) > 1) {
            $from .= '-多券';
        } else {
            $from .= '-单券';
        }
        $couponData = array(
            'ip'           => get_client_ip(),
            'consume_time' => time(),
            'partner_id'   => $partner_id,
            'consume'      => 'Y',
            'from'         => $from
        );

        //该订单总团券
        $isConsume = $this->where('order_id=' . $coupon['order_id'])->count();
        $consume   = M('Comment')->where('order_id=' . $coupon['order_id'])->getField('consume');

        $model = M();
        $model->startTrans();
        $res = $this->where($map)->save($couponData);

        if($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            $model->rollback();
            return false;
        }

        $incomeData = array();
        $incomeTime = time();
        foreach($coupon['list'] as $row) {
            $price = $row['ucaii_price'];

            if(!$price || $price<=0){
                $price = M('Team')->where('id=' . $row['team_id'])->getField('ucaii_price');
            }

//            if(!$price || $price<=0){
//                continue;
//            }
//
            $incomeData[] = array(
                'partner_id'  => $partner_id,
                'team_id'     => $row['team_id'],
                'money'       => $price,
                'create_time' => $incomeTime,
                'coupon_id'   => $row['id'],
            );
        }
        $rs = M('PartnerIncome')->addAll($incomeData);
        if($rs === false) {
            $this->errorInfo['info'] = M('PartnerIncome')->getDbError();
            $this->errorInfo['sql']  = M('PartnerIncome')->_sql();
            $model->rollback();
            return false;
        }
        /*if($isConsume == count($coupon['list']) && $consume == 'N') {
            //TODO 考虑加个消费时间,便于未评价订单排序
            $consume = M('comment')->where('order_id='.$coupon['order_id'])->save(array('consume'=>'Y'));
            if($consume === false) {
                $this->errorInfo['info'] = M('comment')->getDbError();
                $this->errorInfo['sql']  = M('comment')->_sql();
                $model->rollback();
                return false;
            }
        }*/

        // 2015-08-17 update 验证直接修改评论表
        if($consume == 'N') {
            $consume = M('comment')->where('order_id='.$coupon['order_id'])->save(array('consume'=>'Y'));
            if($consume === false) {
                $this->errorInfo['info'] = M('comment')->getDbError();
                $this->errorInfo['sql']  = M('comment')->_sql();
                $model->rollback();
                return false;
            }
        }

        $model->commit();

        if($isOther) {
            // 第三方电子凭证验证
            threeValidCoupon($partner_id, $coupon['list'], 'verify');
        }

        $orderInfo     = D('Order')->info($coupon['order_id'], 'id,user_id,team_id,laiyuan,city_id,partner_id,openid');
        $teanInfo      = D('Team')->info($orderInfo['team_id'], 'product,bonus');
        $partnerMobile = M('partner')->where('id=' . $partner_id)->getField('phone');
        $push          = new \Common\Org\PushAppMessage();
        $str           = '您购买的' . $teanInfo['product'] . '于[' . date('Y-m-d') . ']验证' . count($coupon['list']) . '份，剩余' . ($isConsume - count($coupon['list'])) . '份未消费，有问题与商家确认或咨询' . $partnerMobile;
        $plat = 'android';
        if(isset($orderInfo['laiyuan']) && $orderInfo['laiyuan']=='ios'){
            $plat = 'ios';
        }
        $pushData = array(
            'title'   => '券号消费',
            'content' => $str,
            'account' => array($orderInfo['user_id']),
            'custom'  => array(
                'type' => "coupon_consume_success",
                'data' => array(
                    'id'=>$coupon['order_id']
                )
            ),
            'plat' => $plat,
        );
        $res = $push->pushMessageToAccess($pushData);

        // 检测分销,发送返利红包
        $openid = $orderInfo['openid'];
        $money  = $teanInfo['bonus'];
        if ($openid && $money > 0) {
            $data = array(
                'user_id'      => $orderInfo['user_id'],
                'packet_money' => $money,
                'openid'       => $openid
            );
            $res = $this->sendPacket($data);
            if ($res['code'] == 1) {
            	$this->addPacketData($orderInfo,$res['parket_data']);
            } else {
            	\Think\Log::write("发送失败===========================================================\n",'INFO');
            	\Think\Log::write(var_export($res,true),'INFO');
            	\Think\Log::write("发送失败===========================================================\n",'INFO');
            }
        }

        return true;
    }

    /**
     * 券号撤销
     * @param $coupon
     * @param $order_id
     * @return bool
     */
    public function undoCoupon($coupon, $order_id) {
        $isUndo = M('partner_income')->where(array('coupon_id' => array('IN', array_keys($coupon))))->select();
        $is_pay = array();
        foreach($isUndo as $key=>$row) {
            if(isset($row['pay_id']) && $row['pay_id'] > 0) {
                unset($isUndo[$key]);
                $is_pay[$row['coupon_id']] = $row['coupon_id'];
            }
        }
        if(count($is_pay) > 0) {
            $coupon_str = implode(',', $is_pay);
            return array(
                'error' => "[{$coupon_str}]券号已结算，无法撤消",
                'code' => -1
            );
        }
        $comment = M('Comment')->where('order_id=' . $order_id)->find();
        if($comment === false) {
            $this->errorInfo['info'] = M('comment')->getDbError();
            $this->errorInfo['sql']  = M('comment')->_sql();
            return false;
        }
        // 获取券号的partner_id
        $partnerId = M('Team')->where('id=' . $comment['team_id'])->getField('partner_id');
        $model = M();
        $model->startTrans();
        $map = array(
            'order_id' => $order_id,
            'id'       => array('IN', array_keys($coupon))
        );
        $couponData = array(
            'ip'           => '',
            'consume_time' => 0,
            'partner_id'   => $partnerId,
            'consume'      => 'N',
            'from'         => ''
        );

        $res = $this->where($map)->save($couponData);
        if($res === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            $model->rollback();
            return false;
        }

        $where = array(
            'coupon_id' => array('IN', array_keys($coupon))
        );
        $rs = M('PartnerIncome')->where($where)->delete();
        if($rs === false) {
            $this->errorInfo['info'] = M('PartnerIncome')->getDbError();
            $this->errorInfo['sql']  = M('PartnerIncome')->_sql();
            $model->rollback();
            return false;
        }

        // 2015-08-17 修改券号消费,就可以评价
        /*if($comment['consume'] == 'Y') {
            $commentData = array(
                'consume'    => 'N',
                'is_comment' => 'N'
            );
            $result = M('Comment')->where('order_id=' . $order_id)->save($commentData);
            if($result === false) {
                $this->errorInfo['info'] = M('comment')->getDbError();
                $this->errorInfo['sql']  = M('comment')->_sql();
                $model->rollback();
                return false;
            }
            // 如果已评论晒图
            if($comment['is_pic'] == 'Y') {
                M('CommentPic')->where('order_id=' . $order_id)->delete();
            }
        }*/
        $model->commit();
        return true;
    }

    /**
     * 删除券号
     * @param $couponId
     * @param $coupon
     * @param $order_id
     * @return bool
     */
    public function delCoupon($couponId, $coupon, $order_id) {
        $srcCoupon = $this->where('order_id=' . $order_id)->getField('id,consume', true);
        if($srcCoupon && (count($srcCoupon) == count($coupon))) {
//            return array(
//                'error' => '订单券号不能清空',
//                'code'  => -1
//            );
        }
        $cha = array_diff(array_keys($srcCoupon), $couponId);
        $isComment = 'Y';
        if($cha) {
            foreach($cha as $row) {
                if($srcCoupon[$row] == 'N') {
                    $isComment = 'N';
                    break;
                }
            }
        }
        $model = M();
        $model->startTrans();
        // 将券号出入到coupon_delete表中,删除coupon表中数据,判断订单是否可评论
        foreach ($coupon as &$v) {
            $v['operation_type'] = 'delete';
            $v                   = array_filter($v);
        }
        unset($v);
        $res = M('CouponDelete')->addAll($coupon);
        if (!$res) {
            $model->rollback();
            return array('error' => '券号删除失败！');
        }
        $where = array(
            'id'       => array('IN', $couponId),
            'order_id' => $order_id
        );
        $res = $this->where($where)->delete();
        if (!$res) {
            $model->rollback();
            return array('error' => '券号删除失败！');
        }
        $model->commit();
        $data = array(
            'consume' => $isComment
        );
        M('Comment')->where('order_id=' . $order_id)->save($data);
		//第三方作废电子凭证
        threeValidCoupon($coupon[0]['partner_id'], $coupon, 'invalid');
        return true;
    }


    /**
     * 获取券号信息
     * @param $coupon
     * @param string $isUse
     * @return bool
     */
    public function getCouponDetail($coupon, $isUse = 'N') {
        if(is_numeric($coupon)) {
            $coupon = $this->info($coupon);
        }
        $team                = M('team')->find($coupon['team_id']);
        $data['title']       = ternary($team['product'], '');
        $data['price']       = ternary($team['market_price'], 0);
        $data['expire_time'] = $coupon['expire_time'];
        $partner             = M('Partner')->find($coupon['partner_id']);
        $data['partner']     = ternary($partner['title'], '');
        $data['mobile']      = ternary($partner['mobile'], '');

        $map = array(
            'order_id' => $coupon['order_id'],
            'consume'  => $isUse
        );
        //$list = $this->field('id,consume,expire_time')->where($map)->select();
        $list = $this->where($map)->getField('id', true);
        if($list === false) {
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        }
        $data['list'] = implode(',', $list);
        return $data;
    }

    /**
     * @param $where            条件
     * @param $limit            分页
     * @param string $order     排序
     * @param string $field     字段
     * @param bool $user        是否需要转换用户名
     * @return bool
     */
    public function dayPayDetail($where,$limit,$order="id desc",$field="*",$user=false){
        if($user===false){
            $data=$this->field($field)->where($where)->order($order)->limit($limit)->select();
        }else{
            $data=$this->alias('c')->join('left join user u ON c.user_id=u.id')->field($field)->where($where)->order($order)->limit($limit)->select();
        }
        if($data===false){
            $this->errorInfo['info'] = $this->getDbError();
            $this->errorInfo['sql']  = $this->_sql();
            return false;
        }
        return $data;
    }

    /**
     * 检测coupon
     * @param $id
     * @param string $act
     */
    public function checkCoupon($id,$act='check'){
        $info = $this->info($id);
        if($info){
            $team_info = M('team')->field('product,team_price')->find($info['team_id']);
            if($info['consume'] == 'Y'){
                return array('status'=>-1,'data'=>'该券号已消费','info'=>array('id'=>$id,'product'=>$team_info['product'],'team_price'=>$team_info['team_price'],'consume_time'=>$info['consume_time']));
            } else if($info['expire_time'] < strtotime(date('Y-m-d'))){
                return array('status'=>-1,'data'=>'该券号已过期','info'=>array('id'=>$id,'product'=>$team_info['product'],'team_price'=>$team_info['team_price'],'expire_time'=>$info['expire_time']));
            } else {
                if($act == 'check' ){
                    return array('status'=>1,'data'=>'该券号未消费','info'=>array('id'=>$id,'product'=>$team_info['product'],'team_price'=>$team_info['team_price'],'expire_time'=>$info['expire_time']));
                } else if($act == 'consume'){
                    $data = array(
                        'id'       => array($id),
                        'list'     => array($info),
                        'order_id' => $info['order_id']
                    );
                    $result = $this->consumeCoupon($data,$info['partner_id'], 'PC端');
                    if($result === false ){
                        return array('status'=>-1,'data'=>'验证失败','info'=>array('id'=>$id,'product'=>$team_info['product'],'team_price'=>$team_info['team_price'],'expire_time'=>$info['expire_time']));
                    }else{
                        $info = $this->info($id);
                        return array('status'=>1,'data'=>'验证成功','info'=>array('id'=>$id,'product'=>$team_info['product'],'team_price'=>$team_info['team_price'],'consume_time'=>$info['consume_time']));
                    }
                }
            }
        }else{
            $refund_coupon_count = M('coupon_delete')->where(array('id'=>$id,'operation_type'=>'refund'))->count();
            if($refund_coupon_count && intval($refund_coupon_count)>0){
                 return array('status'=>-1,'data'=>'该团券号已经退款');
            }
            return array('status'=>-1,'data'=>'该券号不是青团券号');
        }
    }

    /**
     * 获取订单对应全部券号,包括退款券号
     * @param $where
     * @param $order
     * @param $limit
     * @param $field
     * @return mixed
     */
    public function getAllList($where, $order, $limit, $field) {
        $sql = $this->field($field . ",'' as operation_type")
            ->union(array('field' => $field . ',operation_type','table'=>'coupon_delete', 'where'=> $where))
            ->where($where)
            ->select(false);

        $sql .= ' ORDER BY `id` DESC LIMIT ' . $limit;
        $data = M()->query($sql);
        if(!$data) {
            $this->errorInfo['sql'] = $this->_sql();
            $this->errorInfo['info'] = $this->getDbError();
        }
        return $data;
    }

    /**
     * 2016-04-22 daipingshan
     * 微信分销发送红包
     * 当用户消费券号时处理
     * $data['user_id'] 用户id
     * $data['packet_money'] 红包金额
     * $data['openid'] 接收红包的openid
     */
    public function sendPacket($data){
        /*$data   = array(
            'packet_num'   => date('Y_m_d').'_'.mt_rand(10000, 99999),
            'packet_money' => 1,
            'openid'       => 'o1NeDjjOV0zX2JsFn19ixjvHArp8',
        );*/
        if(!isset($data['user_id']) || !$data['user_id']){
            return array('code'=>-1,'error'=>'缺少user_id参数');
        }
        $data['packet_num'] = date('Y_m_d').'_'.$data['user_id'].mt_rand(10000,99999);
        $Packet = new \Common\Org\WxPacket();
        $res    = $Packet->pay($data);
        if(isset($res['error']) && $res['error']){
            return $res;
        }
        if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
            return array('code'=>1,'parket_data'=>$res);
        }else{
            return array('code'=>-1,'error'=>$res['return_msg']);
        }
    }

    /**
     * 2016-04-22 daipingshan
     * 添加红包记录
     * $packet_data 调用发红包接口的返回值
     */
    public function addPacketData($order,$packet_data){
        $Model = M('wx_share_packet');
        $data = array(
            'openid'      =>$packet_data['re_openid'],
            'packet_num'  =>$packet_data['mch_billno'],
            'send_listid' =>$packet_data['send_listid'],
            'add_time'    =>$packet_data['send_time'],
            'packet_money'=>$packet_data['total_amount']/100,
            'user_id'   =>$order['user_id'],
            'team_id'   =>$order['team_id'],
            'partner_id'=>$order['partner_id'],
            'city_id'   =>$order['city_id'],
            'order_id'  =>$order['id']

        );
        $res =  $Model->add($data);
        // \Think\Log::write(var_export($res,true),'INFO');
        // \Think\Log::write(var_export($Model->getLastSql(),true),'INFO');
        return $res;
    }
}