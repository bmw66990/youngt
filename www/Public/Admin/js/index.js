/**
 * Created by daishan on 2015/6/9.
 */
function daySign(){
    var url =  $base_url+'/Index/ajaxDaySign';
    $.get(url,'',function(data){
        if(data.status == '1'){
            $('#message-top-tmpl').tmpl(data).appendTo('#message-con');
            window.location.reload();
        }else{
            $('#message-top-tmpl').tmpl(data).appendTo('#message-con');
        }
    });
}

function delFeedback(id){
    var state = confirm('你确定要删除此条信息!');
    if(state == true){
        location.href = $base_url+'/Index/delFeedback/id/'+id;
    }
}

function saveFeedback(id){
    location.href = $base_url+'/Index/saveFeedback/id/'+id;
}

function commentDisplay(id){
    var state = confirm('你确定要屏蔽此条评论!');
    if(state == true){
        location.href = $base_url+'/Index/commentDisplay/id/'+id;
    }
}

function delComment(id){
    var state = confirm('你确定要删除此条评论!');
    if(state == true){
        location.href = $base_url+'/Index/delComment/id/'+id;
    }
}
