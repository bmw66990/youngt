<?php

namespace YoungtApi\Controller;

/**
 * 青团网外接APi接口
 * Class IndexController
 * @package Api\Controller
 */
class IndexController extends CommonController {

    /**
     * 默认访问获取城市列表
     */
    public function index(){
        header('Content-Type:text/html; charset=utf-8');
        echo '<html>';
        echo '<body>';
        echo '<pre/>';
        echo 'Api列表:<br/>';
        echo "|-<a href='".U('Index/getCityList',array('help'=>'help'))."''>/getCityList?help</a> 获取城市列表（帮助）<br/>";
        echo "|-<a href='".U('Index/getCityList')."''>/getCityList</a> 获取城市列表<br/>";
        echo "|-<a href='".U('Index/getCateList',array('help'=>'help'))."''>/getCateList?help</a> 获取分类列表（帮助）<br/>";
        echo "|-<a href='".U('Index/getCateList')."'>/getCateList</a> 获取分类列表<br/>";
        echo "|-<a href='".U('Index/getTeamList',array('help'=>'help'))."'>/getTeamList?help</a> 获取团单列表（帮助）<br/>";
        echo "|-<a href='".U('Index/getTeamList',array('city_id'=>'1','group_id'=>255))."'>/getTeamList?city_id=1&group_id=255</a> 获取团单列表<br/>";
        echo "|-<a href='".U('Index/getTeamCout',array('help'=>'help'))."'>/getTeamList?help</a> 获取团单列表（帮助）<br/>";
        echo "|-<a href='".U('Index/getTeamCout',array('id'=>'1','key'=>255))."'>/getTeamCout?id=1&key=25</a> 获取团单详情<br/>";
        echo '</body>';
        echo '</html>';
    }

    /**
     * 获取城市列表
     */
   public function getCityList(){
       $this->helpInfo = "
       /**
        * 获取城市列表
        * 参数【无需参数直接请求】
        * 返回值：code 0=>'错误码'，'msg'=>'错误说明'。 1=>'成功'，'remarks'=>'返回字段说明'
        * 请求方式:GET
        */";
       $this->_help();
       $zone = 'city';
       $where = array('czone'=>'陕西');
       $data  = $this->_getCategoryList($zone, $where);
       if($data){
           $city = array();
           foreach($data as $val){
                $city[] = array('id'=>$val['id'],'name'=>$val['name']);
            }
           $remarks = array(
               'id'=>'城市id【获取团单列表时所需city_id参数】',
               'name'=>'城市名称',
           );
           $this->outPut($city,0,'',$remarks);
       }else{
           $this->outPut('',-1,'获取数据失败！');
       }
   }

    /**
     * 获取给分类列表
     */
    public function getCateList(){
        $this->helpInfo = "
        /**
        * 获取分类列表
        * 参数【无需参数直接请求】
        * 返回值：code 0=>'错误码'，'msg'=>'错误说明'。 1=>'成功'，'remarks'=>'返回字段说明'
        * 请求方式:GET
        */";
        $this->_help();
        $zone = 'group';
        $where = array('fid'=>'0');
        $data  = $this->_getCategoryList($zone, $where);
        if($data){
            $cate= array();
            foreach($data as $val){
                $cate[] = array('id'=>$val['id'],'name'=>$val['name']);
            }
            $remarks = array(
                'id'=>'分类id【获取团单列表时所需group_id参数】',
                'name'=>'分类名称',
            );
            $this->outPut($cate,0,'',$remarks);
        }else{
            $this->outPut('',-1,'获取数据失败！');
        }
    }

