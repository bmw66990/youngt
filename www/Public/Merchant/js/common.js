$(function() {
    
    // 设置菜单选中状态
    var setMenu = function() {
        if($('ul.nav-list>li a').length<=0){
            return false;
        }
        var $localhref = window.location.href.toLowerCase();
        $('ul.nav-list>li a').each(function(i) {
            var $href = $(this).attr('href');
            var $partner_li = $(this).parents('li');

            if ($localhref.indexOf($href.toLowerCase()) >= 0) {
                if ($(this).parents('ul.submenu').length > 0) {
                    var $_partner_li = $(this).parents('ul.submenu').parents('li');
                    $_partner_li.addClass('active');
                }
                $partner_li.addClass('active');
                return false
            }
        });
    }();


    $(".openifram").click(function() {
        var tit = "";
        var hurl = $(this).attr("data-url");
        var w = 500;
        var h = 450;
        if ($(this).attr('title')) {
            tit = $(this).attr('title');
        }
        if ($(this).attr('data-w')) {
            w = $(this).attr('data-w');
        }
        if ($(this).attr('data-h')) {
            h = $(this).attr('data-h');
        }
        layer.open({
            type: 2,
            title: tit,
            shadeClose: true,
            shade: 0.8,
            area: [w + "px", h + "px"],
            content: [hurl] //这里content是一个URL，如果你不想让iframe出现滚动条，你还可以content: ['http://sentsin.com', 'no']
        });
    });



});
// ajax 异步请求操作
function ajax_operation(self) {
    var $this = $(self);
    var href = $this.attr('href');
    var confirm_tip = $this.attr('confirm_tip');

    if ($this.hasClass('disabled')) {
        return false;
    }
    if (confirm_tip) {
        var tip_res = window.confirm(confirm_tip);
        if (!tip_res) {
            return false;
        }
    }

    $this.addClass('disabled');
    if (show_message_tip) {
        show_message_tip({success: '正在操作，请稍后...'});
    }


    $.post(href, {}, function(res) {
        $this.removeClass('disabled');
        if (res.error && res.code && res.code != 0) {
            if (show_message_tip) {
                show_message_tip(res);
            } else {
                window.alert('操作失败');
            }
            return false;
        }
        if (show_message_tip) {
            show_message_tip(res);
        } else {
            window.alert('操作失败');
        }
        window.setTimeout(function() {
            window.location.reload();
        }, 1500);
        return false;
    }, 'json')

    return false;
}

