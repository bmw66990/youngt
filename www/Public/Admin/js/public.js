$(function(){
    
    // 登录 空值检测
    $('#login-form-btn').die().live('click',function(){
        var $form = $(this).parents('#login-form');
        var username = $form.find('#username').val();
        var password = $form.find('#password').val();
        if(!$.trim(username)){
            window.alert('用户名不能为空！');
            return false;
        }
         if(!$.trim(password)){
            window.alert('密码不能为空！');
            return false;
        }
        $form.submit();
        return false;
    });
});