    /**
     * 获取团单列表
     */
    public function getTeamList(){
        $this->helpInfo = "
        /**
        * 获取分类列表
        * 参数【city_id=>'城市id（必传参数）','group_id'=>'分类id（必传参数）','page'=>'请求页（可选参数 默认为1）'】
        * 返回值：code 0=>'错误码'，'msg'=>'错误说明'。 1=>'成功'，'remarks'=>'返回字段说明'
        * 请求方式:GET
        */";
        $this->_help();
        $city_id  = I('get.city_id',0,'intval');
        $group_id = I('get.group_id',0,'intval');
        $page     = I('get.page',1,'intval');
        if($city_id == 0){
            $this->outPut('',-1,'请传入城市id后再获取数据！');
        }
        if($group_id == 0){
            $this->outPut('',-1,'请传入分类id后再获取数据！');
        }
        $where = array(
            'team_type'  => 'normal',
            'city_id'    => $city_id,
            'group_id'   => $group_id,
            'begin_time' => array('lt', time()),
            'end_time'   => array('gt', time())
        );
        $count = M('team')->where($where)->count('id');
        $pageSize = 10;
        $countPage = ceil($count/$pageSize);
        if($page > $countPage){
            $this->outPut('',-1,"只有{$countPage}页数据，无法获取第{$page}页数据");
        }
        $limit = ($page-1)*$pageSize . ',' . $pageSize;
        $data = M('team')->field('id,image,title,product,team_price,market_price,now_number')->where($where)->order('sort_order desc,id desc')->limit($limit)->select();
        if($data){
            foreach($data as &$val){
                $val['image'] = getImagePath($val['image']);
            }
            $remarks = array(
                'id'           => '团单id',
                'image'        => '团单图片',
                'title'        => '团单长标题',
                'product'      => '团单短标题',
                'team_price'   => '团单价格',
                'market_price' => '市场价格',
                'now_number'   => '已售数量',
                '分页说明，如需查询更多数据请传入page=$page参数【$page为请求页数】，countPage为总页数'
            );
            $this->outPut(array('countPage'=>$countPage,'data'=>$data),0,'',$remarks);
        }else{
            $this->outPut('',-1,'获取数据失败！');
        }

    }

    /**
     * 获取团单详情
     */
    public function getTeamCout(){
        $this->helpInfo = "
        /**
        * 获取分类列表
        * 参数【id=>'团单（必传参数）'
        * 返回值：code 0=>'错误码'，'msg'=>'错误说明'。 1=>'成功'，'remarks'=>'返回字段说明'
        * 请求方式:GET
        */";
        $this->_help();
        $city_id  = I('get.id',0,'intval');
        $key  = I('get.key',0,'intval');
        $keyy=self::encryptPwd($city_id);//$this->encryptPwd($city_id);
        //echo $keyy;exit;
        if($key==$keyy){
            if($city_id == 0){
                        $this->outPut('',-1,'请传入城市id后再获取数据！');
                    }
                    $where = array(
                        'team_type'  => 'normal',
                        'id'    => $city_id
                    );
                    $data = M('team')->field('id,image,title,product,team_price,market_price,now_number,partner_id,detail')->where($where)->select();
                    if($data){
                        $wherep['id']=$data['partner_id'];
                        $pdata = M('partner')->field('address,phone')->where($wherep)->select();
                        foreach($data as &$val){
                            $val['image'] = getImagePath($val['image']);
                        }
                        $data['address']=$pdata['address'];
                        $data['phone']=$pdata['phone'];
                        $remarks = array(
                            'id'           => '团单id',
                            'image'        => '团单图片',
                            'title'        => '团单长标题',
                            'product'      => '团单短标题',
                            'team_price'   => '团单价格',
                            'market_price' => '市场价格',
                            'now_number'   => '已售数量',
                            'address'   => '地址',
                            'phone'   => '电话',
                            'detail'=>'详情'
                        );
                        $this->outPut($data,0,'',$remarks);
                    }else{
                        $this->outPut('',-1,'获取数据失败！');
                    }
        }
        else{
            $this->outPut('',-1,'非法请求！');
        }

    }
    public function encryptPwd($str) {
        return md5(trim($str) . C('scjkey'));
    }
}
