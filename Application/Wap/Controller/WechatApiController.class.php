<?php
/**
 * Created by PhpStorm.
 * User: daishan
 * Date: 2015/7/3
 * Time: 9:42
 */

namespace Wap\Controller;

class WechatApiController extends CommonController {

    const TOKEN = 'vcshfy1406970723';

    /**
     * 是否验证用户登录
     * @var bool
     */
    protected $checkUser = false;

    /**
     * 是否验证选择城市
     * @var bool
     */
    protected $checkCity = false;

    /**
     * 是否验证选择城市
     * @var bool
     */
    protected $checkToken = false;

    /**
     * @var string
     */
    protected $postData = '';

    public function __construct() {

        parent:: __construct();
        $this->postData = $GLOBALS["HTTP_RAW_POST_DATA"];
    }

    /**
     * 验证Token
     */
    protected function _checkToken() {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce     = $_GET["nonce"];
        $token     = self::TOKEN;
        $tmpArr    = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        ob_clean();
        if ($tmpStr == $signature) {
            echo $_GET["echostr"];
            exit;
        } else {
            echo 'token验证失败';
            exit;
        }
    }
    public function index() {

        if($this->checkToken === true){

            $this->_checkToken();
        }else{
            file_put_contents('/tmp/weixin.log','0',FILE_APPEND);
            if (!empty($this->postData) && $this->postData) {
                //解析数据
                $postObj = simplexml_load_string($this->postData, 'SimpleXMLElement', LIBXML_NOCDATA);
                //消息类型
                $form_MsgType = $postObj->MsgType;
                switch ($form_MsgType) {
                    case 'event':
                        $this->_getEvent($postObj);
                        break;
                    case 'location':
                        $this->_getLocation($postObj);
                        break;
                    case 'text':
                        $this->_getText($postObj);
                        break;
                    case 'voice':
                        $this->_getVoice($postObj);
                        break;
                }
            }
        }
    }

