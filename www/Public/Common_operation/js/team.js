function getSubCate(obj) {
    var id = $(obj).val();
    $.get($(obj).attr('url'), {id: id}, function(data) {
        $("#subcate").html(data.html);
    });
}

function partner(obj, id) {
    if (!id) {
        $("#message-con").html($('#message-top-tmpl').tmpl({error: '请选择城市'}));
        return false;
    }
    var url = $base_url + "/Team/partner/city_id/" + id;
    var title = $(obj).attr("data-title");
    layer.open({
        type: 2,
        title: title,
        offset: "10%",
        shadeClose: true,
        shade: 0.8,
        content: url,
    });
    return false;
}

function getActivityList(city_id) {
    var $select_activities_id = $('select#select-activities-id');
    var href = $select_activities_id.attr('load_href');
    var activities_id = $select_activities_id.attr('activities_id');
    if (!city_id) {
        return false;
    }

    $select_activities_id.html("<option value='0'>不参加</option>");
    $.post(href, {city_id: city_id}, function(res) {
        if (res.code == 0 && res.data) {
            var option_arr = [];
            option_arr.push("<option value='0'>不参加</option>");
            for (var i = 0; i < res.data.length; i++) {
                var option_str = "<option value='" + res.data[i].id + "'>" + res.data[i].title + "</option>";
                if (activities_id && activities_id == res.data[i].id) {
                    option_str = "<option value='" + res.data[i].id + "' selected='selected'>" + res.data[i].title + "</option>";
                }
                option_arr.push(option_str);
            }
            $select_activities_id.html(option_arr.join(''));
        }
        return false;
    }, 'json');
    return false;
}

function showTeamAttr() {
    $("#timelimit,#limited,#newuser,#goods,#limited_timelimit,#goods_fare").hide();
    //$("#timelimit,#limited,#goods").hide();
    $("input[name='flv']").attr('disable', true).hide();
    $("input[name=max_number]").attr('readonly', false)
    $("select[name=group_id]").attr('disabled', false).trigger('change');
    $("#add-group-id").remove();
    if ($("input[name=delivery]").attr('disabled')) {
        $("input[name=delivery]").attr('disabled', false);
        $("input[value=coupon]").attr('checked', true);
    }

    // 显示多套餐
    $('#promotion_con').show();
    $('#team_market_price').show();
    $('#team_credit').show();

    //有效期
    $('#expire_time_con').show();

    // 云购
    $('#team_limit_where').show();
    $('#team_product').show();
    $('#team_max_periods_number').hide();
    $('#team_allowrefund').show();
    $('#team_notice').show();
    $('#team_userreview').show();
    $('#team_systemreview').show();
    $('#team_activities_id').show();

    $('#team_pre_number').show();
    $('#team_max_number').show();
}


function showTypeItem(item) {

    // 显示 对应属性
    showTeamAttr();

    switch (item) {
        case 'limited':
            $("input[name='flv']").attr('disable', false);
            $("#limited").show();
            $("#limited_timelimit").show();
            break;
        case 'timelimit':
            $("input[name='flv']").attr('disable', false);
            $("#timelimit").show();
            $("#limited_timelimit").show();
            break;
        case 'newuser':
            $("#newuser").show();
            break;
        case 'goods':
            $("#goods").show();
            $("#goods_fare").show();
            // 显示多套餐
            $('#promotion_con').hide();

            //有效期
            $('#expire_time_con').hide();
            setGoodsCate();
            if ($("#team-models:checked").val() == 'Y') {
                // $("input[name=max_number]").attr('disabled', true)
                $("input[name=max_number]").attr('readonly', true)
            } else {
                $("input[name=max_number]").val(0).attr('readonly', true)
            }
            break;
        case 'cloud_shopping':
            $('#team_limit_where').hide();
            $('#team_product').hide();
            $('#team_max_periods_number').show();
            $('#team_notice').hide();
            $('#team_allowrefund').hide();
            $('#team_allowrefund').find("input[value=N]").attr('checked', true);
            $('#team_userreview').hide();
            $('#team_systemreview').hide();
            $('#team_activities_id').hide();

            $('#promotion_con').hide();
            $('#team_market_price').hide();
            $('#team_credit').hide();

            $('#team_pre_number').hide();
            $('#team_max_number').hide();
            break;
    }
}

