/**
 * Created by daishan on 2015/6/15.
 */
function delCard(id){
    var state = confirm('你确定要删除编号为'+id+'的代金券吗?');
    if(state){
        window.location.href = $base_url+"/Market/delCard/id/"+id;
    }
}
function delAdManage(id){
    var state = confirm('你确定要删除编号为'+id+'的广告吗?');
    if(state){
        window.location.href = $base_url+"/Market/delAdManage/id/"+id;
    }
}

function delActivities(id){
    var state = confirm('你确定要删除编号为'+id+'的活动吗?');
    if(state){
        window.location.href = $base_url+"/Market/delActivities/id/"+id;
    }
}