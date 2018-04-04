<?php

/**
 * Created by JetBrains PhpStorm.
 * User: zhoujz
 * Date: 15-3-9
 * Time: 下午3:01
 *
 */
function getAccess($k) {
    $_ACCESS_LIST = $_SESSION['_ACCESS_LIST'];
    if (!empty($_SESSION[C('ADMIN_AUTH_KEY')])) {
        return true;
    }

    if (!is_null($_ACCESS_LIST['APP'][strtoupper($k)])) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取ajax返回数据
 * @param $info
 * @param string $type
 * @param int $val
 * @param string $url
 *
 * @return array
 */
function getPromptMessage($info, $type = 'error', $val = -1, $url = '') {
    $type = ternary($type, 'error');
    $val = ternary($val, -1);
    $ajaxData = array('status' => $val, $type => $info);
    if ($url) {
        $ajaxData['url'] = $url;
    }
    return $ajaxData;
}

/**
 * 密码加密方式
 */
function encryptPwd($str) {
    return md5(trim($str) . C('PWD_ENCRYPT_STR'));
}

/**
 * 按统计类型获取 时间长度
 * @param $type
 * @return int
 */
function getCountTypeLen($type) {
    $len =10;
    switch ($type) {
        case 'year':
            $len = 4;
            break;
        case 'month':
            $len = 7;
            break;
        case 'week':
            $len = 10;
            break;
        case 'day':
            $len = 13;
            break;
    }
    return $len;
}

/**
 * 三元运算
 * @param $a
 * @param $b
 * @return mixed
 */
function ternary($a, $b) {
    return isset($a) ? $a : $b;
}

/**
 * 密码验证
 * @param $str
 */
function checkPwd($str) {
    return preg_match('/^[\d\w|.]{6,20}$/', $str) ? true : false;
}

/**
 * 手机号码验证
 * @param $str
 */
function checkMobile($str) {
    return preg_match('/^1[3|4|5|7|8]\d{9}$/', $str) ? true : false;
}

/**
 * +----------------------------------------------------------
 * 加密密码
 * +----------------------------------------------------------
 * @param string $data 待加密字符串
 * +----------------------------------------------------------
 * @return string 返回加密后的字符串
 */
function encrypt($data, $solt) {
    return md5(md5($data) . $solt);
}

/**
 * +----------------------------------------------------------
 * 将一个字符串转换成数组,支持中文
 * +----------------------------------------------------------
 * @param string $string 待转换成数组的字符串
 * +----------------------------------------------------------
 * @return string   转换后的数组
 * +----------------------------------------------------------
 */
function strToArray($string) {
    $strlen = mb_strlen($string);
    while ($strlen) {
        $array[] = mb_substr($string, 0, 1, "utf8");
        $string = mb_substr($string, 1, $strlen, "utf8");
        $strlen = mb_strlen($string);
    }
    return $array;
}

/**
 * +----------------------------------------------------------
 * 生成随机字符串
 * +----------------------------------------------------------
 * @param int $length 要生成的随机字符串长度
 * @param string $type 随机码类型：0,数字+大写字母；1,数字；2,小写字母；3,大写字母；4,特殊字符；-1,数字+大小写字母+特殊字符
 * +----------------------------------------------------------
 * @return string
  +----------------------------------------------------------
 */
function randCode($length = 5, $type = 0) {
    $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
    if ($type == 0) {
        array_pop($arr);
        $string = implode("", $arr);
    } else if ($type == "-1") {
        $string = implode("", $arr);
    } else {
        $string = $arr[$type];
    }
    $count = strlen($string) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $str[$i] = $string[rand(0, $count)];
        $code .= $str[$i];
    }
    return $code;
}

/**
 * +-----------------------------------------------------------------------------------------
 * 删除目录及目录下所有文件或删除指定文件
 * +-----------------------------------------------------------------------------------------
 * @param str $path 待删除目录路径
 * @param int $delDir 是否删除目录,1或true删除目录,0或false则只删除文件保留目录（包含子目录）
 * +-----------------------------------------------------------------------------------------
 * @return bool 返回删除状态
 * +-----------------------------------------------------------------------------------------
 */
function delDirAndFile($path, $delDir = FALSE) {
    $handle = opendir($path);
    if ($handle) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..")
                is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
        }
        closedir($handle);
        if ($delDir) {
            return rmdir($path);
        }
    } else {
        if (file_exists($path)) {
            return unlink($path);
        } else {
            return FALSE;
        }
    }
}

