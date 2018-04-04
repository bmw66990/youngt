$(function() {

    // 备注提交
    $('span#do-refund-adminremark').die().live('click', function() {
        var $this = $(this);
        var remark = $(this).parents('div.box').find('textarea#order_adminremark').val();
        var href = $this.attr('load_href');
        if ($this.hasClass('disabled')) {
            return false;
        }
        if (!$.trim(remark)) {
            parent.window.alert('备注不能为空！');
            return false;
        }
        $this.addClass('disabled');
        $this.html('正在处理...');
        $.post(href, {remark: remark}, function(res) {
            $this.removeClass('disabled');
            $this.html('确定');
            if (res.code && res.code != 0 && res.error) {
                parent.window.alert(res.error);
                return false;
            }
            parent.window.alert('修改备注信息成功！');
            parent.window.location.reload();
        }, 'json');
        return false;
    });

    // 处理方式提交
    $('span#refund-deal').die().live('click', function() {
        var $this = $(this);
        var $parent = $(this).parents('div.box');
        var $checked = $parent.find('div#coupon-list-con').find('input.coupon-one');
        var refund_type = $parent.find('select#refund-type').val();
        var href = $(this).attr('load_href');
        if ($this.hasClass('disabled')) {
            return false;
        }
        var check = [];
        var coupon_ids = '';
        if (!$.trim(refund_type)) {
            parent.window.alert('请选择退款处理的方式！');
            return false;
        }

        for (var i = 0; i < $checked.length; i++) {
            var $checkone = $($checked[i]);
            if ($checkone.attr('checked')) {
                check.push($checkone.val())
            }
        }
        if (check.length > 0) {
            coupon_ids = check.join(',');
        }
        if (!$.trim(coupon_ids) && $.trim(refund_type) != 'norefund' && $checked.length > 0) {
            parent.window.alert('请选择券号！');
            return false;
        }
        $this.addClass('disabled');
        $this.html('正在处理...');
        $.post(href, {refund_type: refund_type, coupon_ids: coupon_ids}, function(res) {
            $this.removeClass('disabled');
            $this.html('确定');
            if (res.code && res.code != 0 && res.error) {
                parent.window.alert(res.error);
                return false;
            }
            parent.window.alert('处理成功！');
            parent.window.location.reload();
        }, 'json');
        return false;
    });

    /**
     * 第三方退款
     */
    $('span#third-party-refund-deal').die().live('click', function() {
        var $this = $(this);
        var href = $(this).attr('load_href');
        if ($this.hasClass('disabled')) {
            return false;
        }

        $this.addClass('disabled');
        $this.html('正在处理...');
        $.post(href, {}, function(res) {
            $this.removeClass('disabled');
            $this.html('确定退款');
            if (res.code && res.code != 0 && res.error) {
                parent.window.alert(res.error);
                return false;
            }
            if (res.code && res.code != 0) {
                parent.window.alert('退款失败！');
                return false;
            }

            parent.window.alert('退款成功！');
            parent.window.location.reload();
        }, 'json');
        return false;
    });
    /**
     * 第三方退款
     */
    $('span#third-party-refund-dealnew').die().live('click', function() {
        var $this = $(this);
        var href = $(this).attr('load_href');
        if ($this.hasClass('disabled')) {
            return false;
        }

        $this.addClass('disabled');
        $this.html('正在处理...');
        $.post(href, {}, function(res) {
            $this.removeClass('disabled');
            $this.html('确定退款');
            if (res.code && res.code != 0 && res.error) {
                parent.window.alert(res.error);
                return false;
            }
            if (res.code && res.code != 0) {
                parent.window.alert('退款失败！');
                return false;
            }

            parent.window.alert('退款成功！');
            parent.window.location.reload();
        }, 'json');
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
    // 支付宝批量下载
    $('#alipay_batch_refund_do').die().live('click', function() {
        var $this = $(this);
        var $checked = $this.parents('div.content').find('div.form-list').find("tr.list").find("input.checkone");
        var check = [];
        var href = $this.attr('href');
        var confirm_tip = $this.attr('confirm_tip');

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
        var order_ids = '';
        if (check.length > 0) {
            order_ids = check.join('_');
        }
        if (!$.trim(order_ids)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择批量操作退款的订单！'}));
            return false;
        }
        
        var operation_type = "?order_ids="+order_ids;
        if (href.indexOf('&') > 0) {
            operation_type = "&order_ids="+order_ids;
        }
        href = href + operation_type;
        $(this).attr('href', href);
        return true;
    });
    
    // 批量结算操作 批量审核操作
    $('#credit_batch_refund_do').die().live('click', function() {
        var $this = $(this);
        var $checked = $this.parents('div.content').find('div.form-list').find("tr.list").find("input.checkone");
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
        var order_ids = '';
        if (check.length > 0) {
            order_ids = check.join(',');
        }
        if (!$.trim(order_ids)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择批量操作的数据！'}));
            return false;
        }
        var btn_html = $this.html();
        $this.addClass('disabled');
        $this.html('正在处理...');
        $.post(href, {order_ids: order_ids}, function(res) {
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

function withDown(id) {
    var state = confirm('你确定要给此用户结算吗？');
    if (state) {
        window.location.href = $base_url + "/CustomerService/doWithDown/id/" + id;
    }
}

