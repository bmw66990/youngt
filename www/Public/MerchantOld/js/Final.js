$(function() {
    $("#confirm").die().live('click', function() {
        var $money = $(this).attr('lang');
        var $confire = "您可提现金额："+$money+" 元";
        var $res = window.confirm($confire);
        if(!$res){
            return false;
        }
        var $href = $(this).attr('href');
        $.post($href, {}, function(res) {
            if (res.code && res.code != 0 && res.error) {
                window.alert(res.error);
            }else{
                window.alert('申请提款成功！');
            }
            window.location.reload();
        }, 'json');
        return false;

    });
})