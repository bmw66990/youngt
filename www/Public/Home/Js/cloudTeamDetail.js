

function wqkjxx_html_to(data) {
    $('#kjxx_cont').html($("#team-wqkjxx-tmpl").tmpl(data));
    $(".pernumber").val("第" + data.periods_number + "期")
    $(".pernumber").attr('now_periods_number_id', data.id)
    return false;
}


$(function() {

    var _init = function() {

        var tid = $('#sd_comment').attr('team_id');
        //内容切换卡
        var $oClickA = $(".con-nav").find("a");
        var $oDiv = $(".product-con>div");
        for (var i = 0; i < $oClickA.length; i++) {
            (function(i) {
                $oClickA[i].onclick = function() {
                    $oDiv.siblings().css("display", "none").eq(i).css("display", "block");
                }
            })(i)
        }
        //计算进度条
        var $nd = ($("#partic").text() / $("#allper").text()) * 100;
        $(".progress label").css("width", $nd + "%");

        // 异步获取评论
        var href = $('#sd_comment').attr('load_href');
        $.get(href, {tid: tid, show_page: 'could_shoping_detail_comment_list',_t:Date.parse(new Date())}, function(res) {
            $('#sd_comment').html(res);
        });


        // 往期中奖轮播
        var href = $('#wqkjxx_pernumber').attr('load_href');
        if (href) {
            $.get(href, {tid: tid,_t:Date.parse(new Date())}, function(data) {
                
                if (data.code && data.code != 0) {
                    $('#wqkjxx_con').html("<a target='_blank' href='"+$('#wqkjxx_con').attr('load_default_href')+"'><img style='margin-left:-11px;' src='"+$('#wqkjxx_con').attr('load_image_url')+"'/></a>");
                    return false;
                }
                var $start = data['data']['start_data'] || {};
                if ($start.id) {
                    wqkjxx_html_to($start);
                }

                $(".pre").die().live('click', function() {
                    var now_periods_number_id = $('#wqkjxx_pernumber').attr('now_periods_number_id');
                    if (!now_periods_number_id) {
                        return false;
                    }
                    var prev_id = data['data']['list'][now_periods_number_id]['prev'] || 0;
                    if (!prev_id) {
                        return false;
                    }
                    var prev_data = data['data']['list'][prev_id] || {};
                    if (!prev_data) {
                        return false;
                    }
                    wqkjxx_html_to(prev_data);
                    return false;
                });
                //下一期
                $(".next").die().live('click', function() {
                    var now_periods_number_id = $('#wqkjxx_pernumber').attr('now_periods_number_id');
                    if (!now_periods_number_id) {
                        return false;
                    }
                    var next_id = data['data']['list'][now_periods_number_id]['next'] || 0;
                    if (!next_id) {
                        return false;
                    }
                    var next_data = data['data']['list'][next_id] || {};
                    if (!next_data) {
                        return false;
                    }
                    wqkjxx_html_to(next_data);
                    return false;
                });
                return false;

            }, 'json')

        }


    }();
    $(".close").die().live('click', function() {
        $("#bombox").css("display", "none");
    });
    $("#search-all").die().live('click', function() {
        $("#bombox").css("display", "block");
    });
    // 点击评价分页异步获取
    $('.pagenumber  a').die().live('click', function() {
        var $this = $(this);
        var $href = $this.attr('href').replace('Team/detail', 'Team/getComments');
        if ($this.hasClass('overframe')) {
            return false;
        }
        $this.removeClass('overframe');
        $.get($href, {}, function(res) {
            $('#sd_comment').html(res);
            $this.removeClass('overframe');
            return false;
        });
        return false;
    });

})

