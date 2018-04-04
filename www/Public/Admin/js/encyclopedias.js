
laydate.skin('molv');
if ($('#start').length > 0) {
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
    laydate(start);
}

if ($('#end').length > 0) {
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
    laydate(end);
}


$(function() {
// 新闻公告 添加，修改
    $('#news-notice-operation-btn').die().live('click', function() {
        var $this = $(this);
        var $form = $this.parents('form#news-notice-operation-form')
        var title = $form.find('#title').val();
        var city_id = $form.find('#city_id').val();
        var type = $form.find('#type').val();
        var content = $form.find('textarea.content').val();
        var order_sort = $form.find('#order_sort').val();
        var begin_time = $form.find('#start').val();
        if (!$.trim(title)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '标题不能为空！'}));
            return false;
        }
        if (!$.trim(city_id)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择城市！'}));
            return false;
        }
        if (!$.trim(type)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择类型！'}));
            return false;
        }
        if (!$.trim(content)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '内容不能为空！'}));
            return false;
        }
        if (!$.trim(order_sort)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '排序不能为空！'}));
            return false;
        }
        if (!$.trim(begin_time)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '时间不能为空！'}));
            return false;
        }
        $form.submit();
        return false;
    });

    // 新闻删除
    $('.notice-delete').die().live('click', function() {
        var $this = $(this);
        var href = $this.attr('href');
        if ($this.hasClass('disabled')) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '正在处理，请稍等！'}));
            return false;
        }
        var confirm_tip = $this.attr('confirm_tip');
        var tip_res = window.confirm(confirm_tip);
        if (!tip_res) {
            return false;
        }
        $this.addClass('disabled');
        $.post(href, {}, function(res) {
            $this.removeClass('disabled');
            if (res.code && res.code != 0 && res.error) {
                $('#message-con').html($('#message-top-tmpl').tmpl(res));
                return false;
            }
            $('#message-con').html($('#message-top-tmpl').tmpl({success: '操作成功！'}));
            window.setTimeout(function() {
                window.location.reload();
            }, 300);
        }, 'json');
        return false;
    });

    // 公告 添加，修改
    $('#substation-notice-operation-btn').die().live('click', function() {
        var $this = $(this);
        var $form = $this.parents('form#substation-notice-operation-form')
        var city_id = $form.find('#city_id').val();
        var content = $form.find('textarea.content').val();
        var begin_time = $form.find('#start').val();
        if (!$.trim(city_id)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择城市！'}));
            return false;
        }

        if (!$.trim(content)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '内容不能为空！'}));
            return false;
        }

        if (!$.trim(begin_time)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '时间不能为空！'}));
            return false;
        }
        $form.submit();
        return false;
    });
    
    // 青团百科 添加，修改
    $('#youngt-encyclopedias-operation-btn').die().live('click', function() {
        var $this = $(this);
        var $form = $this.parents('form#youngt-encyclopedias-operation-form')
        var title = $form.find('#title').val();
        var type = $form.find('#type_id').val();
        var content = $form.find('textarea.content').val();
        var order_sort = $form.find('#order_sort').val();
        var begin_time = $form.find('#start').val();
        if (!$.trim(title)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '标题不能为空！'}));
            return false;
        }
        if (!$.trim(type)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '请选择类型！'}));
            return false;
        }
        if (!$.trim(content)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '内容不能为空！'}));
            return false;
        }
        if (!$.trim(order_sort)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '排序不能为空！'}));
            return false;
        }
        if (!$.trim(begin_time)) {
            $('#message-con').html($('#message-top-tmpl').tmpl({error: '时间不能为空！'}));
            return false;
        }
        $form.submit();
        return false;
    });

    /**
     * 分类添加
     */
    $('#encyclopedias_type_add').die().live('click', function() {
        var $this = $(this);
        var encyclopedias_name = $('#encyclopedias_name').val();
        var encyclopedias_order_sort = $('#encyclopedias_order_sort').val();
        var show_plat = $('#show_plat').val();
        var href = $this.attr('load_href');
        if ($this.hasClass('disabled')) {
            parent.window.alert('正在处理，请稍等！');
            return false;
        }
        if (!$.trim(encyclopedias_name)) {
            parent.window.alert('类型名称不能为空！');
            return false;
        }
        if (!$.trim(encyclopedias_order_sort)) {
            parent.window.alert('排序不能为空！');
            return false;
        }
         if (!$.trim(show_plat)) {
            parent.window.alert('显示平台不能为空！');
            return false;
        }
        $this.addClass('disabled');
        $.post(href, {name: encyclopedias_name, order_sort: encyclopedias_order_sort,show_plat:show_plat}, function(res) {
            $this.removeClass('disabled');
            if (res.code && res.code != 0 && res.error) {
                parent.window.alert(res.error);
                return false;
            }
            parent.window.alert('添加成功！');
            window.location.reload();
        }, 'json');
        return false;
    });
})