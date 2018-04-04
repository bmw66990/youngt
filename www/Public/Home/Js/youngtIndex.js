/**
 * Created by daishan on 2015/5/15.
 */
$(function() {
    var ISLOGIN;
    var li_Width = 759;
    var team_access = false;
    // 获取购物车数据
    var getTopCartList = function() {
        var href = $base_url + "/Cart/index";
        $.post(href, {}, function(res) {
            $('#gwid').html($('#top-cart-list-tmpl').tmpl(res));
            var num = res.list.length || 0;
            $("#user-cart-nums").text(num);
            return false;
        }, 'json');
    }

    // 获取顶部历史记录
    var getTopHistoryList = function() {
        var href = $base_url + "/Public/history";
        $.post(href, {}, function(res) {
            if (res.data && res.data.length > 0) {
                $('#top-history-list-con').html($('#top-history-list-tmpl').tmpl(res.data));
            }
            return false;
        }, 'json');
    };

    $(function() {
        $("#verify-coupon").fancybox({
            'autoScale': false,
            'scrolling': 'no',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 480,
            'height': 400,
            'type': 'iframe'
        });

        // 初始化执行的数据
        var init = function() {
            var host = window.location.host;
            var city_name_url = $base_url + '/Public/ajaxChangeCity';
            $.get(city_name_url, {host: host}, function(data) {
                if (data.status == 1) {
                    if (data.url && data.url != '') {
                        location.href = data.url;
                        return false;
                    }
                    var cityName = data['city']['name'];
                    var title = '青团生活' + cityName + '站_' + cityName + '本地精品生活指南,' + cityName + '青团网_' + cityName + '团购_外卖_社区生活';
                    var keywords = '青团生活' + cityName + '站_' + cityName + '本地精品生活指南,' + cityName + '青团网_' + cityName + '团购_外卖_社区生活' + cityName + '站 - 本地精品生活指南！为您汇集最全面最优惠的美食娱乐团购打折促销信息！青团网为您精选' + cityName + '内的自助餐、电影票、KTV、美发、足浴特色商家，享尽无敌折扣！'
                    var description = '青团生活' + cityName + '站_' + cityName + '本地精品生活指南,' + cityName + '青团网_' + cityName + '团购_外卖_社区生活 ' + cityName + ',青团网' + cityName + '站,' + cityName + '团购,打折,杨凌打折,优惠券,' + cityName + '优惠券,' + cityName + '本地团购';
                    try {
                        $('title').text(title);
                    } catch (e) {
                        document.title = title;
                    }
                    $('meta[name=keywords]').attr('content', keywords);
                    $('meta[name=description]').attr('content', description);
                    $('#youngtCityName').empty().html(data['city']['name']);
                    /*if(data['city']['id'] == 1){
                     var html = "<span><a target='_blank' href="+$base_url+"/Study/index>毕业季</a></span>"
                     $('#nav-right').append(html);
                     }*/
                    if (data['city']['id'] == 325) {
                        $('#goods-category-content').html($('#goods-category-tmpl').html());
                    }else{
                        $('#goods-category-content').html($('#goods-category-other-tmpl').html());
                    }
                    ajaxGetActiveList();
                    ajaxDistrict();
                    ajaxAroundCity();
                    ajaxAdManage();
                    ajaxIndexTpl();
                } else {
                    var url = $base_url + '/Index/ajaxCityName';
                    $.get(url, '', function(data) {
                        $('#youngtCityName').html(data.name);
                        if (data.id == 1) {
//                            var html = "<span><a target='_blank' href="+$base_url+"/Study/index>毕业季</a></span>"
//                            $('#nav-right').append(html);
                        }
                    });
                    ajaxDistrict();
                    ajaxAroundCity();
                    ajaxAdManage();
                    ajaxIndexTpl();
                }
            }, 'json');

            // 判断是否登录
            var isLogin_url = $base_url + '/Public/isLogin';
            $.get(isLogin_url, function(data) {
                if (data.status == 1) {
                    ISLOGIN = 1;
                    try {
                        _BFD['BFD_INFO']['user_id'] = data.id;
                    } catch (e) {

                    }
                     $('.youngt-register').html("<a href='" + $base_url + "/Member/index'>Hi:" + data.username + "</a><span class='line-row'></span>");
                            $('.youngt-login').html("<a href='" + $base_url + "/Public/logout'>退出</a><span class='line-row'></span>");
                } else {
                    ISLOGIN = 0;
                  $('.youngt-login').html("<a href='" + $base_url + "/Public/login'>登录</a><span class='line-row'></span>");
           $('.youngt-register').html("<a href='" + $base_url + "/Public/register'>注册</a><span class='line-row'></span>");
                }
                if (dfb_common) {
                    dfb_common();
                }

            }, 'json')

            // 获取历史浏览
            if (getTopHistoryList) {
                getTopHistoryList();
            }

            // 获取顶部购物车列表
            if (getTopCartList) {
                getTopCartList();
            }

        }();
    });

    /**
     * 异步获取商圈
     */
    function ajaxDistrict() {
        var url = $base_url + "/Index/ajaxDistrict";
        $.get(url, '', function(data) {
            if (data.status == 1) {
                $("#district-tmpl").tmpl(data).appendTo('#district');
                if (data.count > 9) {
                    $("#district .aMore").hover(function() {
                        $("#district").addClass("demo-class-menu-liup");
                    });
                    $("#district").mouseleave(function() {
                        $("#district").removeClass("demo-class-menu-liup");
                    });
                } else {
                    $("#district .aMore").hide();
                }
            }
        }, 'json');
    }
    
    /**
     * 获取城市活动
     * @returns {undefined}
     */
    function ajaxGetActiveList(){
         var url = $base_url + "/Active/getActivitiesList";
        $.get(url, '', function(res) {
            var href = $base_url + "/Active/index";
            if (res.code == 0 && res.data) {
                var href = href + "?activities_id="+res.data.id;
                var is_show = false;
                if(res.data.is_show=='Y'){
                    is_show=true;
                }
                active_pop(href, is_show);
            }else{
                active_pop(href,false);
            }
            return false;
        }, 'json');
    }

    /**
     * 异步获取周边城市
     */
    function ajaxAroundCity() {
        // 热门城市
        var url = $base_url + "/Index/ajaxAroundCity";
        $.get(url, '', function(data) {
            if (data.status == 1) {
                $('#hotCity').html($("#hotCity-tmpl").tmpl(data));
            }
        }, 'json');
        
        // 新加周边城市
        var url = $base_url + "/Index/ajaxPeripheryCity";
        $.get(url, '', function(res) {
            if (res.code == 0 && res.data) {
                $('#periphery_city').html($("#top-periphery-city-list-tmpl").tmpl(res.data));
            }
        }, 'json');
    }


    
    /**
     * 异步获取模板显示（1 新模板 0 老模板）
     */
    function ajaxIndexTpl() {
        var url = $base_url + "/Index/ajaxIndexTpl";
        $.get(url, '', function(data) {
            if (data == 1) {
                ajaxCateTeam()
            } else {
                ajaxIndexTeamTotal();
            }
        }, 'json');
    }

    /**
     * 异步获取团单总记录
     */
    function ajaxIndexTeamTotal() {
        var url = $base_url + "/Index/ajaxIndexTeamTotal";
        $.get(url, '', function(data) {
            var teamTotal = data;
            var pageSize = 40;
            if (teamTotal > 40) {
                var moreNum = teamTotal - pageSize;
                ajaxTeam(0, pageSize);
                ajaxMoreTeam(moreNum);
            } else {
                ajaxTeam(0, teamTotal);
            }
        }, 'json');
    }

    /**
     * 异步获取新首页模板数据
     */
    function ajaxCateTeam() {
        var url = $base_url + "/Index/ajaxCateTeam";
        $.get(url, '', function(data) {
            if (data.status == 1) {
                $('#ajax-team').empty();
                $('#ajax-team').html($("#cate-team-tmpl").tmpl(data));
            }
        }, 'json');
    }

    /**
     * 异步获取
     */
    function ajaxTeam(offset, pageSize) {
        var url = $base_url + "/Index/ajaxTeam";
        var ajaxData = {offset: offset, num: pageSize};
        $.get(url, ajaxData, function(data) {
            if (data.status == 1) {
                if (offset == 0) {
                    team_access = true;
                }
                $('#ajax-img').remove();
                $('#ajax-team').append($("#team-tmpl").tmpl(data));
            }
        }, 'json');
    }
	                        	   /**
     * 图片轮播
     */
    	 /**
	     * 异步获取首页广告
	*/
	function ajaxAdManage() {
	    var url = $base_url + "/Index/ajaxAdManage";
	    $.get(url, '', function(data) {
	        if (data.status == 1) {
	            $('#admanage').html($("#admanage-tmpl").tmpl(data));
	            //加载图片轮播效果
	            picAnimate();
	        }
	    }, 'json');
	}

    function picAnimate() {
        //banner 效果
        var banner_Li_Num = $('#banner li').length;
        $('#banner ul').css({'width': li_Width * banner_Li_Num, 'marginLeft': 0});
        if (banner_Li_Num > 1) {
            $('#prevbt').click(function() {
                prevbt();
            })
            $('#nextbt').click(function() {
                nextbt();
            })
            banner_T = setInterval(banner_Auto, 3000);
            $('#banner').hover(function() {
                clearInterval(banner_T);
            }, function() {
                banner_T = setInterval(banner_Auto, 3000);
            })
        }
    }

    /**
     * 图片自动轮播下一张
     */
    function banner_Auto() {
        nextbt();
    }

    /**
     * 上一张
     */
    function prevbt() {
        $('#prevbt').unbind('click');
        $('#banner li:last').detach().insertBefore('#banner li:first').parents('ul').css('marginLeft', -li_Width * 2).stop(true).animate({marginLeft: -li_Width}, 800, 'easeInOutBack', function() {
            $('#prevbt').bind('click', prevbt);
        });
    }

    /**
     * 下一张
     */
    function nextbt() {
        $('#nextbt').unbind();
        $('#banner ul').stop(true).animate({marginLeft: -li_Width * 2}, 800, 'easeInOutBack', function() {
            $('#banner ul').css('marginLeft', -li_Width).find('li:first').insertAfter('#banner li:last');
            $('#nextbt').bind('click', nextbt);
        })
    }
    /**
     * 瀑布流获取更多数据
     * @param moreNum
     */
    function ajaxMoreTeam(moreNum) {
        var step = 8;
        var count = moreNum;
        var offset = 40;
        $(window).scroll(function() {
            if ($(document).height() - $(this).scrollTop() - $(this).height() < 200) {
                if (count >= step && team_access === true) {
                    ajaxTeam(offset, step);
                    offset = offset + step;
                    count = count - step;
                }
            }
        });
    }
});
function GetSjhu(){
    return "superegoliu";
}