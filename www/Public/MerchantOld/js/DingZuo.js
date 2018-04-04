$(function() {
    $(".deal-dz-order").die().live('click', function() {
        var $confire = "您确定用户到店需要处理吗?";
        var $res = window.confirm($confire);
        if(!$res){
            return false;
        }
        var $id = $(this).attr('lang');
        var $href = $(this).attr('href');
        $.post($href, {id:$id}, function(res) {
            if (res.code && res.code != 0 && res.error) {
                window.alert(res.error);
            }else{
                window.alert('处理成功！');
            }
            window.location.reload();
        }, 'json');
        return false;
    });
})