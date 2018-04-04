$(function() {
    if ($(".delay,.dateset").fancybox) {
        $(".delay,.dateset").fancybox({
            'autoScale': false,
            'scrolling': 'no',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 700,
            'height': 450,
            'type': 'iframe'
        });
    }
    if ($("#endTime").datepicker) {
        $("#endTime").datepicker({
            altField: "#endTime",
            altFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true,
            monthNamesShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
            dayNamesMin: ["日", "一", "二", "三", "四", "五", "六"]
        });
        $("#expireTime").datepicker({
            altField: "#expireTime",
            altFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true,
            monthNamesShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
            dayNamesMin: ["日", "一", "二", "三", "四", "五", "六"]
        });
    }

    // 提交延期 和 特殊修改
    $("#affirm").die().live('click', function() {
        var $href = $(this).attr('href');
        var $from = $(this).parents('form#team-manage-form');
        var $data = $from.serialize();
        $.post($href, $data, function(res) {
            if (res.code && res.code != 0 && res.error) {
                parent.window.alert(res.error);
            } else {
                parent.window.alert('操作成功！');
            }
            parent.window.$.fancybox.close();
            parent.window.location.reload();
            return false;
        }, 'json');
        return false;
    })
    $('#out').die().live('click', function() {
        parent.window.$.fancybox.close();
        return false;
    });

    // 申请上线提示
    $("#team-manage-list a.online").die().live('click', function() {
        var $dbUser = $('#team-manage-list').attr('lang').split('|');
        var $phone = $dbUser.pop();
        var $username = $dbUser.pop();
        var $confire = "请联系业务员：" + $username + "，联系电话：" + $phone;
        window.confirm($confire);
        return false;
    });
    
    $('.btn-delete-activities-team').die().live('click',function(){
         var $this = $(this);
        var href = $this.attr('href');
        var confirm_tip = $this.attr('confirm_tip');

        if ($this.hasClass('disabled')) {
            return false;
        }

        var tip_res = window.confirm(confirm_tip);
        if (!tip_res) {
            return false;
        }
        var btn_html = $this.html();
        $this.addClass('disabled');
        $this.html('正在处理...');
        $.get(href, {}, function(res) {
            $this.removeClass('disabled');
            $this.html(btn_html);
            if (res.error && res.code && res.code != 0) {
                window.alert(res.error);
                return false;
            }
            window.alert('操作成功！');
            window.location.reload();
        }, 'json')

        return false;
    });
})