/**
 * +----------------------------------------------------------
 * 将一个字符串部分字符用*替代隐藏
 * +----------------------------------------------------------
 * @param string $string 待转换的字符串
 * @param int $bengin 起始位置,从0开始计数,当$type=4时,表示左侧保留长度
 * @param int $len 需要转换成*的字符个数,当$type=4时,表示右侧保留长度
 * @param int $type 转换类型：0,从左向右隐藏；1,从右向左隐藏；2,从指定字符位置分割前由右向左隐藏；3,从指定字符位置分割后由左向右隐藏；4,保留首末指定字符串
 * @param string $glue 分割符
 * +----------------------------------------------------------
 * @return string   处理后的字符串
 * +----------------------------------------------------------
 */
function hideStr($string, $bengin = 0, $len = 4, $type = 0, $glue = "@") {
    if (empty($string))
        return false;
    $array = array();
    if ($type == 0 || $type == 1 || $type == 4) {
        $strlen = $length = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, "utf8");
            $string = mb_substr($string, 1, $strlen, "utf8");
            $strlen = mb_strlen($string);
        }
    }
    switch ($type) {
        case 1:
            $array = array_reverse($array);
            for ($i = $bengin; $i < ($bengin + $len); $i++) {
                if (isset($array[$i]))
                    $array[$i] = "*";
            }
            $string = implode("", array_reverse($array));
            break;
        case 2:
            $array = explode($glue, $string);
            $array[0] = hideStr($array[0], $bengin, $len, 1);
            $string = implode($glue, $array);
            break;
        case 3:
            $array = explode($glue, $string);
            $array[1] = hideStr($array[1], $bengin, $len, 0);
            $string = implode($glue, $array);
            break;
        case 4:
            $left = $bengin;
            $right = $len;
            $tem = array();
            for ($i = 0; $i < ($length - $right); $i++) {
                if (isset($array[$i]))
                    $tem[] = $i >= $left ? "*" : $array[$i];
            }
            $array = array_chunk(array_reverse($array), $right);
            $array = array_reverse($array[0]);
            for ($i = 0; $i < $right; $i++) {
                $tem[] = $array[$i];
            }
            $string = implode("", $tem);
            break;
        default:
            for ($i = $bengin; $i < ($bengin + $len); $i++) {
                if (isset($array[$i]))
                    $array[$i] = "*";
            }
            $string = implode("", $array);
            break;
    }
    return $string;
}

/**
 * +----------------------------------------------------------
 * 功能：字符串截取指定长度
 * leo.li hengqin2008@qq.com
 * +----------------------------------------------------------
 * @param string $string 待截取的字符串
 * @param int $len 截取的长度
 * @param int $start 从第几个字符开始截取
 * @param boolean $suffix 是否在截取后的字符串后跟上省略号
 * +----------------------------------------------------------
 * @return string               返回截取后的字符串
 * +----------------------------------------------------------
 */
function cutStr($str, $len = 100, $start = 0, $suffix = 1) {
    $str = strip_tags(trim(strip_tags($str)));
    $str = str_replace(array("\n", "\t"), "", $str);
    $strlen = mb_strlen($str);
    while ($strlen) {
        $array[] = mb_substr($str, 0, 1, "utf8");
        $str = mb_substr($str, 1, $strlen, "utf8");
        $strlen = mb_strlen($str);
    }
    $end = $len + $start;
    $str = '';
    for ($i = $start; $i < $end; $i++) {
        $str .= $array[$i];
    }
    return count($array) > $len ? ($suffix == 1 ? $str . "&hellip;" : $str) : $str;
}

/**
 * +----------------------------------------------------------
 * 功能：检测一个目录是否存在,不存在则创建它
 * +----------------------------------------------------------
 * @param string $path 待检测的目录
 * +----------------------------------------------------------
 * @return boolean
  +----------------------------------------------------------
 */
function makeDir($path) {
    return is_dir($path) or ( makeDir(dirname($path)) and @ mkdir($path, 0777));
}

/**
 * +----------------------------------------------------------
 * 功能：检测一个字符串是否是邮件地址格式
 * +----------------------------------------------------------
 * @param string $value 待检测字符串
 * +----------------------------------------------------------
 * @return boolean
  +----------------------------------------------------------
 */
function is_email($value) {
    return preg_match("/^[0-9a-zA-Z]+(?:[\_\.\-][a-z0-9\-]+)*@[a-zA-Z0-9]+(?:[-.][a-zA-Z0-9]+)*\.[a-zA-Z]+$/i", $value);
}

/**
 * +----------------------------------------------------------
 * 功能：系统邮件发送函数
 * +----------------------------------------------------------
 * @param string $to 接收邮件者邮箱
 * @param string $name 接收邮件者名称
 * @param string $subject 邮件主题
 * @param string $body 邮件内容
 * @param string $attachment 附件列表
 * +----------------------------------------------------------
 * @return boolean
  +----------------------------------------------------------
 */
