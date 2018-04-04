function msgbox(data,num){
	tate=true;
	if(num!=0){
		index = num-26;
		if(index>9){
			index=index-10;
		}
		sum = num-30;
		$.msgbox({
			width:300,
			height:150,
			content:data,
			title:'中奖提示',
			type :'confirm',
			onClose:function(v){
				if(v){
					$('#begin').click();
				}else{
					$.closemsgbox(top.window.document);
				}
			}
		});  
	}else{
		$.msgbox({
			height:100,
			width:200,
			content:data,
			title:'错误提示',
			type :'text'
		}); 
	}
}

function lottery(data){
	var list = $('#lottery li');    
	var len = list.length; 
	var interval = null;
	var msg='';
	interval = setInterval(function(){ 
		if( sum == data.msg ){
			if(data.msg==30){
			  msg="很遗憾,您没能中奖!";
			}else{
			  msg="恭喜您抽中"+data['prize']+"积分";
			}
			clearInterval(interval);
			getScore();
			getinfo();
			msgbox(msg,data.msg);
		}else{
			list[index].className = "grid";
			list[(index+1) % len].className = "grid choosed";
			index = ++index % len;
			sum+=1;
		}
	},100);
} 