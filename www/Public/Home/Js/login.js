/**
 * Created by daishan on 2015/5/18.
 */
$(function(){
    var check = true;
    $('#login-form').submit(function () {
        // 消息显示
        if (check === true) {
            if ($('#account', $(this)).val() == '') {
                showMsg('请输入账户');
                return false;
            }
            if ($('#password', $(this)).val() == '') {
                showMsg('请输入密码');
                return false;
            }
            if ($('#verify', $(this)).val() != 'no' && $('#verify', $(this)).val() == '') {
                showMsg('请输入验证码');
                return false;
            }
            $.post($base_url+"/Public/checkUser/act/login", $(this).serialize(), function (data) {
                if (data.status == -1) {
                    showMsg(data.error);
                    fleshVerify();
                } else {
                    check = false;
                    $('#login-form').submit();
                }
            }, 'json');
            return false;
        }else {
            $('#login-form :submit').click();
        }
    });
});
function showMsg(msg) {
    $('#message').css('display','block');
    $('#message').empty().append(msg);
}
function fleshVerify(){
    //重载验证码
    var time = new Date().getTime();
    document.getElementById('verifyImg').src= $base_url+'/Public/verify/'+time;
}

