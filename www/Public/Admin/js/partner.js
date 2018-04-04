/**
 * Created by daishan on 2015/6/11.
 */
function delPartner(id) {
    var state = confirm('你确定要删除该商家吗？');
    if (state) {
        window.location.href = $base_url + "/Partner/delPartner/id/" + id;
    }
}

/**
 * 城市改变时获取商圈
 * @param city_id
 */
function getDistrict(city_id) {
    if (city_id) {
        var url = $base_url + '/Partner/ajaxChangeData/city_id/' + city_id;
        $.get(url, {}, function(data) {
            if (data.status == 1) {
                if (data.data) {
                    $('#district').empty();
                    $('#station').empty().append(" <option value='0'>-选择子商圈-</option>");
                    $("#ajaxData-tmpl").tmpl(data).appendTo('#district');
                }
            } else {
                $('#message-top-tmpl').tmpl(data).appendTo('#message-con');
            }
        });
    }
}

/**
 * 商圈改变时获取子商圈
 * @param city_id
 */
function getStation(zone_id) {
    if (zone_id) {
        var url = $base_url + '/Partner/ajaxChangeData/zone_id/' + zone_id;
        $.get(url, {}, function(data) {
            if (data.status == 1) {
                if (data.data) {
                    $('#station').empty();
                    $("#ajaxData-tmpl").tmpl(data).appendTo('#station');
                }
            } else {
                $('#message-top-tmpl').tmpl(data).appendTo('#message-con');
            }
        });
    }
}

/**
 * 获取地图坐标
 */
function getMap(lnglat) {
    var url = $base_url + '/Partner/getMap/lnglat/' + lnglat;
    popup(url, 800, 550);
}

/**
 * 清空地图坐标
 */
function clearMap() {
    $("input[name=longlat]").val('');
}

function delDingZuo(id) {
    var state = confirm('你确定要删除该订座信息吗？');
    if (state) {
        window.location.href = $base_url + "/DingZuo/delete/id/" + id;
    }
}

function partner(obj, id) {
    if (id == 0) {
        $("#message-con").html($('#message-top-tmpl').tmpl({error: '请选择城市'}));
        return false;
    }
    var url = $base_url + "/Team/partner/city_id/" + id;
    $('<a href="' + url + '"></a>').fancybox({
        'autoScale': false,
        'scrolling': 'yes',
        'transitionIn': 'fade',
        'centerOnScroll': true,
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500,
        'width': 600,
        'height': 700,
        'type': 'iframe',
    }).click();
}
$(function() {
    
    // 商家表单添加编辑非法字段校验
    $('input#partner-add-edit-btn').die().live('click',function(){
        var $this = $(this);
        var $form = $this.parents('form#partner-add-edit-form');
        var $href = $form.attr('check_url');
        var $data = $form.serialize();
        
        if ($this.hasClass('disabled')) {
            return false;
        }
        
        $('#message-con').html('');
        var btn_html = $this.val();
        $this.addClass('disabled');
        $this.val('正在校验...');
        $.post($href,$data,function(res){
            $this.removeClass('disabled');
            $this.val(btn_html);
            if(res.code!=0 && res.error){
                $('#message-con').html($('#message-top-tmpl').tmpl(res));
                return false;
            }
            $this.val('正在提交...');
            $form.submit();
            return false;
        });
        return false;
    });



    // 商家团单列表 关键字
    $('form#partner-team-list-form #query-key').die().live('blur', function() {
        var query_key = $(this).val() || '' ;
        var query_key = $.trim(query_key);  
        var partner_id = $(this).attr('partner_id')

        var $partner_select_option = $('form#partner-team-list-form').find('#partner_id').find('option');
        if (!query_key && !partner_id) {
            return false;
        }
        if ($(this).attr('partner_id')) {
             $(this).attr('partner_id', '');
        }
        $('#message-con').html('');
        var flag = false;
        for (var i = 0; i < $partner_select_option.length; i++) {
            var op_id = $($partner_select_option[i]).attr('value');
            var op_val = $.trim($($partner_select_option[i]).html());
            if (!op_id) {
                continue;
            }
            if (query_key && (op_val == query_key || op_val.indexOf(query_key) >= 0)) {
                flag = true;
                $partner_select_option.attr('selected', false);
                $($partner_select_option[i]).attr('selected', true);
                break;
            }
            
            if (partner_id && op_id == partner_id) {
                flag = true;
                $partner_select_option.attr('selected', false);
                $($partner_select_option[i]).attr('selected', true);
                break;
            }
        }
        if (!flag) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '没有找到对应商家！'}));
        }
        return false;
    });

    // 商家团单列表 根据选择的城市 返回商家
    $('form#partner-team-list-form #city_id').die().live('change', function() {
        var city_id = $(this).val();
        var href = $(this).attr('load_href');
        var partner_obj = $('form#partner-team-list-form').find('#partner_id');
        
        if(!city_id){
            return false;
        }

        partner_obj.html("<option value=''>--请选择商家--</option>");
        $('#message-con').html($('#message-top-tmpl').tmpl({info: '正在加载城市对应的商家，请稍后...'}));
        $.post(href, {city_id: city_id}, function(res) {
            $('#message-con').html('');
            if (res.code == 0 && res.data) {
                var option_arr = [];
                option_arr.push("<option value=''>--请选择商家--</option>");
                for (var i = 0; i < res.data.length; i++) {
                    var option_str = "<option value='" + res.data[i].partner_id + "'>" + res.data[i].partner_title + "</option>";
                    option_arr.push(option_str);
                }
                partner_obj.html(option_arr.join(''));
            }
            $('form#partner-team-list-form #query-key').blur();
            return false;
        }, 'json');
        return false;
    });
    $('form#partner-team-list-form #city_id').change();
});