$(function() {

    // 修改资料提交
    $('#partner-form-submit').die().live('click', function() {
        var $form = $(this).parents('form');
        var $tip = $form.find(".tipContent");
        // 非法参数判断
        var $oldPassword = $form.find("input:[name='oldpassword']").val();
        var $newPassword = $form.find("input:[name='newpassword']").val();
        var $cnewPassword = $form.find("input:[name='cnewpassword']").val();
        if ($oldPassword) {
            if (!$newPassword || !$cnewPassword) {
                $tip.show().text("新密码不能为空！");
                return false;
            }
            if ($.trim($newPassword) != $.trim($cnewPassword)) {
                $tip.show().text("两次输入的新密码不一致！");
                return false;
            }
        }

        var $href = $form.attr('action');
        var $data = $form.serialize();
        // 发送请求
        $.post($href, $data, function(res) {
            if (res.error && res.code != 0) {
                $tip.show().text(res.error);
                return false;
            }
            window.location.reload();
            return false;
        }, 'json')

        return false;
    });

    // 用户修改密码
    $('#edit-pwd-form').die().live('click', function() {
      
        var $form = $(this).parents('form');
        var $tip = $form.find(".tipContent");

        // 非法参数判断
        var $oldPassword = $form.find("input:[name='oldpassword']").val();
        var $newPassword = $form.find("input:[name='newpassword']").val();
        var $cnewPassword = $form.find("input:[name='cnewpassword']").val();
        if (!$.trim($oldPassword)) {
            $tip.show().text("旧密码不能为空！");
            return false;
        }
        if (!$.trim($newPassword) || !$.trim($cnewPassword)) {
            $tip.show().text("新密码不能为空！");
            return false;
        }
        if ($.trim($newPassword) != $.trim($cnewPassword)) {
            $tip.show().text("两次输入的新密码不一致！");
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
            window.alert('密码修改成功！');
            parent.window.$.fancybox.close();
            return false;
        }, 'json')
        
        return false;
    });
});