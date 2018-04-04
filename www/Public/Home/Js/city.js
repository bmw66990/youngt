/**
 * Created by daishan on 2015/5/18.
 */
$(function(){
    var init = function(){
        getProvince();
        $(".citylist li").hover(function(){
            $(this).addClass('hover');
        },function(){
            $(this).removeClass('hover');
        });
        // 城市改变时提交表单
        $('#ename').change(function(){
            $('#select-city').submit();
        });
        $("#city-key").tooltip({
            position : {
                my : "left+60 center",
                at : "right center"
            }
        });
        $('#search-city').submit(function(){
            if($('#city-key').val() == ''){
                $("#city-key").tooltip({
                    content : '请输入城市名称'
                }).focus();
                return false;
            }
            getSearchCity('#city-key');
        });
    }();
});
//自动加载省份
function getProvince(){
    var url = $base_url+"/Public/getProvince";
    $.get(url,function(data){
        if(data.status == 1){
            $('#czone-tmpl').tmpl(data).appendTo('#czone');
        }
    });
}
// 省份改变时查询城市列表
function getCities(obj) {
    var url = $base_url + "/Public/getCities";
    $.get(url, {id: $(obj).val()}, function (data) {
        if(data.status == 1){
            $('#ename').empty();
            $('#ename-tmpl').tmpl(data).appendTo('#ename');
        }

    });
}

function getSearchCity(obj){
    var url = $base_url+"/Public/searchCity";
    $.get(url,{key:$(obj).val()},function(data){
        if(data['status']==1){
            location.href = $base_url+'/Public/city/'+'/ename/'+data['ename'];
        }else{
            $("#city-key").tooltip({
                content : data.error
            }).focus();
        }
    })
}