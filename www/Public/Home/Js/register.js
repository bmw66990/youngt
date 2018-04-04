/**
 * Created by daishan on 2015/5/18.
 */
$(function () {
    $('.f-text').blur(function () {
        $('#message').css('display','none');
        formCheck(this);
    });
    $('#reg-form :submit').click(function () {
        $('#regbtn').attr('disabled', 'disabled');
        $('#regbtn').val('正在提交');
        var url = $base_url+"/Public/checkUser/act/reg";
        $.post(url, $('#reg-form').serialize(), function (data) {
            if (data.status == 1) {
                $('#ajax-check').val('0');
                $('#reg-form').submit();
            } else {
                $('#regbtn').removeAttr('disabled');
                $('#regbtn').val('提交注册');
                showMsg(data.error);
            }
        });
        return false;
    });
    $("form input[title]").tooltip({
        position: {
            my: "left+9 center",
            at: "right center"
        }
    });
    $("#verify").tooltip({
        position : {
            my : "left+183 center",
            at : "right center"
        }
    });
    $("#code").tooltip({
        position : {
            my : "left+183 center",
            at : "right center"
        }
    });
});

function getCodes(obj) {
    var mobile = $('#mobile').val();
    getCheckUser(mobile,obj);
}


function showMsg(msg) {
    $('#message').empty().append(msg);
    $('#message').show();
}


function getCheckUser(mobile,obj){
    var url = $base_url + "/Public/checkUser/act/user";
    var verify = $('input[name=verify]').val();
    $.post(url, {mobile:mobile,verify:verify,is_verify:true}, function (data) {
        if (data.status == 1) {
            obj.attr('disabled', 'disabled');
            obj.css('background', '#666666');
            sendCode(mobile,obj);
        }else{
            showMsg(data.error);
        }
    });
}


/**
 * 发送验证码
 * @param mobile
 */
function sendCode(mobile,obj){
    var InterValObj;  //timer变量，控制时间
    var url = $base_url+"/Public/smsCode";
    $.post(url, {mobile: mobile}, function (sms) {
        if (sms.status == -1) {
            showMsg(sms.error);
            return false;
        }else{
            var curCount = 90; //当前剩余秒数
            var SetRemainTime = function () {
                curCount--;
                obj.html(curCount + "秒后重新获取验证码");
                if (curCount == 0) {
                    obj.removeAttr('disabled');	//jqueryui设置button可用样式
                    obj.css('background', '#2ec3b4');
                    window.clearInterval(InterValObj);//停止计时器
                    obj.html("重新获取验证码");
                }
            }
            //设置button效果，开始计时
            InterValObj = window.setInterval(SetRemainTime, 1000); //启动计时器，1秒执行一次*/
        }
    });
}

/**
 * 失去焦点异步验证
 * @param obj
 */
function formCheck(obj) {
    var url = $base_url+"/Public/checkUser/act/field";
    $.post(url, {key: $(obj).attr('name'), val: $(obj).val()}, function (data) {
        if(data.status == -1){
            showMsg( data.error);
        }
    });
}

function fleshVerify(){
    //重载验证码
    var time = new Date().getTime();
    document.getElementById('verifyImg').src= $base_url+'/Public/verify/'+time;
}