$(function() {
    $('select#city').on('change', function() {
        var city_id = $(this).val();
        getActivityList(city_id);
        return false;
    });

    // 二级分类点击事件
    $('div.form-list a.team-sub-id-btn').on('click', function() {

        var sub_id = $(this).attr('sub_id');
        var href = window.location.href;
        var sub_id_param = "?sub_id=" + sub_id;
        if (href.indexOf('&') > 0) {
            sub_id_param = "&sub_id=" + sub_id;
        }
        if (href.indexOf("sub_id=" + sub_id) <= 0) {
            href = href + sub_id_param;
        }

        $(this).attr('href', href);
        return true;
    });

    // 添加团单
    $("#team-add").on('click', function() {
        var market_price = $("input[name=market_price]").val() - 0;
        var team_price = $("input[name=team_price]").val() - 0;
        var ucaii_price = $("input[name=ucaii_price]").val() - 0;
        var team_team_type = $("#team_team_type").val();

        if (team_team_type != 'cloud_shopping' && market_price < team_price) {
            $("#message-con").html($('#message-top-tmpl').tmpl({error: '市场价必须大于团购价'}));
            return false;
        }
        if (team_price < ucaii_price) {
            $("#message-con").html($('#message-top-tmpl').tmpl({error: '团购价必须大于供货价'}));
            return false;
        }

        var $form = $("#team-form");
        var _this = $(this);
        $(this).attr('disabled', true);
        $.post($form.attr('action'), $form.serialize(), function(data) {
            if (data.status == 1) {
                $("#message-con").html($('#message-top-tmpl').tmpl({success: data.info}));
                window.location.href = $base_url + '/Team/add';
            } else {
                $("#message-con").html($('#message-top-tmpl').tmpl({error: data.info}));
            }
            _this.removeAttr('disabled');
        });
    });


    //编辑属性选择框状态设置
    if ($("#team-models").length == 1 && $("#team-models")[0].checked) {
        $("#team-models-item").show();
        $("input[name=max_number]").attr('readonly', true);
    } else {
        $("input[name=max_number]").attr('readonly', false);
        $("#team-models-item").hide();
    }

    //型号选择
    $("#team-models").on('click',function() {
        $("#team-models-item").toggle();
        if (this.checked) {
            $("input[name=max_number]").attr('readonly', true).val(0);
        } else {
            $("input[name=max_number]").attr('readonly', false).val('');
        }
        return true;
    });

    //选择型号计算总数
    $(".team-models-item-num").on('blur', function() {
        getTeamTotal();
    });

    //添加型号
    $("#add-team-models-item").on('click', function() {
        var html = '<p><label>型号：</label> <input type="hidden" name="attr_id[]" value="0"><input type="text" name="attr_item[]"  style="width:30%;" class="form-control"> <label>库存：</label><input type="text" name="attr_num[]" value="0"  style="width:25%;"  class="form-control team-models-item-num">&nbsp;&nbsp; <a href="javascript:;" class="tx-green remove-team-models-item">删除</a></p>'
        $(this).parent().prev().after(html);
        return false;
    });

    //删除型号
    $('div.wrapper-content').on('click','.remove-team-models-item',function(){
        if ($(".remove-team-models-item").length <= 1) {
            $("#team-models").trigger('click');
        } else {
            $(this).parent().remove();
            getTeamTotal();
        }
        return  false;
    })
//    $(".remove-team-models-item").on('click', function() {
//        if ($(".remove-team-models-item").length <= 1) {
//            $("#team-models").trigger('click');
//        } else {
//            $(this).parent().remove();
//            getTeamTotal();
//        }
//        return  false;
//    });

    //团单属性检查
    function checkTeamAttr() {
        var ischeck = $("input[name=is_optional_model]").val();
        if (ischeck == 'Y') {
            //check
        }
    }

    //计算总数
    function getTeamTotal() {
        var total = 0;
        $(".team-models-item-num").each(function() {
            total += parseInt($(this).val());
        });
        $("input[name=max_number]").val(total);
    }

})

//邮购选中分类
function setGoodsCate() {
    $("select[name=group_id]").val(16).attr('disabled', true).trigger('change').after('<input type="hidden" id="add-group-id" name="group_id" value="16">');
    $("input[name=delivery]").attr('disabled', true);
    $("input[value=express]").attr('checked', true);
}