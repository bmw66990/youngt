//layer 公共配置文件
layer.config({
    extend: 'extend/layer.ext.js', //注意，目录是相对layer.js根目录。如果加载多个，则 [a.js, b.js, …]
    shift: 2, //默认动画风格
    skin: 'layui-layer-molv',//默认皮肤
    area:['780px','80%']
});
$(function(){
	$(".layer-iframe").on("click",function(){
		$url = $(this).attr("data-href");
		$title = $(this).attr("data-title");
		layer.open({
		    type: 2,
		    title: $title,
		    offset:"10%",
		    shadeClose: true,
		    shade: 0.8,
			content:$url,
			
		}); 
	});
})