function send_mail($to, $name, $subject = '', $body = '', $attachment = null, $config = '') {
    $config = is_array($config) ? $config : C('SYSTEM_EMAIL');
    import('PHPMailer.phpmailer', VENDOR_PATH);         //从PHPMailer目录导class.phpmailer.php类文件
    $mail = new PHPMailer();                           //PHPMailer对象
    $mail->CharSet = 'UTF-8';                         //设定邮件编码,默认ISO-8859-1,如果发中文此项必须设置,否则乱码
    $mail->IsSMTP();                                   // 设定使用SMTP服务
//    $mail->IsHTML(true);
    $mail->SMTPDebug = 0;                             // 关闭SMTP调试功能 1 = errors and messages2 = messages only
    $mail->SMTPAuth = true;                           // 启用 SMTP 验证功能
    if ($config['smtp_port'] == 465)
        $mail->SMTPSecure = 'ssl';                    // 使用安全协议
    $mail->Host = $config['smtp_host'];                // SMTP 服务器
    $mail->Port = $config['smtp_port'];                // SMTP服务器的端口号
    $mail->Username = $config['smtp_user'];           // SMTP服务器用户名
    $mail->Password = $config['smtp_pass'];           // SMTP服务器密码
    $mail->SetFrom($config['from_email'], $config['from_name']);
    $mail->AddReplyTo($config['reply_email'], $config['reply_name']);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($to, $name);
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            if (is_array($file)) {
                is_file($file['path']) && $mail->AddAttachment($file['path'], $file['name']);
            } else {
                is_file($file) && $mail->AddAttachment($file);
            }
        }
    } else {
        is_file($attachment) && $mail->AddAttachment($attachment);
    }
    return $mail->Send() ? true : $mail->ErrorInfo;
}

/**
 * +----------------------------------------------------------
 * 功能：计算文件大小
 * +----------------------------------------------------------
 * @param int $bytes
  +----------------------------------------------------------
 * @return string 转换后的字符串
 * +----------------------------------------------------------
 */
function byteFormat($bytes) {
    $sizetext = array(" B", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), 2) . $sizetext[$i];
}

