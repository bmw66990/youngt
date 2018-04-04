/**
 * Created by daishan on 2015/6/13.
 */


function delType(id,url){
    var state = confirm('你确定要删除此条分类信息吗');
    if(state){
        window.location.href=$base_url+'/Manage/delCate/id/'+id+'/url/'+url;
    }
}