    /**
     *
     */
    protected function _getEvent($postObj) {
        $WxObj = new \Common\Org\WeiXin();
        //发送消息方ID
        $fromUsername = $postObj->FromUserName;
        //接收消息方ID
        $toUsername = $postObj->ToUserName;
        //获取事件类型
        $form_Event = $postObj->Event;
        //订阅事件
        file_put_contents('/tmp/weixin.log','1',FILE_APPEND);
        if ($form_Event == "subscribe") {
            //关注带有扫描事件
            $event_key = $postObj->EventKey;
            if ($event_key && $event_key != '') {
                $event_key = substr($event_key, 8);
                if ($event_key == "0") {
                    $this->_getTextTpl($fromUsername,$toUsername,time(),'text','欢迎使用青团网微信客服，可以语音噢亲！');
                } elseif ($event_key == "201") {
                    $title = '欢乐七夕节,青团网邀你一起中大奖！';
                    $disc  ='青团网七夕活动详情：方式一 电脑打开青团网网站扫描二维码直接参与活动！方式二 直接发送我要抽奖到微信青团网。单击我去中奖吧！';
                    $picUrl = 'http://ytimg.oss-cn-hangzhou.aliyuncs.com/img/12.jpg';
                    $url = 'http://www.youngt.com/lottery/index/openid/'.$fromUsername;
                    $this->_getNewsTpl($fromUsername,$toUsername,time(),'news',$title,$disc,$picUrl,$url);
                } elseif ($event_key == "202") {
                    $users = M('weixin_sy')->where(array('openid'=>$fromUsername))->find();
                    if ($users) {
                        $content = "你的微信号: {$users['weixinname']} 已经参加过活动，不能重复使用。";
                        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
                    } else {
                        $title  = '双11绑定账号赚钱乐';
                        $disc   = '青团网双11活动详情：电脑打开青团网网站扫描二维码，单击我去领钱吧！';
                        $picUrl = 'http://ytimg.oss-cn-hangzhou.aliyuncs.com/1.jpg';
                        $url    = 'http://www.youngt.com/WxBind/login/openid/' . $fromUsername;
                        $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url);
                    }
                } else {
                    $data = array();
                    $where = array('c.order_id'=>intval($event_key));
                    $couponInfo = M('coupon')->alias('c')->field('c.id,t.title,t.end_time')->join('left join team t ON t.id = c.team_id')->where($where)->select();
                    if($couponInfo){
                        foreach ($couponInfo as $val){
                            if(isset($data['coupon'])){
                                $data['coupon'] = $data['coupon'].','.$val['id'];
                            }else{
                                $data['coupon'] = $val['id'];
                            }
                            $data['end_time'] =  $val['end_time'];
                            $data['title']    =  $val['title'];
                        }
                        $data['order_id'] = $event_key;
                        $data['wxuser'] = $fromUsername;
                        $WxObj->sendBuyCoupon($data);
                    }
                }
            }
            $users = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
            if (!$users) {
                $token     = M('token')->find(1);
                $aboutUser = $WxObj->getWeiXinUserInfo("{$token}|{$fromUsername}");
                if (!$event_key || $event_key == '') {
                    $this->_fristSubscribe($aboutUser, $fromUsername, $toUsername);
                } else {
                    $this->_notBind($event_key, $aboutUser, $fromUsername, $toUsername);
                }
            } else {
                if ($event_key && $event_key != '') {
                    $usersBd = M('user')->find(substr($event_key,8));
                    if ($users['username'] == '') {
                        $up_data = array('username'=>substr($event_key, 8), 'id' => $users['id']);
                        M('weixin_user')->save($up_data);
                        $content = "欢迎关注青团网，您的青团网账号".$usersBd['username']."与微信号绑定成功";
                        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
                    } else {
                        $content = "您的青团网账号" . $usersBd['username'] . "已绑定微信，不能重复绑定";
                        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
                    }
                } else {
                    $title[0]  = '>>欢迎关注青团网！';
                    $disc[0]   = '点击后今日青团网微信版，随时随地现场团购，支持多种支付方式，购买评价还将获取积分哦！';
                    $picUrl[0] = 'http://www.youngt.com/img/weixin.jpg';
                    $url[0]    = 'http://www.youngt.com/wap/';
                    $title[1]  = '>>点击进入青团网！';
                    $disc[1]   = '';
                    $picUrl[1] = 'http://www.youngt.com/img/weixin.jpg';
                    $url[1]    = 'http://m.youngt.com';
                    $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url,2);
                }
            }

        }elseif($form_Event=="LOCATION"){
            $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
            if($wxUser){
                $up_data = array('lng'=>$postObj->Longitude,'lat'=>$postObj->Latitude,'id'=>$wxUser['id']);
                M('weixin_user')->save($up_data);
            }else{
                $data = array(
                    'openid' => $fromUsername,
                    'lng' => $postObj->Longitude,
                    'lat' => $postObj->Latitude
                );
                M('weixin_user')->add($data);
            }
            ob_clean();
            exit;
        }elseif($form_Event == 'CLICK'){
            $keyword = $postObj->EventKey;
            file_put_contents('/tmp/weixin.log',$keyword,FILE_APPEND);
            if($keyword == '进入首页'){
                $title  = '>>点击进入青团网！';
                $disc   = '';
                $picUrl = 'http://www.youngt.com/img/weixin.jpg';
                $url    = 'http://m.youngt.com/login.php?openid='.$fromUsername;
                $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url);
            }elseif($keyword == '订单'){
                $this->_myOrder($fromUsername,$toUsername);
            }elseif($keyword == '余额'){
                $this->_myCredit($fromUsername,$toUsername);
            }elseif($keyword == '客服'){
                $this->_myKefu($fromUsername,$toUsername);
            }elseif($keyword == '青团卷'){
                $this->_myCoupon($fromUsername,$toUsername);
            }elseif($keyword == '解绑'){
                $this->_delBind($fromUsername,$toUsername);
            }elseif($keyword == '小青'){
                $this->_getTextTpl($fromUsername,$toUsername,time(),'transfer_customer_service');
            }elseif($keyword == '账户绑定'){
                $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
                $usersBd = M('user')->find($wxUser['username']);
                if($wxUser && $usersBd){
                    $content = "您的青团网账号{$usersBd['username']}已绑定微信，不能重复绑定";
                    $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
                }else{
                    $title  = '单击进入绑定账号';
                    $disc   = '';
                    $picUrl = 'http://www.youngt.com/img/weixin.jpg';
                    $url    = 'http://yangling.youngt.com/Wap/WechatApi/loginBind/openid/'.$fromUsername;
                    $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url);
                }
            }elseif($keyword == '附近团购'){
                $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
                $url = "http://api.map.baidu.com/ag/coord/convert?from=2&to=4&y={$wxUser['lng']}&x={$wxUser['lat']}";
                if($wxUser){
                    $this->_getAround($url,$fromUsername,$toUsername);
                }
            }else{
                $condition['question']=array('like',"%{$keyword}%");
                $have = M('weixin_zd')->where($condition)->find();
                if($have){
                    $this->_getHave($have,$fromUsername,$toUsername);
                }
            }
        }elseif($form_Event=="SCAN"){
            $username = $postObj->EventKey;
            if($username == 0){
                $this->_getTextTpl($fromUsername,$toUsername,time(),'text','欢迎使用青团网微信客服，可以语音噢亲！');
            }elseif($username==201){
                $title = '欢乐七夕节,青团网邀你一起中大奖！';
                $disc  ='青团网七夕活动详情：方式一 电脑打开青团网网站扫描二维码直接参与活动！方式二 直接发送我要抽奖到微信青团网。单击我去中奖吧！';
                $picUrl = 'http://ytimg.oss-cn-hangzhou.aliyuncs.com/img/12.jpg';
                $url = 'http://www.youngt.com/lottery/index/openid/'.$fromUsername;
                $this->_getNewsTpl($fromUsername,$toUsername,time(),'news',$title,$disc,$picUrl,$url);
            }elseif($username==202){
                $users = M('weixin_sy')->where(array('openid'=>$fromUsername))->find();
                if ($users) {
                    $content = "你的微信号: {$users['weixinname']} 已经参加过活动，不能重复使用。";
                    $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
                } else {
                    $title  = '双11绑定账号赚钱乐';
                    $disc   = '青团网双11活动详情：电脑打开青团网网站扫描二维码，单击我去领钱吧！';
                    $picUrl = 'http://ytimg.oss-cn-hangzhou.aliyuncs.com/1.jpg';
                    $url    = 'http://www.youngt.com/WxBind/login/openid/' . $fromUsername;
                    $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url);
                }
            }else{
                $data = array();
                $where = array('c.order_id'=>intval($username));
                $couponInfo = M('coupon')->alias('c')->field('c.id,t.title,t.end_time')->join('left join team t ON t.id = c.team_id')->where($where)->select();
                if($couponInfo){
                    foreach ($couponInfo as $val){
                        if(isset($data['coupon'])){
                            $data['coupon'] = $data['coupon'].','.$val['id'];
                        }else{
                            $data['coupon'] = $val['id'];
                        }
                        $data['end_time'] =  $val['end_time'];
                        $data['title']    =  $val['title'];
                    }
                    $data['order_id'] = $username;
                    $data['wxuser'] = $fromUsername;
                    $WxObj->sendBuyCoupon($data);
                }
            }
        }
    }


    /**
     * @param $postObj
     */
    protected function _getLocation($postObj){
        //发送消息方ID
        $fromUsername = $postObj->FromUserName;
        //接收消息方ID
        $toUsername = $postObj->ToUserName;
        //获取地理消息信息，经纬度，地图缩放比例，地址
        $from_Location_X=$postObj->Location_X;
        $from_Location_Y=$postObj->Location_Y;
        //地址解析使用百度地图API的链接
        $url = "http://api.map.baidu.com/ag/coord/convert?from=2&to=4&y={$from_Location_Y}&x={$from_Location_X}";
        $this->_getAround($url,$fromUsername,$toUsername);
    }

    /**
     * @param $postObj
     */
    protected function _getText($postObj){
        //发送消息方ID
        $fromUsername = $postObj->FromUserName;
        //接收消息方ID
        $toUsername = $postObj->ToUserName;
        $content = $postObj->Content;
        if(strstr($content,"附近")){
            $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
            $url = "http://api.map.baidu.com/ag/coord/convert?from=2&to=4&y={$wxUser['lng']}&x={$wxUser['lat']}";
            $this->_getAround($url,$fromUsername,$toUsername);
        }else if(strstr($content,"签到")){
            $this->_daySign($fromUsername,$toUsername);
        }else if(strstr($content,"实习")) {
            file_put_contents(HTML_PATH.'/signUp.txt',$content,FILE_APPEND);
            $this->_getTextTpl($fromUsername,$toUsername,time(),'text','感谢你的加入！我们会尽快电话回访,如有疑问电话咨询：02968965581');
        }elseif(strstr($content,"查看报名")){
            $signUp=file_get_contents(HTML_PATH.'/signUp.txt');
            $signUp = 'A机报名'.$signUp;
            $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$signUp);
        }else{
            $this->_getTextTpl($fromUsername,$toUsername,time(),'transfer_customer_service');
        }
    }

    /**
     * @param $postObj
     */
    protected function _getVoice($postObj){
        //发送消息方ID
        $fromUsername = $postObj->FromUserName;
        //接收消息方ID
        $toUsername = $postObj->ToUserName;
        $recognition = $postObj->Recognition;
        if(strstr($recognition,"附近")){
            $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
            $url = "http://api.map.baidu.com/ag/coord/convert?from=2&to=4&y={$wxUser['lng']}&x={$wxUser['lat']}";
            $this->_getAround($url,$fromUsername,$toUsername);
        }else if(strstr($recognition,"签到")){
            $this->_daySign($fromUsername,$toUsername);
        }else{
            $condition['question']=array('like',"%{$recognition}%");
            $have = M('weixin_zd')->where($condition)->find();
            if($have){
                $this->_getHave($have,$fromUsername,$toUsername);
            }else{
                $this->_getTextTpl($fromUsername,$toUsername,time(),'transfer_customer_service');
            }
        }
    }


    /**
     * @param $fromUserName
     * @param $toUsername
     * @param $time
     * @param $MsgType
     * @param $content
     */
    protected function _getTextTpl($fromUserName,$toUsername,$time,$MsgType,$content = ''){
         ob_clean();
         if($content != ''){
             echo "<xml>
                    <ToUserName><![CDATA[$fromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$toUsername]]></FromUserName>
                    <CreateTime>$time</CreateTime>
                    <MsgType><![CDATA[$MsgType]]></MsgType>
                    <Content><![CDATA[$content]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>";
         }else{
             echo "<xml>
                    <ToUserName><![CDATA[$fromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$toUsername]]></FromUserName>
                    <CreateTime>$time</CreateTime>
                    <MsgType><![CDATA[$MsgType]]></MsgType>
                    <FuncFlag>0</FuncFlag>
                    </xml>";
         }
        exit;
    }

    /**
     * @param $fromUserName
     * @param $toUsername
     * @param $time
     * @param $MsgType
     * @param $title
     * @param $description
     * @param $picUrl
     * @param $url
     */
    protected function _getNewsTpl($fromUserName,$toUsername,$time,$MsgType,$title,$description,$picUrl,$url,$num = 1){
        ob_clean();
        if($num == 1){
            echo "<xml>
                    <ToUserName><![CDATA[$fromUserName]]></ToUserName>
                    <FromUserName><![CDATA[$toUsername]]></FromUserName>
                    <CreateTime>$time</CreateTime>
                    <MsgType><![CDATA[$MsgType]]></MsgType>
                    <ArticleCount>1</ArticleCount>
                    <Articles>
                    <item>
                    <Title><![CDATA[$title]]></Title>
                    <Description><![CDATA[$description]]></Description>
                    <PicUrl><![CDATA[$picUrl]]></PicUrl>
                    <Url><![CDATA[$url]]></Url>
                    </item>
                    </Articles>
                    <FuncFlag>1</FuncFlag>
                    </xml> ";
        }else{
            $xml  =  "<xml>
                        <ToUserName><![CDATA[$fromUserName]]></ToUserName>
                        <FromUserName><![CDATA[$toUsername]]></FromUserName>
                        <CreateTime>$time</CreateTime>
                        <MsgType><![CDATA[$MsgType]]></MsgType>
                        <ArticleCount>$num</ArticleCount>
                        <Articles>";
            for ($i = 0; $i<$num; $i++){
                $xml.="<item>
                        <Title><![CDATA[$title[$i]]]></Title>
                        <Description><![CDATA[$description[$i]]]></Description>
                        <PicUrl><![CDATA[$picUrl[$i]]]></PicUrl>
                        <Url><![CDATA[$url[$i]]]></Url>
                        </item>";
            }
            $xml.="</Articles>
                    <FuncFlag>1</FuncFlag>
                    </xml>";
            echo $xml;
        }
        exit;
    }

    /**
     * @param $fromUserName
     * @param $toUsername
     * @param $time
     * @param $MsgType
     * @param $title
     * @param $description
     * @param $musicUrl
     * @param $hqMusicUrl
     */
    protected function _getMusicTpl($fromUserName,$toUsername,$time,$MsgType,$title,$description,$musicUrl,$hqMusicUrl){
        ob_clean();
        echo  "<xml>
                <ToUserName><![CDATA[$fromUserName]]></ToUserName>
                <FromUserName><![CDATA[$toUsername]]></FromUserName>
                <CreateTime>$time</CreateTime>
                <MsgType><![CDATA[$MsgType]]></MsgType>
                <Music>
                <Title><![CDATA[$title]]></Title>
                <Description><![CDATA[$description]]></Description>
                <MusicUrl><![CDATA[$musicUrl]]></MusicUrl>
                <HQMusicUrl><![CDATA[$hqMusicUrl]]></HQMusicUrl>
                </Music>
                <FuncFlag>0</FuncFlag>
                </xml>";
        exit;
    }


    /**
     * @param $aboutUser
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _fristSubscribe($aboutUser, $fromUsername, $toUsername){
        $data = array(
            'openid' => $fromUsername,
            'nickname' => $aboutUser['nickname'],
            'sex' => $aboutUser['sex'],
            'language' => $aboutUser['language'],
            'city' => $aboutUser['city'],
            'province' => $aboutUser['province'],
            'country' => $aboutUser['country'],
            'headimgurl' => $aboutUser['headimgurl'],
            'subscribe_time' => time(),
        );
        M('weixin_user')->add($data);
        $title  = '>>欢迎关注青团网！';
        $disc   = '点击后今日青团网微信版，随时随地现场团购，支持多种支付方式，购买评价还将获取积分哦！';
        $picUrl = 'http://www.youngt.com/img/weixin.jpg';
        $url    = 'http://www.youngt.com/wap/';
        $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url);
    }

    /**
     * @param $event_key
     * @param $aboutUser
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _notBind($event_key, $aboutUser, $fromUsername, $toUsername){
        $data = array(
           	'username' => substr($event_key,8),
			'openid' => $fromUsername,
			'nickname' => $aboutUser['nickname'],
			'sex' => $aboutUser['sex'],
			'language' => $aboutUser['language'],
			'city' => $aboutUser['city'],
			'province' => $aboutUser['province'],
			'country' => $aboutUser['country'],
			'headimgurl' => $aboutUser['headimgurl'],
			'subscribe_time' => time(),
        );
        M('weixin_user')->add($data);
        $usersBd = M('user')->find(substr($event_key,8));
        $content = "欢迎关注青团网，您的青团网账号".$usersBd['username']."与微信号绑定成功";
        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
    }

    /**
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _myOrder($fromUsername, $toUsername){
        $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
        if(!$wxUser['username']){
            $content = '您的微信没有绑定青团账号，不能查看订单';
        }else{
            $orders = M('order')->where(array('user_id'=>$wxUser['username'],'rstate'=>'normal'))->order('id desc')->limit(10)->select();
            if($orders){
                $content ="您的订单";
                for ($i = 0; $i < count($orders); $i++) {
                    $team = M('team')->find($orders[$i]['team_id']);
                    if ($orders[$i]['state'] == 'pay') {
                        $content .= "\r\n---------------------------------
							\r\n项目：" . $team['product'] .
                            "\r\n数量：" . $orders[$i]['quantity'] .
                            "\r\n金额：" . $orders[$i]['origin'] .
                            "\r\n状态：已付款";
                    } else {
                        $content .= "\r\n---------------------------------
							\r\n项目：" . $team['product'] .
                            "\r\n数量：" . $orders[$i]['quantity'] .
                            "\r\n金额：" . $orders[$i]['origin'] .
                            "\r\n状态：<a href=\"http://m.youngt.com/pay.php?id=" . $orders[$i]['id'] . "\">去付款</a>";
                    }
                }
            }else{
                $content = '您到现在为止还没有订单';
            }
        }
        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
    }

    /**
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _myCredit($fromUsername, $toUsername){
        $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
        if(!$wxUser['username']){
            $content = '您的微信没有绑定青团账号，不能查看余额';
        }else{
            $user = M('user')->find($wxUser['username']);
            $content = "您的余额为{$user['money']}元";
        }
        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
    }
    /**
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _myKefu($fromUsername, $toUsername){
        $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
        if(!$wxUser['username']){
            $url    = 'https://eco-api.meiqia.com/dist/standalone.html?eid=4404';
        }else{
            $user = M('user')->find($wxUser['username']);
            $url    = 'https://eco-api.meiqia.com/dist/standalone.html?eid=4404&metadata={"name":'.$user['username'].',"tel":'.$user['mobile'].'}';
        }
        $title  = '欢迎使用青团网客服系统';
        $disc   = '请单击下面图标进入客服系统！';
        $picUrl = 'http://ytimg.oss-cn-hangzhou.aliyuncs.com/1.jpg';
        $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url);
    }

    /**
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _myCoupon($fromUsername, $toUsername){
        $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
        if(!$wxUser['username']){
            $content = '您的微信号还没有绑定，请绑定!';
        }else{
            $coupon = M('coupon')->where(array('user_id'=>$wxUser['username']))->order('create_time desc')->limit(10)->select();
            if($coupon){
                $content ="您的青团券";
                for ($i = 0; $i < count($coupon); $i++) {
                    $team = M('team')->find($coupon[$i]['team_id']);
                    if($coupon[$i]['consume']=='Y'){
                        $content.="\r\n---------------------------------
							\r\n项目：".$team['product'].
                            "\r\n券号：".$coupon[$i]['id'].
                            "\r\n状态：已消费";
                    }else{
                        $content.="\r\n---------------------------------
							\r\n项目：".$team['product'].
                            "\r\n券号：".$coupon[$i]['id'].
                            "\r\n状态：未消费";
                    }
                }
            }else{
                $content = '您还没有青团券';
            }
        }
        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
    }

    /**
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _delBind($fromUsername, $toUsername){
        $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
        if(!$wxUser['username']){
            $content = '您的微信号还没有绑定，请绑定后再操作！';
        }else {
            $up_data = array('id'=>$wxUser['id'],'username'=>'');
            M('weixin_user')->save($up_data);
            $content = '解绑成功！';
        }
        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
    }

    /**
     * @param $url
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _getAround($url,$fromUsername, $toUsername){
        $Model        = D('Team');
        $contents     = file_get_contents($url);
        $jsonObj      = json_decode($contents);
        $lat          = base64_decode($jsonObj->x);
        $lng          = base64_decode($jsonObj->y);
        $range        = $Model->getMysqlDistanceField($lat, $lng, 'p');
        $squares      = $Model->returnSquarePoint($lng, $lat, 10);
        $field        = 't.id,t.product as title,t.image,' . $range . ' as `range`';
        $team_where   = $this->_getTeamWhere('', 't');
        $team_where[] = array(
            "p.lat<>0",
            "p.lat>'" . $squares['right-bottom']['lat'] . "'",
            "p.lat<'" . $squares['left-top']['lat'] . "'",
            "p.`long`>'" . $squares['left-top']['lng'] . "'",
            "p.`long`<'" . $squares['right-bottom']['lng'] . "'"
        );
        $data         = $Model->getAroundList($team_where, '`range` asc ,t.sort_order desc', 10, $field);
        if($data){
            foreach($data as $key=>$val){
                $title[$key] = $val['title'].'约'.round($val['range']).'米';
                $disc [$key] = $lat.$lng;
                if($val['image']){
                    $picUrl[$key] = getImagePath($val['image']);
                }else{
                    $picUrl[$key] = '';
                }
                $url[$key] = "http://m.youngt.com/team.php?id=".$val['id'];
            }
            $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url,count($data));
        }else{
            $content = '10公里范围内没有团购项目';
            $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
        }
    }

    /**
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _daySign($fromUsername, $toUsername){
        $wxUser = M('weixin_user')->where(array('openid'=>$fromUsername))->find();
        if(!$wxUser['username']){
            $content = '您的微信还未绑定青团账号，不能使用微信签到';
        }else {
            $Model = D('User');
            $user =  $Model->find($wxUser['username']);
            $daySign = M('daysign');
            $daytime = strtotime(date('Y-m-d'));
            $condition['user_id'] = $user['id'];
            $condition['create_time'] = $daytime;
            $count = $daySign->where($condition)->count('id');
            if ($count) {
                $content = '您今天已经签过到了，请明天再来吧!';
            } else {
                $res = $Model->daySign($user['id']);
                if ($res) {
                    $content = '签到成功';
                } else {
                    $content = '签到失败!';
                }
            }
        }
        $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$content);
    }

    /**
     * @param $have
     * @param $fromUsername
     * @param $toUsername
     */
    protected function _getHave($have,$fromUsername,$toUsername){
        if(!$have['img']){
            $this->_getTextTpl($fromUsername,$toUsername,time(),'text',$have['answer']);
        }else{
            $title  = $have['answer'];
            $disc   = '';
            $picUrl = getSmallImage($have['img']);
            $url    = getSmallImage($have['img']);
            $this->_getNewsTpl($fromUsername, $toUsername, time(), 'news', $title, $disc, $picUrl, $url);
        }
    }

    /**
     * 微信绑定
     */
    public function loginBind(){
        $openid = I('get.openid','','trim');
        $this->assign('openid',$openid);
        $this->display();
    }

    /**
     * 绑定验证
     */
    public function doLoginBind(){
        if(IS_AJAX){
            $ajaxData = $this->_checkLogin();
            if(isset($ajaxData['error'])){
                $data = $ajaxData;
            }else{
                $openid = I('post.openid','','trim');
                if($openid){
                    $count = M('weixin_user')->where(array('username'=>$ajaxData['id']))->count('id');
                    if($count > 0){
                        $data = getPromptMessage('该账号已绑定其他账号');
                    }else{
                        $up_data = array('username'=>$ajaxData['id']);
                        $is_openid = M('weixin_user')->where(array('openid'=>$openid))->count('id');
                        if($is_openid){
                            $res = M('weixin_user')->where(array('openid'=>$openid))->save($up_data);
                            if($res){
                                $data = getPromptMessage('绑定成功','success','1');
                            }else{
                                $data = getPromptMessage('绑定失败');
                            }
                        }else{
                            $data = getPromptMessage('您的唯一标示不合法');
                        }
                      
                    }
                }else{
                    $data = getPromptMessage('非法操作');
                }
            }
        }else{
            $data = getPromptMessage('非法请求');
        }
        $this->ajaxReturn($data);
    }

    /**
     * 账号验证
     */
    protected function _checkLogin(){
        $account  = I('post.account','','trim');
        $password = I('post.password','','trim');
        if(!$account)
            return getPromptMessage('请输入用户名');
        if(!$password)
            return getPromptMessage('请输入密码');
        $where = array('username|mobile|email'=>$account);
        $have_user = M('user')->where($where)->find();
        if(!$have_user)
            return getPromptMessage('账号信息不存在');
        if($have_user['password'] != encryptPwd($password))
            return getPromptMessage('密码错误');
        return $have_user;
    }
}