function checkCharset($string, $charset = "UTF-8") {
    if ($string == '')
        return;
    $check = preg_match('%^(?:
                                [\x09\x0A\x0D\x20-\x7E] # ASCII
                                | [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
                                | \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
                                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
                                | \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
                                | \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
                                | [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
                                | \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
                                )*$%xs', $string);

    return $charset == "UTF-8" ? ($check == 1 ? $string : iconv('gb2312', 'utf-8', $string)) : ($check == 0 ? $string : iconv('utf-8', 'gb2312', $string));
}

/**
 * 2.     * ajax返回
 * 3.     *@param $data    返回数据
 * 4.     *@param $info  返回信息
 * 5.     *@param $status   返回状态
 */
function ajaxReturnNew($data, $info, $status) {
    $out['data'] = $data;
    $out = array(
        'data' => $data,
        'info' => $info,
        'status' => $status
    );
    header('Content-Type:application/json; charset=utf-8');
    exit(json_encode($out));
}

/**
 * +----------------------------------------------------------
 * 功能：文件上传
 * +----------------------------------------------------------
 */
function uploadFiles($exts = array(), $path = '', $autoSub = true, $maxsize = 0) {
    $upload = new \Think\Upload();                            // 实例化上传类
    $upload->autoSub = $autoSub;
    $upload->rootPath = C('uploadURL');                         //保存根路径
    if ($autoSub) {
        $upload->saveName = array('createFileName', ''); //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
    } else {
        $upload->saveName = array('uniqid', ''); //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
    }
    $upload->maxSize = $maxsize;                // 设置附件上传大小
    $upload->subName = array('date', 'Y-m');
    $upload->exts = $exts;                                // 设置附件上传类型
    $upload->savePath = $path;
    if (!$info = $upload->upload()) {
        // 上传错误提示错误信息
        return array('status' => 0, 'info' => $upload->getError());
    } else {
        // 上传成功 获取上传文件信息
        return array('status' => 1, 'info' => $info);
    }
}

/**
 * +----------------------------------------------------------
 * 功能：上传函数文件
 * +----------------------------------------------------------
 */
function createFileName() {
    $string = new \Org\Util\String();
    return $string->uuid();
}

/**
 * 创建目录
 * @param  string $savepath 要创建的穆里
 * @return boolean          创建状态，true-成功，false-失败
 */
function pmkdir($savepath) {
    $dir = $savepath;
    if (is_dir($dir)) {
        return true;
    }

    if (mkdir($dir, 0777, true)) {
        return true;
    } else {
        //$this->error = "目录 {$savepath} 创建失败！";
        return false;
    }
}

/*
 * 	检测是否为手机访问
 *
 */

function isMobile() {
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
        //找不到为flase,否则为true
        return true;
    }
    //脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
}

/*
 * 	通过 $val 获取用户流水明细
 */

function flow_info($val) {
    if (empty($val))
        return NULL;
    $option = array(
        'buy' => '购买项目',
        'invite' => '邀请',
        'store' => '充值',
        'withdraw' => '用户提现',
        'coupon' => '返利',
        'refund' => '退款',
        'register' => '注册',
        'charge' => '现金充值',
        'store' => '线下充值',
        'paycharge' => '购买充值',
        'daysign' => '每日签到',
        'hongbao' => '红包',
        'cash' => '现金支付',
    );
    return $option[$val];
}

function isNullChange($val) {
    return $val ? $val : 0;
}

/*
 * 	判断用户交易收支
 */

function flow_direction($val) {
    if (empty($val))
        return NULL;
    $option = array(
        'income' => '收入',
        'expense' => '支出',
    );
    return $option[$val];
}

/*
 * 	判断青团券号是否消费
 */

function coupon_state($val) {
    if (empty($val))
        return NULL;
    $option = array(
        'N' => '未消费',
        'Y' => '已消费',
    );
    return $option[$val];
}

/*
 * 	判断订单付款状态
 */

function order_state($val) {
    if (empty($val))
        return NULL;
    $option = array(
        'pay' => '已付款',
        'unpay' => '未付款',
    );
    return $option[$val];
}

/*
 * 	判断订单退款状态
 */

function order_rstate($val) {
    if (empty($val))
        return NULL;
    $option = array(
        'normal' => '正常',
        'berefund' => '已退款',
        'askrefund' => '退款中',
        'norefund' => '不允许退款'
    );
    return $option[$val];
}

/*
 *  获取订单支付来源
 */

function order_service($val, $def = '未知') {
    $payservice = array(
        'alipay' => '支付宝',
        'tenpay' => '财付通',
        'tenwap' => '财付通手机',
        'chinabank' => '全民付手机网页',
        'umspay' => '全民付客户端',
        'credit' => '余额付款',
        'cash' => '线下',
        'yeepay' => '易宝',
        'sdopay' => '盛付通',
        'cmpay' => '手机',
        'paypal' => 'Paypal',
        'aliwap' => '无线支付宝',
        'aliapp' => '支付宝客户端',
        'wapepay' => '翼支付无线',
        'tenapp' => '财付通客户端',
        'wechatpay' => '客户端微信支付',
        'pcalipay' => 'pc支付宝',
        'pctenpay' => 'pc财付通',
        'wxpay' => '客户端微信',
        'unionpay' => '银联',
        'wapunionpay' => 'wap银联',
        'lianlianpay' => '连连支付',
        'wapalipay' => 'wap支付宝',
        'waptenpay' => 'wap财付通',
        'wapumspay' => 'wap全民付',
        'wapwechatpay' => 'wap微信',
        'pcwxpaycode' => 'pc微信扫码支付',
        'wepay'       => '京东支付',
    );
    return ternary($payservice[$val], $def);
}

/*
 *  获取订单支付来源
 */

function order_from($val) {
    $tmp = array(
        'android' => '安卓手机',
        'newandroid' => '安卓手机',
        'ios' => '苹果手机',
        'newios' => '苹果手机',
        'pc' => 'PC电脑',
        'm.youngt.com' => '手机浏览器',
        'mobile.youngt.com' => '手机浏览器',
    );
    return $tmp[$val] ? $tmp[$val] : 'PC电脑';
}

/*
 * 获取评论等级
 */

function comment_state($val) {
    $comment = array(
        '1' => '很不满意',
        '2' => '不满意',
        '3' => '一般',
        '4' => '满意',
        '5' => '很满意',
    );
    return $comment[$val] ? $comment[$val] : '一般';
}

/*
 * 获取积分状态
 */

function credit_state($val) {
    $option_action = array(
        'buy' => '购买商品',
        'login' => '每日登录',
        'pay' => '支付返积',
        'exchange' => '兑换商品',
        'register' => '注册用户',
        'invite' => '邀请好友',
        'refund' => '项目退款',
    );
    return $option_action[$val] ? $option_action[$val] : '其他';
}

/**
 * 获取图片完整路径
 * @param $path
 * @return string
 */
function getSmallImage($path) {
    list($a, $b) = explode('.', $path);
    if (strrpos('static', $a)) {
        $url = C('IMG_PREFIX') . '/' . $a . '_index.' . $b;
    } else {
        $url = C('IMG_PREFIX') . '/static/' . $a . '_index.' . $b;
    }
    return $url;
}

/**
 * 获取图片路径
 */
function getImagePath($path) {

    if (!trim($path)) {
        return '';
    }
    if (strpos($path, 'http') !== false) {
        return $path;
    }

    if (strpos($path, 'static') !== false) {
        return C('IMG_PREFIX') . $path;
    } else {
        return C('IMG_PREFIX') . '/static/' . $path;
    }
}

/**
 * 版本号处理
 * @param $ver
 * @return float
 */
function getFloatVersion($ver) {
    $vers = explode('.', $ver);
    $first = $vers[0];
    unset($vers[0]);
    $ver = $first . '.' . implode('', $vers);
    return floatval($ver);
}

/**
 * 获取订座类型(他人/自己)
 * @param $st
 * @return string
 */
function getDingzuoType($st) {
    switch ($st) {
        case 'Y':
            return '自己';
        case 'N':
            return '他人';
    }
}

/**
 * 过滤短信不合法字符
 * @param $msg
 * @return string
 */
function sms_trim($msg) {
    $msg = str_replace('【', '', $msg);
    $msg = str_replace('】', '', $msg);
    $msg = str_replace('《', '', $msg);
    $msg = str_replace('》', '', $msg);
    $msg = str_replace('<', '', $msg);
    $msg = str_replace('>', '', $msg);
    $msg = str_replace('测试', 'ceshi', $msg);
    return $msg;
}

// 确定是否为移动设备
function is_mobile() {
    if (stristr($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
        return false;
    }
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    //此条摘自TPM智能切换模板引擎，适合TPM开发
    if (isset($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT'])
        return true;
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA']))
    //找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
    //判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
        );
        //从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

function formatCoupon($val) {
    return chunk_split($val, 4, ' ');
}

/* 获取用户中心订单操作菜单 */

function getUserOrderBtn($order) {
    $str = '';
    $state = strtolower($order['status']);
    $id = $order['id'];

    if ($order['express'] == 'Y') {
        switch ($state) {
            case 'unpay':
                $str = '<a class="btn btn-mini" href="' . U('Team/teamBuy', array('orderId' => $id)) . '" style="background-color: #f76120;  border-color: #da3f02;">付款</a>';
                //TODO code
                break;
            case 'applyrefund':
                $str = '<a href="' . U('Member/refunding', array('id' => $id)) . '">退款中</a>';
                //TODO code
                break;
            case 'refund':
                $str = '已退款';
                //TODO code
                break;
            default:
                switch ($order['mail_order_pay_state']) {
                    case 0:
                        break;
                    case 1:
                        $str = '<a class="btn u-btn-c1" href="javascript:;" onclick="makesureOrder(' . $order['id'] . ')">确定收货</a>';
                        $str .= '<a class="coupon-view"  href="' . U('Member/viewTransport', array('id' => $id)) . '">查看物流</a>';
                        break;
                    case 2:
                        $str = '<a class="coupon-view"  href="' . U('Member/viewTransport', array('id' => $id)) . '">查看物流</a>';
                        break;
                }
                if ($order['mail_order_pay_state'] == 2 && $state == 'unreview') {
                    $str .= '<a  href="' . U('Member/review', array('id' => $id)) . '">发表评论</a>';
                }
                if ($order['state'] == 'pay' && $order['rstate'] == 'normal') {
                    $str .= '<a target="_blank" href="' . U('Member/refund', array('id' => $id)) . '">申请退款</a>';
                }
        }
    } else {
        // 获取OTA信息
        $ota = D('Ota');
        switch ($state) {
            case 'unpay':
                $str = '<a class="btn btn-mini" href="/Team/teamBuy/orderId/' . $id . '" style="background-color: #f76120;  border-color: #da3f02;">付款</a>';
                break;
            case 'applyrefund':
                if (!$ota->tmCheck($order['team_id'])) {
                    $str = '<a class="coupon-view"  href="' . U('Member/viewCoupon', array('id' => $id)) . '">查看券号</a>';
                }
                $str .= '<a href="' . U('Member/refunding', array('id' => $id)) . '">退款中</a>';
                break;
            case 'refund':
                $str = '已退款';
                break;
            case 'expired':
            case 'unuse':
                $str = '<a class="coupon-view"  href="' . U('Member/viewCoupon', array('id' => $id)) . '">查看券号</a>';
                if ($order['allowrefund'] == 'Y') {
                    if ($order['rstate'] == 'askrefund') {
                        $str .= '<a href="' . U('Member/refunding', array('id' => $id)) . '">退款中</a>';
                    } else {
                        $str .= '<a target="_blank" href="' . U('Member/refund', array('id' => $id)) . '">申请退款</a>';
                    }
                } else {
                    $str .= '不允许退款';
                }
                break;
            case 'used':
                if ($order['is_comment'] == 'N') {
                    $str .= '<a  href="' . U('Member/review', array('id' => $id)) . '">发表评论</a>';
                }
                break;
            case 'review':
                break;
            case 'unreview':
                if ($ota->tmCheck($order['team_id'])) {
                    $info = $ota->where(array('order_id'=>$order['id']))->find();
                    if (strtotime($info['pro_sdate']) + 86400 < NOW_TIME) {
                        $str .= '已失效';
                    } else {
                        $status = substr($info['status'], 0, 1);
                        if ($status == 'S') {
                            $str .= '<a target="_blank" href="' . U('Member/refund', array('id' => $id)) . '">申请退款</a>';
                        }
                    }
                } else {
                    $str .= '<a  href="' . U('Member/review', array('id' => $id)) . '">发表评论</a>';
                }
                break;
        }
    }
    return $str;
}

/* 获取订单状态 */

function getUserOrderState($order) {
    $str = '';
    if ($order['express'] == 'Y') {
        if ($order['state'] == 'unpay' && $order['rstate'] == 'normal') {
            $str = '未付款';
        } elseif ($order['state'] == 'pay' && $order['rstate'] == 'normal') {
            switch ($order['mail_order_pay_state']) {
                case 0:
                    $str = '待发货';
                    break;
                case 1:
                    $str = '已发货';
                    break;
                case 2:
                    $str = '已收货';
                    break;
            }
        } elseif ($order['rstate'] == 'askrefund') {
            $str = '退款中';
        } elseif ($order['rstate'] == 'berefund') {
            $str = '已退款';
        }
    } else {
        if ($order['state'] == 'pay') {
            if ($order['rstate'] == 'normal') {
                $str = '已付款';
            } else if ($order['rstate'] == 'askrefund') {
                $str = '申请退款';
            }
        } else {
            if ($order['rstate'] == 'normal') {
                $str = '待付款';
            } else if ($order['rstate'] == 'berefund') {
                $str = '已退款';
            }
        }
    }
    return $str;
}

/**
 * 下载xls文件
 * @param type $data
 * @param type $keynames
 * @param type $name
 */
function download_xls($data, $keynames, $name = 'dataxls') {

    $xls[] = "<html><meta http-equiv=content-type content=\"text/html; charset=UTF-8\"><body><table border='1'>";

    $xls[] = "<tr><td align='center'>ID</td><td>" . implode("</td><td align='center'>", array_values($keynames)) . '</td></tr>';
    $index = 0;
    foreach ($data As $o) {

        $line = array(++$index);

        foreach ($keynames AS $k => $v) {

            $line[] = $o[$k];
        }

        $xls[] = '<tr><td align="center">' . implode("</td><td align='center'>", $line) . '</td></tr>';
    }

    $xls[] = '</table></body></html>';

    $xls = join("\r\n", $xls);

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header('Content-Disposition: attachment; filename="' . $name . '.xls"');
    header("Content-Transfer-Encoding: binary");
    die(mb_convert_encoding($xls, 'UTF-8', 'UTF-8'));
}

function partner_down_xls($data, $keynames, $name = 'dataxls') {

    $xls[] = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"><head><head><meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\" /><style id=\"Classeur1_16681_Styles\"></style></head><body><div id=\"Classeur1_16681\" align=center x:publishsource=\"Excel\"><table x:str border=1 cellpadding=0 cellspacing=0 width=100% style=\"border-collapse: collapse\">";

    $xls[] = "<tr><td>" . implode("</td><td>", array_values($keynames)) . '</td></tr>';

    foreach ($data As $o) {
        $line = array();
        foreach ($keynames AS $k => $v) {

            $line[] = $o[$k];
        }

        $xls[] = '<tr class="xl2216681 nowrap"><td>' . implode("</td><td>", $line) . '</td></tr>';
    }

    $xls[] = '<table></div></body></html>';

    $xls = join("\r\n", $xls);
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header('Content-Disposition: attachment; filename="' . $name . '.xls"');
    header("Content-Transfer-Encoding: binary");

    die(mb_convert_encoding($xls, 'UTF-8', 'UTF-8'));
}

/**
 * 生成手机验证码！！
 */
function getMobileCode() {
    return rand(C('SMS_MIN'), C('SMS_MAX'));
}

/* 积分抽奖 */

function getLottery($proArr) {
    //计算概率
    $result = '';
    //概率数组的总概率精度
    $proSum = array_sum($proArr);

    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    unset($proArr);
    return $result;
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * @param $params
 * @param null $key
 * @return bool|string
 */
function param($params, $key = null) {
    if ($key != null) {
        $keys = explode(',', $key);
        foreach ($keys as $var) {
            if (array_key_exists(trim($var), $params)) {
                unset($params[$var]);
            }
        }
    }

    $str = '';
    if (!empty($params)) {
        foreach ($params as $k => $v) {
            $str .= $k . '/' . $v . '/';
        }
    }
    return trim($str);
}

/**
 * 过滤sql
 * @param $str
 * @return mixed|string
 */
function sqlReplace($str) {
    if (!get_magic_quotes_gpc()) {    // 判断magic_quotes_gpc是否为打开
        $str = addslashes($str);    // 进行magic_quotes_gpc没有打开的情况对提交数据的过滤
    }
    $str = str_replace("and", "", $str);
    $str = str_replace("execute", "", $str);
    $str = str_replace("update", "", $str);
    $str = str_replace("count", "", $str);
    $str = str_replace("chr", "", $str);
    $str = str_replace("mid", "", $str);
    $str = str_replace("master", "", $str);
    $str = str_replace("truncate", "", $str);
    $str = str_replace("char", "", $str);
    $str = str_replace("declare", "", $str);
    $str = str_replace("select", "", $str);
    $str = str_replace("create", "", $str);
    $str = str_replace("delete", "", $str);
    $str = str_replace("insert", "", $str);
    $str = str_replace("'", "", $str);
    $str = str_replace(" ", "", $str);
    $str = str_replace("or", "", $str);
    $str = str_replace("=", "", $str);
    $str = str_replace("%20", "", $str);
    return $str;
}

if (!function_exists('chunkCoupon')) {

    // 分隔券号
    function chunkCoupon($code, $len = 4, $delimiter = ' ') {
        if (strlen(strval($code)) > $len) {
            return rtrim(chunk_split(strval($code), $len, $delimiter), $delimiter);
        } else {
            return $code;
        }
    }

}

function admanageType($val) {
    $data = array('pc' => '电脑首页轮播图', 'app' => 'APP广告图片', 'timelimit' => 'APP秒杀图片', 'limited' => 'APP限量图片', 'special_selling' => 'APP特卖图片');
    return $data[$val] ? $data[$val] : '其他';
}

/**
 * 商户端使用
 * @param type $team
 * @return type
 */
// 团单状态获取
function team_state(&$team) {
    if ($team['now_number'] >= $team['min_number']) {
        if ($team['max_number'] > 0) {
            if ($team['now_number'] >= $team['max_number']) {
                if ($team['close_time'] == 0) {
                    $team['close_time'] = $team['end_time'];
                }
                return $team['state'] = 'soldout';
            }
        }
        if ($team['end_time'] <= time()) {
            $team['close_time'] = $team['end_time'];
        }
        return $team['state'] = 'success';
    } else {
        if ($team['end_time'] <= time()) {
            $team['close_time'] = $team['end_time'];
            return $team['state'] = 'failure';
        }
    }
    return $team['state'] = 'none';
}

function state_explain($team, $error = 'false') {

    $state = team_state($team);

    $state = strtolower($state);

    switch ($state) {

        case 'none': return '正在进行中';

        case 'soldout': return '已售光';

        case 'failure': if ($error)
                return '团购失败';

        case 'success': return '团购成功';

        default: return '已结束';
    }
}

//反馈类型
function feedbackType($val) {
    $feedcate = array('suggest' => '意见反馈', 'seller' => '商务反馈', 'sms' => '短信反馈', 'zhaoshang' => '招商加盟');
    return $feedcate[$val] ? $feedcate[$val] : '其他';
}

/**
 * 代金券状态
 * @param $state
 * @param $time
 * @return string
 */
function cardState($state, $time) {
    if ($state == 'Y') {
        return '已使用';
    } else {
        if ($time < time()) {
            return '已过期';
        } else {
            return '未使用';
        }
    }
}

// 客户端下载
function client_download() {
    header("Content-type:text/html; charset=utf-8");
    //$android_url = 'http://jscss.youngt.com/youngt.apk';
    $android_url = 'http://ytfile.oss-cn-hangzhou.aliyuncs.com/youngt.apk';
    $ios_url = 'https://itunes.apple.com/cn/app/qing-tuan/id681045261?mt=8';

    $service_tag = strtolower(json_encode($_SERVER));
    if (strpos($service_tag, 'android') !== false) {
        if (strpos($service_tag, 'micromessenger') === false) {
            header("Location: $android_url");
        }
    } else if (strpos($service_tag, 'iphone') !== false || strpos($service_tag, 'ipad') !== false) {
        if (strpos($service_tag, 'micromessenger') === false) {
            header("Location: $ios_url");
        }
    } else {
        die("<span style='color:red;font-size:26px;'>没有该系统对应的客户端</span>");
    }
}

// 获取邮购订单地址信息
function getAddressInfo($str) {
    $data = json_decode($str, true);
    if (!is_array($data)) {
        return strip_tags($data);
    } else {
        return "收货人：{$data['name']}，电话：{$data['mobile']}，地址：{$data['province']}-{$data['area']}-{$data['city']}-{$data['street']}，邮编：{$data['zipcode']}";
    }
}

// 判断商家是否要使用第三方验证券号
function threeValidCoupon($partner_id, $data, $type) {
    if (!C('THREE_VALID_STATE')) {
        return false;
    }

    $config = C('THREE_VALID_PARTNER');
    if (in_array($partner_id, array_keys($config))) {
        $movie = new \Common\Org\MovieTicket();
        $movie->setPartnerId($config[$partner_id]['partner_id']);
        $movie->setVid($config[$partner_id]['vid']);
        switch ($type) {
            case 'create':
                $movie->createAll($data);
                break;
            case 'query':
                $movie->query($data);
                break;
            case 'verify':
                $movie->verifyAll($data);
                break;
            case 'invalid':
                $movie->invalidAll($data);
                break;
        }
        return true;
    } else {
        return false;
    }
}

// 获取云购关于redis的key值生成
function getCloudShopingRedisKey($key) {
    $pix = strtr(APP_DOMAIN, array('.' => '_'));
    return $pix . '_' . $key;
}

// 获取毫秒时间戳
function microtime_float() {
    $res = list($usec, $sec) = explode(" ", microtime());
    return sprintf('%.4f', $usec + $sec);
}

// 将毫秒时间戳格式化
function microtime_type($time, $type = 'His') {
    if (!$time) {
        return 0;
    }
    $res = list($usec, $sec) = @explode(".", sprintf('%.3f', $time));
    return date($type . $sec, $usec);
}

/**
 * 判断是否为ip
 * @param type $gonten
 * @return type
 */
function is_ip($gonten) {
    $ip = explode(".", $gonten);
    for ($i = 0; $i < count($ip); $i++) {
        if ($ip[$i] > 255) {
            return (0);
        }
    }
    return preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $gonten);
}

/**
 * php防注入和XSS攻击通用过滤.
 * @param type $arr
 *  $_GET     && SafeFilter($_GET);
    $_POST    && SafeFilter($_POST);
    $_COOKIE  && SafeFilter($_COOKIE);
 */
function safeFilter(&$arr) {
    $ra = array('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '/script/', '/javascript/', '/vbscript/', '/expression/', '/applet/', '/meta/', '/xml/', '/blink/', '/link/', '/style/', '/embed/', '/object/', '/frame/', '/layer/', '/title/', '/bgsound/', '/base/', '/onload/', '/onunload/', '/onchange/', '/onsubmit/', '/onreset/', '/onselect/', '/onblur/', '/onfocus/', '/onabort/', '/onkeydown/', '/onkeypress/', '/onkeyup/', '/onclick/', '/ondblclick/', '/onmousedown/', '/onmousemove/', '/onmouseout/', '/onmouseover/', '/onmouseup/', '/onunload/');
    if (is_array($arr)) {
        foreach ($arr as $key => $value) {
            if (!is_array($value)) {
                if (!get_magic_quotes_gpc()) {
                    $value  = addslashes($value);
                }
                $value     = preg_replace($ra, '', $value);
                $arr[$key] = htmlentities(strip_tags($value));
            } else {
                safeFilter($arr[$key]);
            }
        }
    }
}


/**
 * @param $e
 *
 * @return array|void
 */
function objectToArray($e){
    $e=(array)$e;
    foreach($e as $k=>$v){
        if( gettype($v)=='resource' ) return;
        if( gettype($v)=='object' || gettype($v)=='array' )
            $e[$k]=(array)objectToArray($v);
    }
    return $e;
}

/**
 * 发送HTTP请求方法
 * @param  string $url    请求URL
 * @param  array  $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
function http($url, $params = array(), $method = 'GET', $header = array(), $multi = false){
    $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => $header
    );
    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            //判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if($error) throw new Exception('请求发生错误：' . $error);
    return  $data;
}

function ota_action($order) {
    $ota = D('Ota');
    if ($ota->tmCheck($order['team_id'])) {
        if ($order['state'] == 'unpay' && NOW_TIME - $order['create_time'] > 7200) {
            return '<button class="btn btn-info btn-sm J_menuItem"  id="order-del" data-href="cancelOrder/id/'.$order['id'].'/tid/'.$order['team_id'].'" data-title="释放订单"><i class="fa fa-trash"></i> 取消订单</button>';
        }
    }
}

// 生成团单链接URL
function team_url($tid, $cid) {
    $model = M('category');
    $city = $model->where(array('id'=>$cid))->find();
    if (!$city || $city['zone'] != 'city' || $city['ename'] == 957) {
        $city = session(C('CITY_AUTH_KEY'));
        if (!$city) {
            $city = cookie('city');
        }
    }
    if (!$city || !defined('APP_DOMAIN')) {
        return 'javascript:void(0);';
    } else {
        return 'http://'.$city['ename'].'.'.APP_DOMAIN.'/team-'.$tid.'.html';
    }
}