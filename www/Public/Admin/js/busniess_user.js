

laydate.skin('molv');
if ($('#start').length > 0) {
    var start = {
        elem: '#start',
        format: $('#start').attr('format') || 'YYYY-MM-DD',
        max: '2099-06-16 23:59:59', //最大日期
        istoday: false,
        istime: true,
        choose: function(datas) {
            end.min = datas; //开始日选好后，重置结束日的最小日期
        }
    };
    laydate(start);
}

if ($('#end').length > 0) {
    var end = {
        elem: '#end',
        format: $('#end').attr('format') || 'YYYY-MM-DD',
        max: '2099-06-16 23:59:59',
        istoday: false,
        istime: true,
        choose: function(datas) {
            start.max = datas; //结束日选好后，重置开始日的最大日期
        }
    };
    laydate(end);
}
$(function(){
    // js逻辑代码
    
     // 复选框选中
    $("tr.list-title input.checkall").die().live('click', function() {
        var $this = $(this);
        var $checkone = $this.parents('table').find('input.checkone');
        $checkone.attr('checked', false);
        if ($this.attr('checked')) {
            $checkone.attr('checked', true);
        }
        return true;
    });
    
    $('#user-distribution-exec').die().live('click',function(){
        var $this = $(this);
        var $checked = $this.parents('div.form-list').find("tr.list").find("input.checkone");
        var check = [];
        var href = $this.attr('href');
        var confirm_tip = $this.attr('confirm_tip');

        if ($this.hasClass('disabled')) {
            return false;
        }

        // 操作提示
        var tip_res = window.confirm(confirm_tip);
        if (!tip_res) {
            return false;
        }

        $('#message-con').html('');
        for (var i = 0; i < $checked.length; i++) {
            var $checkone = $($checked[i]);
            if ($checkone.attr('checked')) {
                check.push($checkone.val())
            }
        }
        var user_ids = '';
        if (check.length > 0) {
            user_ids = check.join(',');
        }
        var db_user_id=$('#db_user_id').val();
         if (!$.trim(db_user_id)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择分配的db！'}));
            return false;
        }
        if (!$.trim(user_ids)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择要分配的用户！'}));
            return false;
        }

        var btn_html = $this.html();
        $this.addClass('disabled');
        $this.html('正在处理...');
        $.post(href, {user_ids: user_ids,db_user_id:db_user_id}, function(res) {
            $this.removeClass('disabled');
            $this.html(btn_html);
            if (res.code && res.code != 0 && res.error) {
                $('#message-con').html($('#message-top-tmpl').tmpl(res));
                return false;
            }
            window.alert(res.success);
            window.location.reload();
            return false;
        }, 'json');
        return false;
    });
});

/**
 * 设置意向客户
 * @param id
 * @param status
 */
function setTarget(id,status){
    if(status == 1){
        var confirm_status = confirm('您确定将此用户设置为无意向客户？');
    }else{
        var confirm_status = confirm('您确定将此用户设置为有意向客户？');
    }
    var url = $base_url+'/CanvassBusiness/setTarget/user_id/'+id+ '/status/'+status;
    if(confirm_status){
        window.location.href = url;
    }
}

/**
 * 设置意向客户
 * @param id
 * @param status
 */
function setHaveTarget(id){
    var confirm_status = confirm('您确定将此用户变更为意向客户？');
    var url = $base_url+'/CanvassBusiness/setHaveTarget/id/'+id;
    if(confirm_status){
        window.location.href = url;
    }
}