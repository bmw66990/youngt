$(function(){
	/*ajax分页*/
	$(".ajax-pages a").die().live('click',function(){
		var url=$(this).attr('href');
		var src=$('.ajax-pages').attr('data-src');
		$.get(url,function(data){
			$(src).html(data.html);
		});
		return false;
	});

	$('#order-state').change(function(){
	 	$(this).parents('form').submit();
	});

	

	/*获取成长值*/
	$.get($base_url+"/Member/getGrowth",function(data){
 		if(data<300){
 			$('.get-growth-icons').css('background-position','-20px 0');
 		}else if(data<1000){
 			$('.get-growth-icons').css('background-position','-20px -24px');
 		}else if(data<3000){
 			$('.get-growth-icons').css('background-position','-20px -49px');
 		}else if(data<10000){
 			$('.get-growth-icons').css('background-position','-20px -73px');
 		}else if(data<30000){
 			$('.get-growth-icons').css('background-position','-20px -99px');
 		}else if(data<100000){
 			$('.get-growth-icons').css('background-position','-20px -124px');
 		}else if(data>=100000){
 			$('.get-growth-icons').css('background-position','-20px -149px');
 		}
 	});
 	//用户手机图标悬停加载弹出层内容
	$("#phoneICO").hover(function(){
		$(".phoneTip").show();
	},
		function(){
			$(".phoneTip").hide();
		}
	)

	//手机绑定弹出框效果
 	$(".fac").fancybox({
      'autoScale':false,
      'scrolling':'no',
      'transitionIn':'fade',
      'transitionOut':'fade',
      'speedIn': 500,
      'speedOut': 500,
      'width': 480,
      'height': 260,
      'type':'iframe'
	});	

	//订单详情
	$('.order-detail').fancybox({
		'autoScale':false,
		'scrolling':'no',
		'transitionIn':'fade',
		'transitionOut':'fade',
		'speedIn': 500,
		'speedOut': 500,
		'width': 750,
		'height': 750,
		'type':'iframe'
	});
	//查看券号
	$('.coupon-view').fancybox({
		'autoScale':false,
		'scrolling':'no',
		'transitionIn':'fade',
		'transitionOut':'fade',
		'speedIn': 500,
		'speedOut': 500,
		'width': 800,
		'type':'iframe'
	});
        
        // 领奖显示
        $('.receive-prize-view').fancybox({
		'autoScale':false,
		'scrolling':'no',
		'transitionIn':'fade',
		'transitionOut':'fade',
		'speedIn': 500,
		'speedOut': 500,
		'width': 500,
		'height': 300,
		'type':'iframe'
	});
        
        // 云购码查看
        $('.view-all-cloud-shopping-code').fancybox({
		'autoScale':false,
		'scrolling':'yes',
		'transitionIn':'fade',
		'transitionOut':'fade',
		'speedIn': 500,
		'speedOut': 500,
		'width': 550,
		'height': 350,
		'type':'iframe'
	});
})

//确认收货
function makesureOrder(id){
	if(confirm('确认收货吗？')){
		$.post($base_url+'/Member/makesureOrder',{id:id},function(data){
			alert(data.info);
			if(data.status==1){
				window.location.reload();
			}
		});
	}
}