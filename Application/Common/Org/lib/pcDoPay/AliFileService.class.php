<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * 文件上传类
 * @category   ORG
 * @package  ORG
 * @subpackage  Net
 * @author    liu21st <liu21st@gmail.com>
 */
class AliFileService {//类定义开始

    private $config =   array(
        'inputName'         =>  '',
        'maxSize'           =>  -1,    // 上传文件的最大值
        'supportMulti'      =>  true,    // 是否支持多文件上传
        'allowExts'         =>  array(),    // 允许上传的文件后缀 留空不作后缀检查
        'allowTypes'        =>  array(),    // 允许上传的文件类型 留空不做检查
        'thumb'             =>  true,    // 使用对上传图片进行缩略图处理
        'imageClassPath'    =>  'ORG.Util.Image',    // 图库类包路径
        'thumbMaxWidth'     =>  '',// 缩略图最大宽度
        'thumbMaxHeight'    =>  '',// 缩略图最大高度
        'thumbPrefix'       =>  '',// 缩略图前缀
        'thumbSuffix'       =>  '_thumb',
        'thumbPath'         =>  '',// 缩略图保存路径
        'thumbFile'         =>  '',// 缩略图文件名
        'thumbExt'          =>  '',// 缩略图扩展名        
        'thumbRemoveOrigin' =>  false,// 是否移除原图
        'thumbType'         =>  0, // 缩略图生成方式 1 按设置大小截取 0 按原图等比例缩略
        'zipImages'         =>  false,// 压缩图片文件上传
        'autoSub'           =>  false,// 启用子目录保存文件
        'subType'           =>  'hash',// 子目录创建方式 可以使用hash date custom
        'subDir'            =>  '', // 子目录名称 subType为custom方式后有效
        'dateFormat'        =>  'Ymd',
        'hashLevel'         =>  1, // hash的目录层次
        'savePath'          =>  '',// 上传文件保存路径
        'autoCheck'         =>  true, // 是否自动检查附件
        'uploadReplace'     =>  true,// 存在同名是否覆盖
        'saveRule'          =>  'uniqid',// 上传文件命名规则
        'hashType'          =>  'md5_file',// 上传文件Hash规则函数名
        );
        
    // 错误信息
    private $error = '';
    // 上传成功的文件信息
    private $uploadFileInfo ;

    private $service;
    private $bucket;
    private $temp;

