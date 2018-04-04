/*
 youngt.com Manage Css common.js
 by 2015-5
 ***********公共JS
 */
window.onload = function() {
    if (!window.applicationCache) {
       // location.href = "error/noHtml5.html";
    }
}
$(function() {

    //nav 效果
    $(window).scroll(function() {
        //获取子菜单高度
        var $divheight = $(".tab-menu").height() - 200;
        //赋值右边内容区最低高度
        if ($divheight > 500  &&  $(".tab-menu").display &&  $(".tab-menu").display() != "none") {
            $("section").css("min-height", $divheight);
        }
        //左侧子菜单形成最终位置
        var $w_height = $(window).height();
        var $tab_li = $(".tab-menu ul li");
        for (var i = 0; i < $tab_li.length; i++) {
            $tab_li.eq(i).css("z-index", $tab_li.length - i);
            $tab_li.eq(i).animate({top: -25 * i + 'px'}, "slow");
        }


        var scrollTop = document.documentElement.scrollTop || window.pageYOffset || document.body.scrollTop
        //计算未浮动时 子菜单left 值
        $x_left = ($(document).width() - 960) / 2 - 25;
        if (scrollTop > 130) {
            //主菜单悬浮
            $("nav").addClass("nav-float");
            //子菜单悬浮
            $(".nav-dis-menu").css({
                "position": "fixed",
                "top": "40px",
                "left": $x_left + 20
            });
            $(".tab-menu").css({
                "position": "fixed",
                "top": "40px",
                "left": $x_left
            });
        } else {
            $("nav").removeClass("nav-float");
            $(".nav-dis-menu").css({
                "position": "absolute",
                "top": "0",
                "left": "0"
            });
            $(".tab-menu").css({
                "position": "absolute",
                "top": "0px",
                "left": "-25px"
            });
        }
        ;
        //返回顶部显示隐藏
        if ($(window).scrollTop() > 50) {
            $(".scrollTop").fadeIn();
        }
        else {
            $(".scrollTop").hide();
        }
    });
    //子菜单1 
    var $tab_li = $(".tab-menu ul li");
    for (var i = 0; i < $tab_li.length; i++) {
        $tab_li.eq(i).css("z-index", $tab_li.length - i);
        $tab_li.eq(i).animate({top: -25 * i + 'px'}, "slow");
    }
    //复制子菜单
    $(".nav-dis-menu").append($(".tab-menu ul").html());
    $(".nav-dis-menu").hover(function() {
        $(".nav-dis-menu").animate({width: "900px"}, "slow");
        $(".nav-dis-menu").css("height", "auto");
        $(".nav-dis-menu label").css("background", "url("+$base_image_url+"/ico/hover.png)");
    }, function() {
        $(".nav-dis-menu").animate({width: "40px"}, "slow", function() {
            $(".nav-dis-menu label").css("background", "url("+$base_image_url+"/ico/nohover.png)");
        });
        $(".nav-dis-menu").css("height", "40px");
    })
    var $divheight = $(".tab-menu").height() - 200;
    var $screenheight = $(window).height();
    if ($divheight > $screenheight) {
        $(".tab-menu").css("display", "none");
        $(".nav-dis-menu").css("display", "block");
        $("section").css("border-left", "none");
    }
    else {
        $(".tab-menu").css("display", "block");
        $(".nav-dis-menu").css("display", "none");
        $("section").css("border-left", "1px solid #00796B");
    }
    //返回顶部
    $(".scrollTop").click(function() {
        $('html,body').animate({'scrollTop': 0}, 300);
    });
    //邻里圈
    $(".circle-pic").on("click",".pic-view",function(){
			var $_img_list = $(this).parent().prev(".img").html();
			var $_html = '<div id="pic-view"><div class="pic-view-con"><div class=""><span><button class="pre">上一张</button></span>';
			$_html = $_html+'<span><button class="next">下一张</button></span><span><button class="close-pop">关闭</button></span></div>';
			$_html = $_html+'<div class="pic-list">'+$_img_list;
			$_html = $_html+'</div></div></div>';
			$("body").append($_html);			
		})
		$(document).on("click",".close-pop",function(){
			$(this).parents("#pic-view").prop("outerHTML","");
		})
		$(document).on("click",".pre",function(){
			var o;
			$(".pic-list img").each(function(){
				if($(this).css("display")=="block"){
					o = $(this).index();
				}
			});
			$("#pic-view .next").removeAttr("disabled");
			$(".pic-list img").siblings().css("display","none").eq(o-1).css("display","block");
			if(o==1){
				$(this).attr("disabled","disabled");
			}
		});
		$(document).on("click",".next",function(){
			var o;
			$(".pic-list img").each(function(){
				if($(this).css("display")=="block"){
					o = $(this).index();
				}
			});
			$("#pic-view .pre").removeAttr("disabled");
			$(".pic-list img").siblings().css("display","none").eq(o+1).css("display","block");
			if(o==$(".pic-list img").length-2){
				$(this).attr("disabled","disabled");
			}
			
		});
	//邻里圈结束
	
});
function popup(url, width, height) {
    $('<a href="' + url + '"></a>').fancybox({
        'autoScale': false,
        'scrolling': 'yes',
        'transitionIn': 'fade',
        'centerOnScroll': true,
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500,
        'width': width,
        'height': height,
        'type': 'iframe',
    }).click();
}

// ajax请求
function toAjax(url, msg) {
    if (confirm(msg)) {
        $.get(url, function(data) {
            if (data.status == 1) {
                $("#message-con").html($('#message-top-tmpl').tmpl({success: data.info}));
                window.setTimeout(function() {
                    window.location.reload();
                }, 3000);
            } else {
                $("#message-con").html($('#message-top-tmpl').tmpl({error: data.info}));
            }
        });
    }
}

//全选||取消操作
function checkAll(checked, className) {
    if (checked) {
        $(className).attr('checked', true);
    } else {
        $(className).attr('checked', false);
    }
}
//删除券号
function delCoupon(url, oid, className, msg) {
    if (confirm(msg)) {
        var ids = [];
        $(className).each(function() {
            if (this.checked) {
                ids.push(this.value);
            }
        });
        if (ids == false) {
            alert('请选择要删除的券号');
            return false;
        }
        $.post(url, {id: ids, oid: oid}, function(data) {
            if (data.status == 1) {
                $("#message-con").html($('#message-top-tmpl').tmpl({success: data.info}));
                window.setTimeout(function() {
                    window.location.reload();
                }, 3000);
            } else {
                $("#message-con").html($('#message-top-tmpl').tmpl({error: data.info}));
            }
        })
    } else {
        return false;
    }
}