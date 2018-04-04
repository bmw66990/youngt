/**
 * 详情页面JS
 */

$(function() {
    //浮动导航
    var $navset = $(".con-nav").offset();
    if($navset){
        var $navtop = $navset.top;
        $(window).scroll(function() {
                var scrollTop = document.documentElement.scrollTop || window.pageYOffset || document.body.scrollTop;
                if(scrollTop>=$navtop){
                    $(".con-nav").addClass("nav-over");
                    $("#team-j-qg").css("display","block");
                }else{
                    $(".con-nav").removeClass("nav-over");
                    $("#team-j-qg").css("display","none");
                }
        });
    }
    //导航点击选中效果
    var $div_li = $(".con-nav dl");
    $div_li.click(function() {
        //获取高度  scroll+50
        var $index = $(this).index();
        var src =$(this).find("a").attr("href");
        if(src=="javascript:;"){
            var $oDiv = $(".product-con>div");
            $oDiv.siblings().css("display","none").eq($index).css("display","block");
        }else
        {
            var $du = $(src).offset().top-60;
            $("html, body").animate({
                    scrollTop: $du
                }, 800);
        }
        
        $(this).addClass("c").siblings().removeClass("c");
    })
    //图片轮播
    var cur = 0;
    function autoRun() {//自动轮播函数
        cur++;
        // if(cur==7){
        // 	cur = 0;
        // }
        cur = (cur == 5) ? 0 : cur;//判断cur是否该变化
        // 变化大图div
        var left = cur * -480;//计算大图div的left值
        $('#pro-pic-carous .pic_box').animate({'left': left + 'px'}, 300);//让大图div变换left值
        // 变化小图列表
        $('#pro-pic-carous ul li').eq(cur).addClass('cur').siblings('li').removeClass('cur');
    }
    //   var timer = setInterval(autoRun,3000);//定时器设立
//    $('#pro-pic-carous ul li').mouseover(function(){
//        clearInterval(timer);
//        cur = $(this).index();//获得当前鼠标移入的li的序号
//        // 变化大图div
//        var left = cur* -480;//计算大图div的left值
//        $('#pro-pic-carous .pic_box').stop().animate({'left':left+'px'},300);//让大图div变换left值
//        // 变化小图列表
//        $('#pro-pic-carous ul li').eq(cur).addClass('cur').siblings('li').removeClass('cur');
//    })
//    $('#pro-pic-carous ul li').mouseout(function(){
//      //  timer = setInterval(autoRun,3000);//定时器设立
//    })
//评论图片展示
    $(".showpic img").die().live('click', function() {
        var $s_spic = $(this).parents('div.showpic').find('ul.showselect');
        $(this).addClass("selected").siblings().removeClass("selected");//选中状态
        //将img 值写入div
        var $img_src = $(this).attr("src");
        //如果已显示则隐藏
        if ($s_spic.html() === "") {
            $s_spic.html("<img src='" + $img_src + "'/>");
        }
        else {
            $(this).removeClass("selected");
            $s_spic.html("");
        }
    });
})
//评价选项卡
$(document).ready(function() {

    (function($) {
        //$('.tab ul.tabs').addClass('active').find('> li:eq(0)').addClass('current');

        $('#pj div.title label a').click(function(g) {
            var tab = $(this).closest('#pj'),
                    index = $(this).closest('label').index();

            tab.find('div.title > label').addClass('cz_blue');
            $(this).closest('label').removeClass('cz_blue');
            tab.find('#showpj').find('li.tabs_item').not('li.tabs_item:eq(' + index + ')').slideUp();
            tab.find('#showpj').find('li.tabs_item:eq(' + index + ')').slideDown();

            g.preventDefault();
        });
    })(jQuery);

});
/*数量加减js*/
;
(function($) {
    $.fn.spinner = function(opts) {
        return this.each(function() {
            var defaults = {value: 0, min: 0}
            var options = $.extend(defaults, opts)
            var keyCodes = {up: 38, down: 40}
            var container = $('<div></div>')
            container.addClass('spinner')
            var textField = $(this).addClass('value').attr('maxlength', '3').val(options.value)
                    .bind('keyup paste change', function(e) {
                        var field = $(this)
                        if (e.keyCode == keyCodes.up)
                            changeValue(1)
                        else if (e.keyCode == keyCodes.down)
                            changeValue(-1)
                        else if (getValue(field) != container.data('lastValidValue'))
                            validateAndTrigger(field);
                        return false;
                    })
            textField.wrap(container)

            var increaseButton = $('<button class="increase">+</button>').click(function() {
                changeValue(1);
                return false;
            })
            var decreaseButton = $('<button class="decrease">-</button>').click(function() {
                changeValue(-1);
                return false;
            })

            validate(textField)
            container.data('lastValidValue', options.value)
            textField.before(decreaseButton)
            textField.after(increaseButton)

            function changeValue(delta) {
                var num = getValue() + delta;
                if (num < 1) {
                    num = 1;
                } else if (num > 500) {
                    num = 500;
                }
                textField.val(num)
                validateAndTrigger(textField)
            }

            function validateAndTrigger(field) {
                clearTimeout(container.data('timeout'))
                var value = validate(field)
                if (!isInvalid(value)) {
                    textField.trigger('update', [field, value])
                }
            }

            function validate(field) {
                var value = getValue()
                if (value <= options.min)
                    decreaseButton.attr('disabled', 'disabled')
                else
                    decreaseButton.removeAttr('disabled')
                field.toggleClass('invalid', isInvalid(value)).toggleClass('passive', value === 0)

                if (isInvalid(value)) {
                    var timeout = setTimeout(function() {
                        textField.val(container.data('lastValidValue'))
                        validate(field)
                    }, 500)
                    container.data('timeout', timeout)
                } else {
                    container.data('lastValidValue', value)
                }
                return value
            }

            function isInvalid(value) {
                return isNaN(+value) || value < options.min;
            }

            function getValue(field) {
                field = field || textField;
                return parseInt(field.val() || 1, 10)
            }
        })
    }
})(jQuery)