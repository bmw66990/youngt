$(function() {


    // 设置团单倒计时
    var setTeamTime = function(now_state, remain) {
        var $dealTimeCon = $('#last_time');
        if (!now_state || !remain) {
            return false;
        }

        var day, hour, min, sec;
        var times = null;
        // 倒计时
        var callback = function() {
            day = parseInt(remain / 86400);
            hour = parseInt(remain % 86400 / 3600);
            min = parseInt(remain % 86400 % 3600 / 60);
            sec = parseInt(remain % 86400 % 60);
            if (hour < 10) {
                hour = '0' + hour;
            }
            if (min < 10) {
                min = '0' + min;
            }
            if (sec < 10) {
                sec = '0' + sec;
            }
            var html = "剩余<label class='t_orange'>" + day + "</label>天<label class='t_orange'>" + hour + "</label>小时<label class='t_orange'>" + min + "</label>分钟<label class='t_orange'>" + sec + "</label>秒";
            $dealTimeCon.html(html);
            remain--;
            if (remain <= 0) {
                $dealTimeCon.text("此项目已结束");
                clearInterval(times);
                return false;
            }
        }
        if (now_state == 1) {
            times = setInterval(callback, 1000);
        } else if (remain == 2) {
            $dealTimeCon.text("此项目暂正在审核中");
        } else if (remain == 3) {
            $dealTimeCon.text("此项目暂未开始");
        } else if (remain == 4) {
            $dealTimeCon.text("此项目已结束");
        } else if (remain == 5) {
            $dealTimeCon.text("此项目已卖光");
        }
        return false;
    }

    // 异步设置团单详情相关数据
    var setTeamOtherParam = function() {

        // 设置浏览历史和 点击次数
        var $dealTimeCon = $('#team-state-con');
        var tid = $dealTimeCon.attr('for');
        if (!tid) {
            return false;
        }
        var href = $base_url + 'Team/setTeamOtherParam'
        $.get(href, {tid: tid}, function(res) {
        }, 'json');

    }

    // 异步获取团单详情其他数据
    var getTeamOtherParam = function() {

        var $teamStateCon = $('#team-state-con');
        var tid = $teamStateCon.attr('for');
        if (!tid) {
            return false;
        }

        // 获取团单状态
        var href = $base_url + 'Team/getTeamOtherParam';
        $.post(href, {tid: tid}, function(res) {
            if (res.error && res.code < 0) {
                return false;
            }

            // 填充状态模板
            $('#team-state-con').html($("#team-state-tmpl").tmpl(res.data));
            $teamStateCon.find('.spinnerExample').spinner({value: 1});
            $('#team-j-qg').html($("#team-j-qg-tmpl").tmpl(res.data));
            $('#team-deal-buy-bottom-con').html($("#team-deal-buy-bottom-tmpl").tmpl(res.data));
            $('#collect-show-con').html($("#collect-show-con-tmpl").tmpl(res.data));

            // 设置倒计时
            if (res.data.now_state && res.data.remain) {
                setTeamTime(res.data.now_state, res.data.remain);
            }
            return false;

        }, 'json');

        // 获取历史浏览
        var href = $base_url + 'Public/history';
        $.post(href, {}, function(res) {
            if(res.data && res.data.length>0){
                  $('#team-history-list-tmpl').tmpl(res.data).appendTo('#team-history-list-con');
            }
            return false;
        });

        // 异步获取评论
        var href = $base_url + 'Team/getComments';
        $.get(href, {tid: tid}, function(res) {
            $('#team-pj-detail').html(res);
        });
        

        return false;
    }

    var init = function() {
        // js属性设置
        $('teble tr td').prevAll().css('text-align', 'center');
        $('.detail table tr:eq(0) td').css('background-color', '#f0f0f0');

        // 异步设置团单详情相关数据
        setTeamOtherParam();

        // 异步获取团单详情相关数据
        getTeamOtherParam();

    }();

    $("a#search-path").fancybox({
        'autoScale': false,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe',
        'width': 350,
        'height': 280
    });
    $("a.popwin").fancybox({
        'autoScale': false,
        'transitionIn': 'none',
        'transitionOut': 'none',
        'type': 'iframe'
    });
    $("#login").die().live('click', function() {
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
    $("#order").fancybox({
        'content': '<p id="no-payed"  class="tishi no-payed" style="width:300px;height:100px;line-height:100px;text-align:center;font-size:16px;">很抱歉，购买过本团才能够评价</p>',
        'autoScale': false,
        'scrolling': 'no',
        'transitionIn': 'fade',
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500,
        'width': 360,
        'height': 200
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
            $('#team-pj-detail').html(res);
            $this.removeClass('overframe');
            return false;
        });
        return false;
    });

    // 点击评论中图片
    $('.review-list> li.rate-list__item .show-image-btn').die().live('click', function() {
        var $newImageCon = $(this).parents('li.rate-list__item').find(".show_img");
        var oldImageSrc = $(this).find('img').attr('src');
        var isDisplay = $newImageCon.is(":hidden");
        if (isDisplay) {
            $newImageCon.find('img').attr('src', oldImageSrc);
            $newImageCon.show();
        } else {
            $newImageCon.find('img').attr('src', "");
            $newImageCon.hide();
        }
        return false;
    });

    $(".content-navbar ul li").die().live('click', function() {
        $(this).addClass("content-navbar__item--current").siblings().removeClass("content-navbar__item--current");
    });
    
    // 选择属性
    $('#team_attr_con label.attr_item').die().live('click',function(){
        var $this = $(this);
        var $attr_id_input = $('#team_attr_con').find('input#team-attr-select-id');
        
        $('#team_attr_con').find('label.attr_item').removeClass('curos');
        $this.addClass('curos');
        $attr_id_input.val($this.attr('attr_id'));
        return false;
    });

    // 点击收藏和取消收藏
    $("li#collect-show-con a").die().live('click', function() {
        var href = $(this).attr('load_href');
        if (!href) {
            return false;
        }
        $.get(href, {}, function(res) {
            if (res.code && res.code > 0) {
                // 未登录
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
                return false;
            }
            if(res.code && res.code < 0 && res.error){
                window.alert(res.error);
                return false;
            }
            if(res.data){
                $('#collect-show-con').html($("#collect-show-con-tmpl").tmpl(res.data));
            }
        });
        return false;

    });
})