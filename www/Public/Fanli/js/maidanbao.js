$(document).ready(function(){

	$(".arrow-up4").hide();
	$(".arrow-up3").hide();
	$(".arrow-up2").hide();

	$(".share-make-monery").show();
	$(".my-comment-content").hide();
	$(".rebate-order-content").hide();
	$(".property-records-content").hide();

	$(".user-info2").hide();
	$(".user-leve2-line").hide();

	$(".make-monery").click(function(){
		$(this).css({"color":"#E60827","border-bottom":"3px solid #E60827"});
		$(this).siblings().css({"color":"#666","border-bottom":"none"});
		$(".arrow-up1").show();
		$(".arrow-up2").hide();
		$(".arrow-up3").hide();
		$(".arrow-up4").hide();
		$(".my-comment-content").hide();
		$(".rebate-order-content").hide();
		$(".property-records-content").hide();
		$(".share-make-monery").show();
	});
	$(".my-comment").click(function(){
		$(this).css({"color":"#E60827","border-bottom":"3px solid #E60827"});
		$(this).siblings().css({"color":"#666","border-bottom":"none"});
		$(".arrow-up2").show();
		$(".arrow-up1").hide();
		$(".arrow-up3").hide();
		$(".arrow-up4").hide();
		$(".share-make-monery").hide();
		$(".rebate-order-content").hide();
		$(".property-records-content").hide();
		$(".my-comment-content").show();
	});
	
	$(".rebate-order").click(function(){
		$(this).css({"color":"#E60827","border-bottom":"3px solid #E60827"});
		$(this).siblings().css({"color":"#666","border-bottom":"none"});
		$(".arrow-up1").hide();
		$(".arrow-up2").hide();
		$(".arrow-up3").hide();
		$(".arrow-up4").hide();
		$(".arrow-up3").show();

		$(".my-comment-content").hide();
		$(".share-make-monery").hide();
		$(".property-records-content").hide();
		$(".rebate-order-content").show();
	});

	$(".property-records").click(function(){
		$(this).css({"color":"#E60827","border-bottom":"3px solid #E60827"});
		$(this).siblings().css({"color":"#666","border-bottom":"none"});

		$(".arrow-up1").hide();
		$(".arrow-up2").hide();
		$(".arrow-up3").hide();
		$(".arrow-up4").show();


		$(".my-comment-content").hide();
		$(".share-make-monery").hide();
		$(".rebate-order-content").hide();
		$(".property-records-content").show();
	});

	$(".next-step-btn1").click(function(){
		$(".scan-code").hide();
		$(".business-information").show();
		$(".step2").css({"background-color":"#E60827"});
		$(".cut-line1").css({"background-color":"#E60827"});
	});

	$(".next-step-btn2").click(function(){	
		$(".business-information").hide();
		$(".make-monery-gx").show();
		$(".step3").css({"background-color":"#E60827"});
		$(".cut-line2").css({"background-color":"#E60827"});
	});

	/*用户级别*/

	$(".user-leve2").click(function(){
		$(".user-info1").hide();
		$(".user-info2").show();
		$(".user-leve2-line").show();
		$(".user-leve1-line").hide();
	});

	$(".user-leve1").click(function(){
		$(".user-info1").show();
		$(".user-info2").hide();
		$(".user-leve2-line").hide();
		$(".user-leve1-line").show();
	});

	$('.middle').height($(window).height() - $('.personal-info').height() - 100);
	  $(window).resize(function () {
	    $('.middle').height($(window).height() - $('.personal-info').height() - 100);
	  });

	$('.img-bg').height($(window).height());
    $('.img-bg').width($(window).width());  


    $(".personal-center").click(function(){
    	$(".personal-center-txt").css("color","#E60827");
    });

    /*申请提现*/
    $(".withdrawal-makeSure").click(function(){
    	$(".withdrawal-content").hide();
    	$(".withdrawal-content2").show();
    });

    $(".withdrawal-code-lt").click(function(){
    	$(".withdrawal-content").show();
    	$(".withdrawal-content2").hide();
    });
    
    $(".withdrawal-all").click(function(){   	
    	var count = $(".prompt-count").text();
    	$("#withdrawal-count").val(count);
    });


    /*提现历史*/
/*
   	$(".withdrawal-history").click(function(){
   		$(".withdrawal-code-title").text("提现历史");
   		$(".withdrawal-bg").hide();
   		$(".withdrawal-prompt-success").hide();
   		$(".withdrawal-form").hide();
   		$(".withdrawal-finish").hide();
   		$(".withdrawal-his").show();

   		$(".withdrawal-code-lt").
   	});

*/
	/*弹框*/

 /*   $(".getHongbao-btn").click(function(){
    	popMask();
    });


});

function popMask(){	
	var cHeight=document.documentElement.clientHeight;
	var cWidth=document.documentElement.clientWidth;

    var oMask = document.createElement("div");
    	oMask.id = "mask";
	    oMask.style.width = cWidth+"px";
	   	oMask.style.height = cHeight+"px";
	   	document.body.appendChild(oMask);

   	var omaskContet = document.createElement("div");
   		omaskContet.id = "maskContet";
		omaskContet.style.width = cWidth+"px";
		omaskContet.style.height = cHeight+"px";
		omaskContet.innerHTML = "<img src='images/getHongbao-success.png' class='img-responsive'><div class='mask-btn'><button class='shopNow-btn'>立即购买</button></div>";
		document.body.appendChild(omaskContet);

		omaskContet.style.left=0+"px";
		omaskContet.style.top=0+"px";
}
*/
});