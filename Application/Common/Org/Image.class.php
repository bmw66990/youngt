<?php
/**
 * Created by PhpStorm.
 * User: zhoujz
 * Date: 15-3-13
 * Time: 上午11:42
 */


namespace Common\Org;


/**
 * Class OSS
 *
 * @package Common\Org
 */
class Image 
{


    /**
     * ALIOSS客户端
     * @var \ALIOSS|null
     */
    static $image = null;
    
    /**
     * 压缩图片的宽度大小
     */
    const THUMB_IMAGE_WIDTH=640;
    
    /**
     * 水印图
     */
    static $water_image_path='';

    

    /**
     *构造函数
     */
    public function __construct()
    {
        self::$image = new \Think\Image();
        self::$water_image_path = __DIR__.'/lib/Image/water.png';
    }
    
    /**
        * 团单详情图片处理
       */
    public function teamDetailImage($imagePath='',$option=array()){
        if(!$imagePath){
            return false;
        }
        if(!file_exists($imagePath)){
            return false;
        }
        if(!isset($option['thumb_width']) || !trim($option['thumb_width'])){
            $option['thumb_width'] = self::THUMB_IMAGE_WIDTH;
        }
       
        self::$image->open($imagePath);
        // 压缩图片
        self::$image->thumb($option['thumb_width'], 99999999999)->save($imagePath);
        
        // 添加水印
        self::$image->water(self::$water_image_path,\Think\Image::IMAGE_WATER_CENTER)->save($imagePath);
        return true;
    }
    
    

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return \call_user_func(array(self::$image, $method), $args[0]);
    }


}