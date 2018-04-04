
laydate.skin('molv');
var start = {
    elem: '#start',
    format: $('#start').attr('format') || 'YYYY-MM-DD',
    max: '2099-06-16 23:59:59', //最大日期
    istoday: false,
    istime: true,
    choose: function(datas) {
        end.min = datas; //开始日选好后，重置结束日的最小日期
        end.start = datas //将结束日的初始值设定为开始日
    }
};
var end = {
    elem: '#end',
    format: $('#end').attr('format') || 'YYYY-MM-DD',
    max: '2099-06-16 23:59:59',
    istoday: false,
    istime: true,
    choose: function(datas) {
        start.max = datas; //结束日选好后，重置开始日的最大日期
    }
};
laydate(start);
laydate(end);

$(function() {

    var set_menu_init = function() {
        var $localhref = window.location.href.toLowerCase();
        $('.tab-menu>ul>li').each(function(i) {
            var $href = $(this).find('a').attr('href');
            $href = $href.substring(0, $href.indexOf('.')).toLowerCase();
            if ($localhref.indexOf($href) >= 0) {
                $(this).addClass('current');
            }
        });
    }();

    // 审核结款提交
    $('.settlement,.examine,.agent_settlement').die().live('click', function() {
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
                $('#message-con').html($('#message-top-tmpl').tmpl(res));
                return false;
            }
            window.alert('操作成功！');
            window.location.reload();
        }, 'json')

        return false;
    });

    // 复选框选中
    $("tr.list-title input.checkall").die().live('click', function() {
        var $this = $(this);
        var $checkone = $this.parents('table').find('input.checkone');
        $checkone.attr('checked', false);
        if ($this.attr('checked')) {
            $checkone.attr('checked', true);
        }
        return true;
    });

    // 批量结算操作 批量审核操作
    $('#batchSettlement,#batchExamine').die().live('click', function() {
        var $this = $(this);
        var $checked = $this.parents('div.form-list').find("tr.list").find("input.checkone");
        var $select=$("#refund").val();
        var check = [];
        var href = $this.attr('href');
        var confirm_tip = $this.attr('confirm_tip');

        if ($this.hasClass('disabled')) {
            return false;
        }

        // 操作提示
        var tip_res = window.confirm(confirm_tip);
        if (!tip_res) {
            return false;
        }

        $('#message-con').html('');
        for (var i = 0; i < $checked.length; i++) {
            var $checkone = $($checked[i]);
            if ($checkone.attr('checked')) {
                check.push($checkone.val())
            }
        }
        var pay_ids = '';
        if (check.length > 0) {
            pay_ids = check.join(',');
        }
        if (!$.trim($select)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择支付银行卡！'}));
            return false;
        }
        if (!$.trim(pay_ids)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择批量操作的数据！'}));
            return false;
        }
        var btn_html = $this.html();
        $this.addClass('disabled');
        $this.html('正在处理...');
        $.post(href, {pay_id: pay_ids,bank_type:$select}, function(res) {
            $this.removeClass('disabled');
            $this.html(btn_html);
            if (res.code && res.code != 0 && res.error) {
                $('#message-con').html($('#message-top-tmpl').tmpl(res));
                return false;
            }
            window.alert(res.success);
            window.location.reload();
            return false;
        }, 'json');
        return false;
    });

    // 现金支付下载
    $('#cash-payment-btn').die().live('click', function() {
        var href = window.location.href;
        var operation_type = "?operation_type=cashPaymentDownLoad";
        if (href.indexOf('&') > 0) {
            operation_type = "&operation_type=cashPaymentDownLoad";
        }
        href = href + operation_type;
        $(this).attr('href', href);
        return true;
    });

    // 批量结算所有今天商家的款项
    $("#batchSettlementTodayAllPartner").die().live('click', function() {
        var $this = $(this);
        var href = $this.attr('href');
        var confirm_tip = $this.attr('confirm_tip');
        var $select=$("#refund").val();
        if ($this.hasClass('disabled')) {
            return false;
        }

        // 操作提示
        var tip_res = window.confirm(confirm_tip);
        if (!tip_res) {
            return false;
        }
        if (!$.trim($select)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择支付银行卡！'}));
            return false;
        }
        $('#message-con').html('');
        var btn_html = $this.html();
        $this.addClass('disabled');
        $this.html('正在处理...');
        $.post(href, {bank_type:$select}, function(res) {
            $this.removeClass('disabled');
            $this.html(btn_html);
            if (res.code && res.code != 0 && res.error) {
                $('#message-con').html($('#message-top-tmpl').tmpl(res));
                return false;
            }
            window.alert(res.success);
            window.location.reload();
            return false;
        }, 'json');
        return false;
    });
})


