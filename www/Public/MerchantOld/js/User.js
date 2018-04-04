$(function() {
    if ($("a#access-manager-add-a").fancybox) {
        $("a#access-manager-add-a").fancybox({
            'autoScale': false,
            'scrolling': 'no',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 422,
            'height': 322,
            'type': 'iframe'
        });
        $("a.access-manager-edit-a").fancybox({
            'autoScale': false,
            'scrolling': 'no',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 422,
            'height': 322,
            'type': 'iframe'
        });
         $("a.access-manager-do-auth-a").fancybox({
            'autoScale': false,
            'scrolling': 'no',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 800,
            'height': 500,
            'type': 'iframe'
        });
    }

    // 用户修改密码
    $('#access-manager-add-btn').die().live('click', function() {

        var $form = $(this).parents('form#access-manager-add-form');
        var $tip = $form.find(".tipContent");

        // 非法参数判断
        var $username = $form.find("input:[name='login_access[username]']").val();
        if (!$.trim($username)) {
            $tip.show().text("账号不能为空！");
            return false;
        }

        var $href = $form.attr('action');
        var $data = $form.serialize();
        // 发送请求
        $.post($href, $data, function(res) {
            if (res.error && res.code != 0) {
                $tip.show().text(res.error);
                return false;
            }
            window.alert('操作成功！');
            parent.window.$.fancybox.close();
            parent.window.setTimeout(function() {
                parent.window.location.reload();
            }, 500);
            return false;
        }, 'json')

        return false;
    });

    // 账号操作
    $('a.access-manager-operation').die().live('click', function() {
        var $this = $(this);
        var confirm_tip = $this.attr('confirm_tip');
        var href = $this.attr('href');

        if ($this.hasClass('disabled')) {
            window.alert('正在处理，请稍等！');
            return false;
        }

        var tip_res = window.confirm(confirm_tip);
        if (!tip_res) {
            return false;
        }
        $this.addClass('disabled');
        $.post(href, {}, function(res) {
             $this.removeClass('disabled');
            if (res.error && res.code != 0) {
                 window.alert(res.error);
                return false;
            }
            window.alert('操作成功！');
            window.location.reload();
            return false;
        }, 'json');
     return false;
    });

})
