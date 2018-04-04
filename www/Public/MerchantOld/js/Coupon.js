$(function() {

    $(".team").fancybox({
        'autoScale': false,
        'scrolling': 'no',
        'transitionIn': 'fade',
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500,
        'width': 530,
        'height': 505,
        'type': 'iframe'
    });

    //cx_coupon
    $(".cx_coupon").fancybox({
        'autoScale': false,
        'scrolling': 'no',
        'transitionIn': 'fade',
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500,
        'width': 550,
        'height': 290,
        'type': 'iframe'
    });

    // 多行团卷校验输入
    $('.multi_coupon').die().live('keyup', function() {
        var $pwd = ($(this).val()).replace(/ /g, '');
        var $pwdLength = $pwd.length;
        var $next = $(this).next('.multi_coupon');
        if ($pwdLength == 12) {
            if ($next) {
                $next.focus();
                return false;
            }
            $('#selects').focus();
        }
        return false;
    });
    $(".ui-cardnum-show").die().live('focus', function() {
        $(this).die().live('keyup', function() {
            var num = $(this).val();
            num = num.replace(/\s+/g, "");//将输入的内容去掉空格传入ui-cardnum-hide
            num = num.replace(/(.{4})/g, "$1 ");//将ui-cardnum-hide得到的内容每4个字符插入一个空格传回去
            $(this).val(num);
            return false;
        });
        return false;
    });

    // Tab切换
    $('#myTab a').die().live('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
        return false;
    })

    // 单个团卷验证
    $("#ticket").die().live('keydown', function(e) {
        if (e.keyCode != 8) {
            if ($(this).val().length == 4 || $(this).val().length == 9) {
                $(this).val($(this).val() + " ");
            }
        }
        return true;
    });

    $('select#partner_select_id').die().live('change', function() {
        var id = $(this).val();
        window.location.href = $base_url + '/Coupon/index/partner_id/' + id;
        return false;
    });

    // 青团卷密码 校验查询
    $('.coupon-check-info').die().live('click', function() {
        var ticket = $('#ticket').val();
        var $from = $(this).parents('form');
        if (!$.trim(ticket)) {
            window.alert('青团券密码不能为空！');
            return false;
        }

        // 校验团券是否存在
        var checkCoupon = function(cb) {
            var href = $from.attr('checkHref');
            var data = {id: ticket};
            $.post(href, data, function(res) {
                if (res.code != 0 && res.error) {
                    window.alert(res.error);
                    return false;
                }
                cb();
                return false;
            }, 'json')
        }
        // 校验团卷后提交表单
        checkCoupon(function() {
            $from.find('#action').val($(this).attr('name'));
            $from.submit();
            return false;
        });
        return false;
    });

    // 点击校验
    $('button#btn-check-coupon').die().live('click', function() {
        var $this = $(this);
        var $form = $this.parents('form');
        var $action = $this.attr('lang');

        switch ($.trim($action)) {
            case 'one':
                var couponId = $form.find('#coupon_id').val();
                if (!$.trim(couponId)) {
                    window.alert("团卷id不能为空！");
                    return false;
                }
                break;
            case 'multi':
                var num = $form.find('[type=checkbox]:checked').length;
                if (num <= 0) {
                    window.alert("请选择您要验证的券号！");
                    return false;
                }
                break;
            default:
                window.alert("非法操作！");
                return false;
                break;
        }

        // 提交表单
        var checkedSubmit = function(cb) {
            var href = $form.attr('action');
            var data = $form.serialize();
            $.post(href, data, function(res) {
                if (res.code != 0 && res.error) {
                    window.alert(res.error);
                    return false;
                }
                cb();
            }, 'json');
        };

        // 校验完成后获取打印信息
        checkedSubmit(function() {
            $form.find('#action').removeAttr('name').attr('disabled', false);
            $form.submit();
            return false;
        });
        return false;

    });
    $("#checkall").click(function() {
        $(':checkbox').attr('checked', true);
    });

    $("#checkno").click(function() {
        $(':checkbox').attr('checked', false);
    });

    //积分商品查询
    $(':button[name=select-voucher]').click(function(){
       var voucher = $('#voucher[name=id]').val();
        if (!$.trim(voucher)) {
            window.alert('积分商品兑换券不能为空！');
            return false;
        }
        var url = $base_url+'/Coupon/checkPointsVoucher';
        $.post(url,{voucher:voucher},function(data){
            if(data.status == -1){
                alert(data.error);
            }else{
                showVoucher(data.success);
            }
        },'json');
        return false;
    });

    $('#confirm-voucher').die().live('click', function() {
        var voucher = $(this).attr('code');
        var url = $base_url+'/Coupon/consumePointsVoucher';
        $.post(url,{voucher:voucher},function(data){
            if(data.status == -1){
                alert(data.error);
            }else{
                alert('兑换成功');
                showVoucher(data.success);
            }
        },'json')
    });

    var showVoucher = function(data){
        var obj = $('#display-voucher');
        if(obj.find('button')){
            obj.find('button').remove();
        }
        obj.find('.name').html('商品名称：<span style="color: red">'+data.name+'</span>');
        if(data.status == 'Y'){
            obj.find('.time').html('兑换时间：<span style="color: red">'+data.consume_time+'</span>');
        }else{
            obj.find('.time').html('过期时间：<span style="color: red">'+data.expire_time+'</span>');
        }
        obj.find('.confirm_num').html('兑换数量：<span style="color: red">'+data.num+'份</span>')
        obj.find('.total_score').html('使用积分：<span style="color: red">'+data.total_score+'</span>');
        obj.find('.status_name').html('当前状态：<span style="color: red">'+data.status_name+'</span>');
        if(data.status == 'N'){
            obj.append('<div style="text-align: center; padding-top: 20px;"><button type="button" class="u-btn u-btn-c2" code="'+data.code+'" id="confirm-voucher">确定兑换</button></div>');
        }
        obj.show();
    }
})