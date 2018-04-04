$(function() {

    $("#verify-coupon").fancybox({
        'autoScale': false,
        'scrolling': 'no',
        'transitionIn': 'fade',
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500,
        'width': 480,
        'height': 260,
        'type': 'iframe'
    });
    $('#submit').click(function() {
        var $username = $('#username').val();
        var $password = $('#password').val();
        if(!$.trim($username)){
            $(".tipContent").show().text("账号不能为空！");
            return false;
        }
        if(!$.trim($password)){
            $(".tipContent").show().text("密码不能为空！");
            return false;
        }
        
        var $href = $(this).parents('form##login').attr('action');
        $.post($href, $('form').serialize(), function(data) {
            
            if(data.code && data.error && data.code!=0){
                $(".tipContent").show().text(data.error);
                return false
            }
            window.location.href=$base_url;
            $(".tipContent").fadeOut(4000);

        }, 'json');
        return false;

    });
    $("#login input[title]").tooltip({
        position: {
            my: "left+3 center",
            at: "right center"
        }
    });
})
