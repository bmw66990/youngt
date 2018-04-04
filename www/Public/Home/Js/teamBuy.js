$(function() {

    // 验证手机弹出层
    $('#get-mobile').fancybox({
        'autoScale': true,
        'scrolling': 'no',
        'transitionIn': 'fade',
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500
    });

    // 类型选择
    $('.condbuy').fancybox({
        'autoScale': true,
        'scrolling': 'no',
        'transitionIn': 'fade',
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500
    });

    // 绑定快速登录窗口
    $("#btn-buy-no-login-submit").click(function() {
        $('<a href="' + $base_url + '/Public/popLogin">登录窗口</a>').fancybox({
            'autoScale': false,
            'scrolling': 'no',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 360,
            'height': 285,
            'type': 'iframe'
        }).click();
    });

    //换帮手机号
    $("#facbox").fancybox({
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

    // 获取手机验证码
    $('#get-code').die().live('click', function() {

        var $this = $(this);
        var mobile = $('#mobile').val();
        var curCount = 120;
        var timeId = null;
        if ($.trim(mobile) == '') {
            $('#code-msg').text('请输入手机号!');
            return false;
        }

        // 检查手机号是否已经绑定
        var checkMobile = function(cb) {
            $('#mobile').attr('disabled', 'true');
            $('#mobile-waitting').show();
            var href = $base_url + '/team/checkMobile';
            $.post(href, {mobile: mobile}, function(res) {
                $('#mobile-waitting').hide();
                if (res.code < 0 && res.error) {
                    $('#mobile').attr('disabled', false);
                    $('#mobile-msg').text(res.error);
                    return false;
                }
                if (res.code > 0 && res.error) {
                    $('#mobile-msg').text(res.error);
                    $this.hide();
                    $('#go-on').die().live('click', function() {
                        $('#mobile-msg').empty();
                        $('#reset-mobile').hide();
                        $(this).hide();
                        $this.show();
                        cb();
                    });
                    $('#go-on').show();
                    $('#reset-mobile').click(function() {
                        $('#mobile').removeAttr('disabled').val('');
                        $('#mobile-msg').empty();
                        $('#mobile').focus();
                        $(this).hide();
                        $('#go-on').hide();
                        $this.show();
                        $this.val("获取验证码");
                    });
                    $('#reset-mobile').show();
                    return false;
                }
                cb();
            });
        }

        // 获取校验码
        checkMobile(function() {
            $("#get-code").button({disabled: true});//jqueryui设置button禁用样式	
            var href = $base_url + '/team/mobileVerify';
            $.post(href, {mobile: mobile, action: 'pcbindmobile'}, function(res) {
                if (res.code != 0 && res.error) {
                    $('#mobile-msg').text(res.error);
                    return false;
                }
                var SetRemainTime = function() {
                    $this.val(curCount-- + "秒后重新获取验证码");
                    if (curCount <= 0) {
                        $("#get-code").button({disabled: false});//jqueryui设置button可用样式
                        window.clearInterval(timeId);//停止计时器
                        $this.val("重新获取验证码");
                    }
                }
                //设置button效果，开始计时
                timeId = window.setInterval(SetRemainTime, 1000); //启动计时器，1秒执行一次
                return false;
            }, 'json');
        });
        return false;
    });

    // 点击校验
    $('#check-code').die().live('click', function() {
        var mobile = $.trim($('#mobile').val());
        var vCode = $.trim($('#code').val());

        if (!mobile) {
            $('#code-msg').text('请输入手机号!');
            return false;
        }
        if (!vCode) {
            $('#code-msg').text('请输入验证码!');
            return false;
        }

        var href = $base_url + '/team/checkMobileCode';
        var data = {mobile: mobile, vCode: vCode, action: 'pcbindmobile'};
        $('#mobile-waitting').show();
        $.post(href, data, function(res) {
            $('#mobile-waitting').hide();
            if (res.code != 0 && res.error) {
                $('#code-msg').text(res.error);
                return false;
            }
            $.fancybox.close();
            window.location.reload();
        }, 'json');
        return false;
    });

    // 类型选择
    $('#condbuy button').die().live('click', function() {
        var close = true;
        var cond = new Array();
        $('#condbuy select').each(function(i) {
            if ($(this).find("option:selected").val() == '0') {
                $(this).css("border", "1px solid red");
                close = false;
                return false;
            } else {
                $(this).css("border", "none");
                cond[i] = $(this).find("option:selected").val();
            }
        });
        if (close != false) {
            $('.condbuy').text(cond.join('|'));
            $("#deal-buy-form textarea[name='condbuy']").val(cond.join('@'));
            $.fancybox.close();
        }
    });
    
    /*类型选择 end*/

    var setGetCookie = function(val) {
        var tid = $("#deal-buy-form input[name='tid']").val();
        var key = 'quantity_' + tid;
        if (!$.trim(val)) {
            return $.cookie(key);
        }
        return $.cookie('quantity_' + tid, val)

    }

    var tatal = function() {
        var quantity = parseInt($('#deal-buy-quantity').val());
        var text = quantity * $('#deal-buy-price').text();
        $('#deal-buy-total').text(text.toFixed(2));
        $('#deal-buy-total-t').text(text.toFixed(2));
        try {
            _BFD.BFD_INFO.cart_items[0][3] = setGetCookie();
        } catch (e) {

        }
    };
    var free = function() {
        
        var fare_price = Number($('#deal-buy-fare-t').html());
        if(fare_price && fare_price>0){
            var totalmoney = Number($('#deal-buy-total-t').html()) + Number(fare_price);
            $('#deal-buy-total-t').text(totalmoney.toFixed(2));
        }
        
        return false;
        
        var num = $('#deal-buy-quantity').val();
        var freenum = $('#deal-buy-quantity').attr('farefree');
        var freenum = $('#deal-buy-fare-t').html('farefree');
        var check = Number($('.selected').find('.detail').html());
        if (parseInt(num) >= parseInt(freenum) || parseInt(freenum) == 1) {
            if (freenum == 0 && check) {
                $('#discount').show();
                $('#express_money').text(check.toFixed(2));
                var totalmoney = Number($('#deal-buy-total-t').html()) + Number(check);
                $('#deal-buy-total-t').text(totalmoney.toFixed(2));
            } else {
                $('#discount').hide();
            }
        } else {
            $('#discount').show();
            $('#express_money').text(check.toFixed(2));
            var totalmoney = Number($('#deal-buy-total-t').html()) + Number(check);
            $('#deal-buy-total-t').text(totalmoney.toFixed(2));
        }
        return false;
    };

    var setQuantity = function() {
        var quantity = $('#deal-buy-quantity').val();
        quantity = isNaN(parseInt(quantity)) ? 1 : parseInt(quantity);
        $('a.minus').show();
        $('a.plus').show();
        if (quantity < 1) {
            quantity = 1;
            $('a.minus').hide();
        } else if (quantity > 500) {
            quantity = 500;
            $('a.plus').hide();
            $('#error-con').html($("#error-top-tmpl").tmpl({error: '最大购买份数不能超过500'}));
        }
        $('#deal-buy-quantity').attr('value', quantity);
        setGetCookie(quantity);
        return false;
    }

    $('#deal-buy-quantity').die().live('keyup', function() {
        $('#error-con').html('');
        setQuantity();
        tatal();
        free();
    });

    var _setFreeInit = function() {

        $("#verify-mobile input[type='button']").button({disabled: false});

        // 触发该事件,用于显示隐藏 数量输入框中左右加减号
        setQuantity();

        // 计算总金额
        try {
            var $val = setGetCookie();
            if ($val) {
                $('#deal-buy-quantity').val($val);
            }

            // 计算总金额
            tatal();
            free();
        } catch (e) {

        }
        
        // 如果没有选择地址  则默认选第一个
        if(!$("#address-list-content input[name='address_id']:checked").val()){
            $("#address-list-content input:radio[name='address_id']:first").click();
        }
    }();
    $("#address-list-content input:radio[name='address_id']").die().live('change',function(){
      var $selectValue = $("input[name='address_id']:checked").val();
      if($selectValue=="newaddress"){
        $('#pro_city').show();
      }
      else{
        $('#pro_city').hide();
      }
    });

    $('a.minus').die().live('click', function() {
        var quantity = parseInt($('#deal-buy-quantity').val());
        quantity = quantity - 1;
        $(this).show();
        $('a.plus').show();
        if (quantity < 1) {
            quantity = 1;
            $(this).hide();
        }
        setGetCookie(quantity);
        $('#deal-buy-quantity').val(quantity);
        tatal();
        free()
    });


    $('a.plus').die().live('click', function() {
        var quantity = parseInt($('#deal-buy-quantity').val());
        quantity = quantity + 1;
        $(this).show();
        $('#error-con').html('');
        $('a.minus').show();
        if (quantity > 500) {
            quantity = 500;
            $(this).hide();
            $('#error-con').html($("#error-top-tmpl").tmpl({error: '最大购买份数不能超过500'}));
        }
        setGetCookie(quantity);
        $('#deal-buy-quantity').val(quantity);
        tatal();
        free()
    });

    $('#newaddr').die().live('click', function() {
        $('#pro_city').show();
    });

    // 订单表单提交验证
    $('#btn-buy-submit').die().live('click', function() {
        var mobile = $.trim($('#phone').val());
        var $from = $(this).parents('form#deal-buy-form');

        if ($('.condbuy') && $('.condbuy').text() == '选择类型') {
            $('.condbuy').click();
            return false;
        }

        if (!mobile) {
            $('#get-mobile').click();
            return false;
        }

        var reg = /^1[34578][\d]{9}$/;
        if (!reg.test(mobile)) {
            $('#error_content').text("请输入正确手机号码");
            return false;
        }

        var href = $from.attr('action') + '?action=checked';
        var data = $from.serialize();
        $.post(href, data, function(res) {
            if (res.code != 0 && res.error) {
                $('#error-con').html($("#error-top-tmpl").tmpl(res));
                return false;
            }
            $from.submit();
            return false;
        }, 'json');
        return false;
    });
});

