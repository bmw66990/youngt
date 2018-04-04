$(function() {

    var init = function() {
        if ($(":input[name='paytype']:checked").val() == 'credit') {
            // 余额支付
            $("#pay-form").removeAttr('target');
            $(":input[type='submit']").val('确定付款');
        } else if ($(":input[name='paytype']:checked").val() == 'thirdparty') {
            // 第三方支付
            $('#paytype-list').fadeIn("slow");
            $("#J-pay-total").show();
        } else if ($(":input[name='paytype']:checked").val() == 'freepay') {
            // 免支付
            $("#pay-form").removeAttr('target');
            $(":input[type='submit']").val('免费获取');
        } else {
            $('#paytype-list').fadeIn("slow");
            $("#J-pay-total").show();
            $(":input[type='submit']").val('付款');
        }
    }();

    $(".paytype-list").accordion({
        collapsible: true,
        heightStyle: "content"
    });

    // 余额支付
    $('#radio-credit').die().live('click', function() {
        $('#paytype-list').fadeOut("slow");
        $("#J-pay-total").hide();
        $("#pay-form").removeAttr('target');
        $(":input[type='submit']").val('确定付款');
    });
    // 第三方支付
    $('#paytype-choice').die().live('change', function() {
        $('#paytype-list').toggle("slow");
        $("#J-pay-total").show();
        $("#pay-form").attr('target', '_blank');
        $(":input[type='submit']").val('去付款');
    });

    // 余额部分支付
    $('#check-credit').die().live('click', function() {
        var umoney = Number($('#user-money').text());
        var nmoney = Number($('#need-money').text());
        if ($(this).attr('checked')) {
            $('#totle-money').text(nmoney.toFixed(2));
        } else {
            $('#totle-money').text((umoney + nmoney).toFixed(2));
        }
    });

    // 关闭等待支付
    $('#close-win').die().live('click', function() {
        $.fancybox.close();
    });

    // 提交支付
    $('#pay-form').die().live('submit', function() {
        if ($(":input[name='paytype']:checked").val() == 'thirdparty') {
            if (!$(":input[name='bank_type']:checked").val()) {
                $('<a href="#no-paytype">提示信息</a>')
                        .fancybox({
                            'autoScale': false,
                            'scrolling': 'no',
                            'transitionIn': 'fade',
                            'transitionOut': 'fade',
                            'speedIn': 500,
                            'speedOut': 500,
                            'content': '<p id="no-paytype"  class="tishi no-paytype" style="width:300px;height:100px;line-height:100px;text-align:center;font-size:16px;">请选择支付方式</p>'
                        }).click();
                return false;
            } else {
                if ($("#pay-form").attr('target')) {
                    $('<a href="#wait-pay">等待支付</a>')
                            .fancybox({
                                'autoScale': false,
                                'scrolling': 'no',
                                'transitionIn': 'fade',
                                'transitionOut': 'fade',
                                'speedIn': 500,
                                'speedOut': 500,
                                'width': 360,
                                'height': 430,
                                'hideOnOverlayClick': false,
                                'enableEscapeButton': false,
                                'centerOnScroll': true
                            }).click();
                }
            }
        }
    });


});
