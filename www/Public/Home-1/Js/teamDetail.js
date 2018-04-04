$(function() {


    // 设置团单倒计时
    var setTeamTime = function(now_state, remain) {
        var $dealTimeCon = $('#deal-time-con');
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
            var html = "剩余时间：" + day + "天" + hour + "时" + min + "分" + sec + "秒";
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

        // 签到
        var href = $base_url + "/Public/checkDaysign";
        $.post(href, {}, function(data) {
            $('#checkdaysign').html(data.content);
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
            $("#team-state-tmpl").tmpl(res.data).appendTo('#team-state-con');
            $("#team-j-qg-tmpl").tmpl(res.data).appendTo('#j-qg');
            $("#team-deal-buy-bottom-tmpl").tmpl(res.data).appendTo('#team-deal-buy-bottom');

            // 设置倒计时
            if (res.data.now_state && res.data.remain) {
                setTeamTime(res.data.now_state, res.data.remain);
            }

        }, 'json');

        // 异步获取评论
        var href = $base_url + 'Team/getComments';
        $.get(href, {tid: tid}, function(res) {
            $('.ratelist-content').html(res);
        });
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
        'height': 200,
    });

    // 点击评价分页异步获取
    $('.pagination a').die().live('click', function() {
        var $this = $(this);
        var $review = $('.review-list');
        var $href = $this.attr('href').replace('Team/detail', 'Team/getComments');
        if ($review.hasClass('overframe')) {
            return false;
        }
        $review.removeClass('overframe');
        $.get($href, {}, function(res) {
            $('.ratelist-content').html(res);
            $review.removeClass('overframe');
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

    // 点击签到
    $('#daysign').die().live('click', function() {
        $.post($base_url + "/Public/daysign", function(data) {
            if (data.state == 1) {
                $('#login').click();
            } else if (data.state == 3) {
                alert('签到成功');
                $('#checkdaysign').html(data.content);
            } else if (data.state == 2) {
                alert('今天已经签过到了，请明天再签');
            }
        }, 'json');
        return false;
    });

    // 滚动条监听事件
    $(window).scroll(function() {
        var scrollTop = document.documentElement.scrollTop || window.pageYOffset || document.body.scrollTop
        if (scrollTop > 1250) {
            $("#yt-content-navbar").addClass("ytfixed");
            $(".j-qg").show();
        } else {
            $("#yt-content-navbar").removeClass("ytfixed");
            $(".j-qg").hide();
        }
        return false;
    })
    $(".content-navbar ul li").die().live('click', function() {
        $(this).addClass("content-navbar__item--current").siblings().removeClass("content-navbar__item--current");
    });
})