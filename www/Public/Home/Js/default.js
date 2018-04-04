/*
 * 首页js
 */
this.imagePreview = function(){	
	/* CONFIG */
		
		xOffset = 5;
		yOffset = 10;
		
		// these 2 variable determine popup's distance from the cursor
		// you might want to adjust to get the right result
		
	/* END CONFIG */
	$("a.preview").hover(function(e){
		this.t = this.title;
		this.title = "";	
		var c = (this.t != "") ? "<br/>" + this.t : "";
		$("body").append("<div id='preview'><img src='"+ this.rel +"' alt='Image preview' />"+ c +"</div>");								 
		$("#preview")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px")
			.fadeIn("fast");						
    },
	function(){
		this.title = this.t;	
		$("#preview").remove();
    });	
	$("a.preview").mousemove(function(e){
		$("#preview")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	});			
};


$(function() {
	//商家评论 图片效果
	imagePreview();
    //呼出主菜单
    var $div_i = $(".menu-title")
    $div_i.css("cursor", "pointer");
    $div_i.hover(function() {
        $(".class-menu").show();
        $(this).children("i").removeClass("noover").addClass("onover");
    },
            function() {
            });
    $("#disn").mouseleave(function() {
        $(".class-menu").hide();
        $div_i.children("i").removeClass("onover").addClass("noover");
    });

    //呼出子导航
    var $div_li = $(".class-menu>ul>li");
    var $f_class_menu = $(".class-menu ul li .class-menu-float");
    $div_li.die().live('mouseenter', function() {
        $(".demo-one .demo-one-right").css("z-index", "0");
        $(this).addClass("n-onclick").siblings().removeClass("n-onclick");//选择的li样式
        $(this).children(".class-menu-float").css("display", "block");
    }).live('mouseleave', function() {
        $(".demo-one .demo-one-right").css("z-index", "2");
        $div_li.removeClass("n-onclick");
        $(this).children(".class-menu-float").css("display", "none");
    });

		////////////////

			$(".demo-class-more").on("mouseover",function(){
				$(this).next("div").addClass("model-class");
				$(".demo-class-menu-li").css("overflow","visible");
			});
			$(document).on("mouseleave","#AllAera",function(){
				$(this).removeClass("model-class");
				$(".demo-class-menu-li").css("overflow","hidden");
			})
		//头部nav效果2015-12-16***************************************************
	var $oli = $(".more-showul");
	for(i=0;i<$oli.length;i++){
		$oli.eq(i).bind({mouseover:navmoover,mouseleave:navmoleave});
	}
	function navmoover(){
		$(this).find("div").css("display","block");
	}
	function navmoleave(){
		$(this).find("div").css("display","none");
	}

});


