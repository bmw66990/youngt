<?php
/**
 * Created by PhpStorm.
 * User: daipingshan
 * Date: 2016/4/22
 * Time: 9:56
 */

namespace Common\Org;


class WxPacket{
    private $app_id = 'wx71ef1edff818d209'; //公众账号appid，首先申请与之配套的公众账号
    private $app_secret = '6ca61e882c27a18dfd063882d80f96bf';//公众号secret，用户获取用户授权token
    private $app_mchid = '1239642502';//商户号id

    /**
     * @param $data
     *
     * @return \SimpleXMLElement[]
     */
    public function pay($data) {
        if(!isset($data['packet_num']) || !$data['packet_num']){
            return array('code'=>-1,'error'=>'缺少packet_num参数');
        }
        if(!isset($data['openid']) || !$data['openid']){
            return array('code'=>-1,'error'=>'缺少openid参数');
        }
        if(!isset($data['packet_money']) || !$data['packet_money']){
            return array('code'=>-1,'error'=>'缺少packet_money参数');
        }
        $total_amount = $data['packet_money'] * 100;
        include_once(__DIR__.'/packet/WxHongBaoHelper.php');

        $wxHongBaoHelper = new \WxHongBaoHelper();
        $wxHongBaoHelper->setParameter("nonce_str", $this->great_rand());//随机字符串，丌长于 32 位
        $wxHongBaoHelper->setParameter("mch_billno", $data['packet_num']);//订单号
        $wxHongBaoHelper->setParameter("mch_id", $this->app_mchid);//商户号
        $wxHongBaoHelper->setParameter("wxappid", $this->app_id);
        $wxHongBaoHelper->setParameter("nick_name", '青团网');//提供方名称
        $wxHongBaoHelper->setParameter("send_name", '青团网');//红包发送者名称
        $wxHongBaoHelper->setParameter("re_openid", $data['openid']);//相对于医脉互通的openid
        $wxHongBaoHelper->setParameter("total_amount", $total_amount);//付款金额，单位分
        $wxHongBaoHelper->setParameter("min_value", $total_amount);//最小红包金额，单位分
        $wxHongBaoHelper->setParameter("max_value", $total_amount);//最大红包金额，单位分
        $wxHongBaoHelper->setParameter("total_num", 1);//红包収放总人数
        $wxHongBaoHelper->setParameter("wishing", '恭喜发财');//红包祝福诧
        $wxHongBaoHelper->setParameter("client_ip", get_client_ip());//调用接口的机器 Ip 地址
        $wxHongBaoHelper->setParameter("act_name", '青团分销活动');//活劢名称
        $wxHongBaoHelper->setParameter("remark", '快来抢！');//备注信息
        $postXml = $wxHongBaoHelper->create_hongbao_xml();
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $responseXml = $wxHongBaoHelper->curl_post_ssl($url, $postXml);
        $responseObj = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return objectToArray($responseObj);
    }

    /**
     * 生成随机数
     *
     */
    public function great_rand(){
        $str = '1234567890abcdefghijklmnopqrstuvwxyz';
        $temp = '';
        for($i=0;$i<30;$i++){
            $j=rand(0,35);
            $temp .= $str[$j];
        }
        return $temp;
    }
}