    public function __get($name){
        if(isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    public function __set($name,$value){
        if(isset($this->config[$name])) {
            $this->config[$name]    =   $value;
        }
    }

    public function __isset($name){
        return isset($this->config[$name]);
    }
    
    /**
     * 架构函数
     * @access public
     * @param array $config  上传参数
     */
    public function __construct($config=array()) {
        if(is_array($config)) {
            $this->config   =   array_merge($this->config,$config);
        }

        require(dirname(__FILE__).'/aliyun_oss/sdk.class.php');
        $this->service = new ALIOSS();
        $this->service->set_debug_mode(TRUE);

        if(!isset($this->bucket)){
            $this->bucket = C('TEAM_IMG_BUCKET');
        }

        if(!isset($this->temp)){
            $this->temp = RUNTIME_PATH.'Temp/';
        }

        $this->thumbMaxWidth = C('THUMB_MAX_WIDTH');
        $this->thumbMaxHeight = C('THUMB_MAX_HEIGHT');
    }

    /**
     * 获取错误代码信息
     * @access public
     * @param string $errorNo  错误号码
     * @return void
     */
    protected function error($errorNo) {
         switch($errorNo) {
            case 1:
                $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
                break;
            case 2:
                $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                break;
            case 3:
                $this->error = '文件只有部分被上传';
                break;
            case 4:
                $this->error = '没有文件被上传';
                break;
            case 6:
                $this->error = '找不到临时文件夹';
                break;
            case 7:
                $this->error = '文件写入失败';
                break;
            default:
                $this->error = '未知上传错误！';
        }
        return ;
    }

    /**
     * 检查上传的文件
     * @access private
     * @param array $file 文件信息
     * @return boolean
     */
    private function check($file) {
        if($file['error']!== 0) {
            //文件上传失败
            //捕获错误代码
            $this->error($file['error']);
            return false;
        }
        //文件上传成功，进行自定义规则检查
        //检查文件大小
        if(!$this->checkSize($file['size'])) {
            $this->error = '上传文件大小不符！';
            return false;
        }

        //检查文件Mime类型
        if(!$this->checkType($file['type'])) {
            $this->error = '上传文件MIME类型不允许！';
            return false;
        }
        //检查文件类型
        if(!$this->checkExt($file['extension'])) {
            $this->error ='上传文件类型不允许';
            return false;
        }

        //检查是否合法上传
        if(!$this->checkUpload($file['tmp_name'])) {
            $this->error = '非法上传文件！';
            return false;
        }
        return true;
    }

    // 自动转换字符集 支持数组转换
    private function autoCharset($fContents, $from='gbk', $to='utf-8') {
        $from   = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to     = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            return $fContents;
        }
    }

    /**
     * 检查上传的文件类型是否合法
     * @access private
     * @param string $type 数据
     * @return boolean
     */
    private function checkType($type) {
        if(!empty($this->allowTypes))
            return in_array(strtolower($type),$this->allowTypes);
        return true;
    }


    /**
     * 检查上传的文件后缀是否合法
     * @access private
     * @param string $ext 后缀名
     * @return boolean
     */
    private function checkExt($ext) {
        if(!empty($this->allowExts))
            return in_array(strtolower($ext),$this->allowExts,true);
        return true;
    }

    /**
     * 检查文件大小是否合法
     * @access private
     * @param integer $size 数据
     * @return boolean
     */
    private function checkSize($size) {
        return !($size > $this->maxSize) || (-1 == $this->maxSize);
    }

    /**
     * 检查文件是否非法提交
     * @access private
     * @param string $filename 文件名
     * @return boolean
     */
    private function checkUpload($filename) {
        return is_uploaded_file($filename);
    }

    /**
     * 取得上传文件的后缀
     * @access private
     * @param string $filename 文件名
     * @return boolean
     */
    private function getExt($filename) {
        $pathinfo = pathinfo($filename);
        return $pathinfo['extension'];
    }

    /**
     * 取得上传文件的信息
     * @access public
     * @return array
     */
    public function getUploadFileInfo() {
        return $this->uploadFileInfo;
    }

    /**
     * 取得最后一次错误信息
     * @access public
     * @return string
     */
    public function getErrorMsg() {
        return $this->error;
    }

    // 创建阿里云目录
    public function createDir($savepath){
        $paths = explode('/', trim($savepath,'/'));
        foreach($paths as $i => $path){
            if($i != 0){
                $paths[$i] = $paths[$i-1].'/'.$path;
            }
            $object = $paths[$i].'/';
            if($this->service->is_object_exist($this->bucket,$object)->status != 200){
                if($this->service->create_object_dir($this->bucket,$object)->status != 200){
                    $res = false;
                }else{
                    $res = true;
                }
            }
        }
        return $res;
    }

    // 下载文件到本地
    public function getFile($file){
        $filename = $this->temp.$file['savename'];
        if(!file_exists($file)){
            $options = array(
                ALIOSS::OSS_FILE_DOWNLOAD => $this->temp.$file['savename'],
                ALIOSS::OSS_CONTENT_TYPE => $file['type'],
            );
            // 从阿里云获取图片文件
            if($this->service->get_object($this->bucket, $file['savepath'].$file['savename'], $options)->status != 200){
                return false;
            }
        }
        return $filename;
    }

    private function aliSave($file){
        //print_r($file);
        $object = $file['savepath'].$file['savename'];

        if(!$this->uploadReplace && $this->service->is_object_exist($this->bucket,$object)->status == 200) {
            // 不覆盖同名文件
            $this->error    =   '文件已经存在！'.$object;
            return false;
        }
        
        // 如果是图像文件 检测文件格式
        if( in_array(strtolower($file['extension']),array('gif','jpg','jpeg','bmp','png','swf'))) {
            $info   = getimagesize($file['tmp_name']);
            if(false === $info || ('gif' == strtolower($file['extension']) && empty($info['bits']))){
                $this->error = '非法图像文件';
                return false;                
            }
        }

        if($this->service->upload_file_by_file($this->bucket,$this->autoCharset($object,'utf-8','gbk'),$file['tmp_name'])->status != 200) {
            $this->error = '文件上传保存错误！';
            return false;
        }

        $filename = $this->getFile($file);
        if(!$filename){
            $this->error = '获取源文件失败,无法生成缩略图！';
            return false;
        }

        if($this->thumb && in_array(strtolower($file['extension']),array('gif','jpg','jpeg','bmp','png'))) {
            $image =  getimagesize($filename);
            if(false !== $image) {
                //是图像文件生成缩略图
                $thumbWidth     =   explode(',',$this->thumbMaxWidth);
                $thumbHeight    =   explode(',',$this->thumbMaxHeight);
                $thumbPrefix    =   explode(',',$this->thumbPrefix);
                $thumbSuffix    =   explode(',',$this->thumbSuffix);
                $thumbFile      =   explode(',',$this->thumbFile);
                $thumbPath      =   $this->thumbPath ? $this->thumbPath : dirname($filename).'/';
                $thumbExt       =   $this->thumbExt ? $this->thumbExt : $file['extension']; //自定义缩略图扩展名
                // 生成图像缩略图
                import($this->imageClassPath);
                for($i=0,$len=count($thumbWidth); $i<$len; $i++) {
                    if(!empty($thumbFile[$i])) {
                        $thumbname  =   $thumbFile[$i];
                    }else{
                        $prefix     =   isset($thumbPrefix[$i])?$thumbPrefix[$i]:$thumbPrefix[0];
                        $suffix     =   isset($thumbSuffix[$i])?$thumbSuffix[$i]:$thumbSuffix[0];
                        $thumbname  =   $prefix.basename($filename,'.'.$file['extension']).$suffix;
                    }
                    if(1 == $this->thumbType){
                        Image::thumb2($filename,$thumbPath.$thumbname.'.'.$thumbExt,'',$thumbWidth[$i],$thumbHeight[$i],true);
                    }else{
                        Image::thumb($filename,$thumbPath.$thumbname.'.'.$thumbExt,'',$thumbWidth[$i],$thumbHeight[$i],true);                        
                    }
                    // 上传缩略图
                    $thumbImg = $thumbPath.$thumbname.'.'.$thumbExt;
                    $object = $file['savepath'].basename($thumbImg);
                    if($this->service->upload_file_by_file($this->bucket,$this->autoCharset($object,'utf-8','gbk'),$thumbImg)->status == 200){
                        // 上传成功后删除临时文件
                        unlink($thumbImg);
                    }
                }
            }
        }
        return true;
    }


    // 传送文件到阿里云存储
    public function aliUpload($object = ''){

        $time = time();

        if($object != ''){
            $this -> aliRemove($object);
        }

        $savepath = $object != '' ? dirname($object).'/' : date('Y-n',$time).'/';

        // 创建目录
        if(!$this->createDir($savepath)){
            $this->error = '创建目录失败';
            return false;
        }

        if(!isset($this->inputName)){
            $this->error  =  '表单Name值没有设置';
            return false;
        }

        $file   =  $_FILES[$this->inputName];

        if(!empty($file['name'])) {
            //登记上传文件的扩展信息
            $file['key']        =   $this->inputName;
            $file['extension']  =   $this->getExt($file['name']);
            $file['savepath']   =   $savepath;
            $file['savename']   =   $object != '' ? basename($object) : $time.".".$file['extension'];

            // 自动检查附件
            if($this->autoCheck) {
                if(!$this->check($file))
                    return false;
            }

            //保存上传文件
            if(!$this->aliSave($file)){
                return false;
            }
            if(function_exists($this->hashType)) {
                $fun =  $this->hashType;
                $file['hash']  =  $fun($this->autoCharset($this->getFile($file),'utf-8','gbk'));
            }
            //上传成功后保存文件信息，供其他地方调用
            unset($file['tmp_name'],$file['error']);
            $isUpload   = true;
        }

        if($isUpload) {
            $this->uploadFileInfo = $file;
            // 上传成功后删除临时文件
            unlink($this->temp.$file['savename']);
            return true;
        }else {
            $this->error  =  '没有选择上传文件';
            return false;
        }            
    }
}

// 格式化阿里云返回结果_仅用于调试代码
function _format($response) {
    echo '|-----------------------Start---------------------------------------------------------------------------------------------------'."\n";
    echo '|-Status:' . $response->status . "\n";
    echo '|-Body:' ."\n"; 
    echo $response->body . "\n";
    echo "|-Header:\n";
    print_r ( $response->header );
    echo '-----------------------End-----------------------------------------------------------------------------------------------------'."\n\n";
}