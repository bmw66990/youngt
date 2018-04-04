<?php
/**
 * +------------------------------------------------------------------------------
 *错误信息显示类
 * +------------------------------------------------------------------------------
 * @category    ORG
 * @package     ORG
 * @author      runtiad
 * @version     1.0
 * @createtime  2015/4/7
 * +------------------------------------------------------------------------------
 */
namespace Common\Org;

class errorInfo
{
    /**
     * 错误信息
     * 1开头为交互错误，2开头为用户相关错误 3开头团单相关错误
     * @var array
     */
    private static $data
        = array(
            0    => 'ok',
            1    => '用户不存在',
            2    => '签名验证失败',
            3    => 'token验证失败',
            4    => '该手机已激活',
            5    => '目前已是最新版本无需更新',
            1001 => '参数错误',
            1002 => '操作失败',
            1003 => '订单不存在',
            1004 => '评论失败',
            1005 => '数据获取失败',
            1006 => '退款申请失败',
            1007 => '此订单不允许退款',
            1008 => '此订单已申请退款，请耐心等待',
            1009 => '此订单已成功退款',
            1010 => '已消费，无法退款',
            1011 => '提现金额不正确',
            1012 => '提现申请失败',
            1013 => '签到失败',
            1014 => '密码修改失败',
            1015 => '手机绑定失败',
            1016 => '用户名修改失败',
            1017 => '头像修改失败',
            1018 => '今日已签到',
            1019 => '此订单已经评论',
            1020 => '订单未支付，无法评论',
            1032 => '团购券未消费，无法评论',
            2001 => '用户名或密码错误',
            2002 => '此用户名已存在',
            2003 => '密码错误',
            2004 => '两次输入密码不一致',

            1021 =>'登陆方式错误',
            1022 =>'登陆失败',
            1023 =>'非法手机号码',
            1024 =>'注册失败',
            1025 =>'非法验证码行为',
            1026 =>'该手机号已经绑定！不能重复绑定',
            1027 =>'该用户不存在，不能修改密码',
            1028 =>'获取验证码，你获取验证码次数过多',
            1029 =>'验证码获取失败',
            1030 =>'验证码校验失败',
            1031 =>'密码找回失败',
            1033 =>'该手机号已注册，请用验证码登陆',
            1034 =>'请一分钟之后再获取验证码',
            1035 =>'每小时只能获取两次验证码',

            3001 =>'团单详情获取失败',
            3002 =>'商家详情获取失败',
            3003 =>'购买商品的数量必须大于0',
            3004 =>'团单购买失败',
            3005 =>'快捷购买失败',
            3006 =>'购买的该团单不存在',
            3007 =>'非秒杀团单',
            3008 =>'秒杀时间已结束',
            3009 =>'购买数量不合法',
            3010 =>'超过每个账号购买数量',
            3011 =>'秒杀完成，请下次购买',
            3012 =>'商品剩余数量不足',
            3013 =>'当前秒杀用户过多，请重新提交',
            3014 =>'获取支付参数失败',
            3015 =>'非法支付方式',
            3016 =>'余额支付失败',
            3017 =>'抵金券使用失败',

            3018 =>'优惠买单失败',
        );

    /**
     * 返回错误消息
     * @param int $code 错误代码
     *
     * @return string 错误消息
     */
    public static function getErrMsg($code,$proType='')
    {
        if (!isset(self::$data[$code])) {
            return '';
        }
        return self::$data[$code];
    }
}

?>
