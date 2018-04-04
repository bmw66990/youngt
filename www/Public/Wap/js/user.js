$(function() {

    var set_menu_init = function() {
        var $localhref = window.location.href.toLowerCase();
        $('ul#orders>li').each(function(i) {
            var $href = $(this).find('a').attr('href');
            $href = $href.substring(0, $href.indexOf('.')).toLowerCase();
            if ($href && $localhref.indexOf($href) >= 0) {
                $(this).addClass('active');
            }
        });
    }();
    // next-page-show
    $('div.container-fluid').on('click', 'a.next-page-show', function() {
        var lastid = $(this).attr('last_id');
        var href = window.location.href;
        var lastid_type = "?lastid=" + lastid;
        if (href.indexOf('&') > 0) {
            lastid_type = "&lastid=" + lastid;
        }
        href = href + lastid_type;
        $(this).attr('href', href);

        return true;
    });

    // 取消退款
    $('div.container-fluid').on('click', 'button.btn-cancel-refund', function() {
        var href = $(this).attr('load_href');
        var tip_res = window.confirm("您确定要取消退款吗？");
        if (!tip_res) {
            return false;
        }
        $.post(href, {}, function(data) {
            if (data.code == 0) {
                window.alert('取消成功！');
                window.setTimeout(function() {
                    window.location.href = data.url;
                }, 500);
                return false;
            } 
            window.alert('取消失败');
            
        })
        return true;
    });
    
      $('div.container-fluid').on('click', 'a.btn_ajax_post', function() {
        var href = $(this).attr('href');
        var tip_message = $(this).attr('tip_message') || '您确定要执行此操作吗？';
        var tip_res = window.confirm(tip_message);
        if (!tip_res) {
            return false;
        }
        $.post(href, {}, function(data) {
            if (data.code == 0) {
                window.alert('操作成功！');
                window.setTimeout(function() {
                    window.location.reload();
                }, 500);
                return false;
            } 
            window.alert('操作失败');
        })
        return false;
    });

    if ($('div#star').raty) {

        $('div.container-fluid').on('click', 'button.btn-do-review-submit', function() {
            var $from = $(this).parents('form#do-review-form');
            var href = $from.attr('action');
            var content = $from.find('#content').val();
            var score = $('div#star').attr('score');
            if (!$.trim(score) || !$.trim(content)) {
                window.alert("请打分或输入评价内容后再进行发表评论!");
                return false;
            }
            $.post(href, {score: score, content: content}, function(data) {
                if (data.code == 0) {
                    window.alert('评论成功！');
                    window.setTimeout(function() {
                        window.location.href = data.url;
                    }, 500);
                    return false;
                }
                window.alert(data.error);

            })
            return true;
        });
        $('div#star').raty({
            onClick: function(score) {
                $('div#star').attr('score', score)
            },
            path: $('div#star').attr('path')
        });
    }

    $('div.container-fluid').on('click', '#check', function() {
        $("#qita").hide();
        if (this.checked) {
            $("#qita").show();
        }
        return true;
    });
    $('div.container-fluid').on('click', 'button.btn-do-refund-submit', function() {
        var $form = $(this).parents('form#do-refund-form');
        var href = $form.attr('action');
        var check_len = $form.find('input[type=checkbox]:checked').length;
        if (!check_len || check_len <= 0) {
            window.alert("请选择退款原因");
            return false;
        }
        var data = $form.serialize();
        $.post(href, data, function(data) {
            if (data.code == 0 && data.url) {
                window.alert('申请成功！');
                window.setTimeout(function() {
                    window.location.href = data.url;
                }, 500);
                return false;
            }  
            window.alert(data.error);
            
        }, 'json');
        return false;
    });



})