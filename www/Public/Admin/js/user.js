/**
 * Created by daishan on 2015/6/11.
 */
function delUser(id) {
    var url = $base_url + '/User/delUser/id/' + id;
    var state = confirm('你确定要删除此用户');
    if (state) {
        window.location.href = url;
    }
}
/**
 * 开通代理快捷编辑团单权限
 */
function openTeam(id) {
    var url = $base_url + '/User/openTeam/id/' + id;
    var state = confirm('您确定给该代理开通团单编辑直接上线功能吗？');
    if (state) {
        window.location.href = url;
    }
}
$(function() {
    $('.authority-operation-btn').die().live('click', function() {
        var $this = $(this);
        var href = $this.attr('href');
        if ($this.hasClass('disabled')) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '正在处理，请稍等！'}));
            return false;
        }
        var confirm_tip = $this.attr('confirm_tip');
        var tip_res = window.confirm(confirm_tip);
        if (!tip_res) {
            return false;
        }
        $this.addClass('disabled');
        $.post(href, {}, function(res) {
            $this.removeClass('disabled');
            if (res.code && res.code != 0 && res.error) {
                $('#message-con').html($('#message-top-tmpl').tmpl(res));
                return false;
            }
            $('#message-con').html($('#message-top-tmpl').tmpl({success: '操作成功！'}));
            window.setTimeout(function() {
                window.location.reload();
            }, 300);
        }, 'json');
        return false;
    });
    
    // 设置管理员权限
     $('#user-do-auth-btn').die().live('click', function() {
         var $this = $(this);
        var $from = $this.parents('form#user-do-auth-form');
        var href = $from.attr('action');
        if ($this.hasClass('disabled')) {
             parent.window.alert('正在处理，请稍等！');
            return false;
        }
        var data = $from.serialize();
        $this.addClass('disabled');
        $.post(href,data, function(res) {
            $this.removeClass('disabled');
            if (res.code && res.code != 0 && res.error) {
                parent.window.alert( res.error);
                return false;
            }
            parent.window.alert('设置成功！');
             parent.window.location.reload();
        }, 'json');
        return false;
    });
    
    // 复选框选中
    $("input.checkall").die().live('click', function() {
        var $this = $(this);
        var check_list = $this.attr('check_list');
        var check_one = $this.attr('check_one') || "input.checkone";
        var $checkone = $this.parents(check_list).find(check_one);
        $checkone.attr('checked', false);
        if ($this.attr('checked')) {
            $checkone.attr('checked', true);
        }
        return true;
    });

    // 批量结算操作
    $('#authorityBatchDisable').die().live('click', function() {
        var $this = $(this);
        var $checked = $this.parents('div.form-list').find('tr.check-list').find("input.checkone");
        var check = [];
        var href = $this.attr('href');
        var confirm_tip = $this.attr('confirm_tip');

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
        var auth_ids = '';
        if (check.length > 0) {
            auth_ids = check.join(',');
        }

        if (!$.trim(auth_ids)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择批量禁用的权限！'}));
            return false;
        }

        $.post(href, {auth_id: auth_ids}, function(res) {
            if (res.code && res.code != 0 && res.error) {
                $('#message-con').html($('#message-top-tmpl').tmpl(res));
                return false;
            }
            window.alert('禁用成功！');
            window.location.reload();
            return false;
        }, 'json');
        return false;
    });

})