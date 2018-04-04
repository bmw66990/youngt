$(function(){
	//创建弹出层对话框
	$("#floatFrame").dialog({
		width:770,
		height:500,	
		show:true,
		hide:true,
		draggable:false,
		resizable:false,
		modal:true,
		autoOpen:false,
		closeText:"关闭",
	});
	//自定义初始化鼠标悬停事件函数
	function resetClass(){
		$("div[class^='img']").each(function(index){
			$(this)
			.unbind("mouseenter mouseleave")
			.prop("className", "img"+(index+1) )
			.hover(function(){
				$(this).toggleClass("img"+(index+1)+"Hover");
			});
		});
	};
	//设置图片点击切换效果
	function setImg(obj, cName, hContent, codeClass){
		resetClass();
		obj.unbind("mouseenter mouseleave").addClass(cName);
		$("#pContent").html(hContent);
		$("#pContent").find(".btn").button();
		$("#qrCode").prop("className", codeClass);
	}
	//自定义切换点击事件函数
	function switchClick(index){
		switch(index){
			case 0 : setImg($(".img1"), "img1Hover", "电脑直接下载<br /><br /><a class='btn' href='http://ytfile.oss-cn-hangzhou.aliyuncs.com/newui3.2.8.apk'>Android下载</a><br /><br />手机浏览器访问<a href='http://ytfile.oss-cn-hangzhou.aliyuncs.com/newui3.2.8.apk' target='_blank'>http://ytfile.oss-cn-hangzhou.aliyuncs.com/newui3.2.8.apk</a>即可下载", "andriodCode"); break;
			case 1 : setImg($(".img2"), "img2Hover", "访问商店下载<br /><br /><a class='btn' href='https://itunes.apple.com/cn/app/qing-tuan/id681045261?mt=8' target='_blank'>iPhone下载</a><br /><br />手机浏览器访问<a href='https://itunes.apple.com/cn/app/qing-tuan/id681045261?mt=8' target='_blank'>itunes.apple.com/cn/app/qing-tuan/id681045261?mt=8</a>即可下载", "iphoneCode"); break;
			case 2 : setImg($(".img3"), "img3Hover", "请点击下面按钮直接访问<br /><br /><a class='btn' href='http://m.youngt.com/' target='_blank'>触屏网站</a>", "wapCode"); break;
			case 3 : setImg($(".img4"), "img4Hover", "微信搜索“青团网“", "weixinCode"); break;
			default : return false;
		}
	}
	//自定义函数打开对话框并切换到对应的选项index从0开始
	function openDialog(index){
		$("#floatFrame").dialog("open");
		switchClick(index);
	}
	//点击页面中间免费下载弹出对话框
	$(".downloadtop, .androidicon").click(function(){
		openDialog(0);
	});
	$(".iPhoneicon").click(function(){
		openDialog(1);
	});	
	$(".weixinicon").click(function(){
		openDialog(3);
	});	
	//通过点击菜单按钮打开对话框
	$(".mbnav a").each(function(index){
		$(this).click(function(){
			$("#floatFrame").dialog("open");
			switchClick(index);
		});
	});
	//点击弹出层中的图片来切换相应的内容
	$("div[class^='img']").each(function(index){
		$(this).click(function(){
			switchClick(index);
		});
	});
	$('a, button').focus(function(){
		this.blur();
	});
});