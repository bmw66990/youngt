
//设置|取消 小组制定
function setGroupStatus(id,val){
	$.getJSON($base_url+'/Workbench/setGroupStatus', {id:id,st:val}, function(data) {
		alert(data.info);
		if(data.status==1){
			window.location.reload();
		}
	});
}

$(function(){

	//小组任务js
	$("#group-select-uid").change(function(){
		var val=$(this).val();
		var gid=$(this).attr('gid');
		if(val=='adduser') {

		}else{
			location.href=$base_url+'/Workbench/groupTask/gid/'+gid+'/uid/'+val;
		}
	});

	$("#group-select-type").change(function(){
		var gid=$(this).attr('gid');
		location.href=$base_url+'/Workbench/groupTask/gid/'+gid+'/type/'+$(this).val();
	});

	$("#group-select-item").change(function(){
		var val=$(this).val();
		var gid=$(this).attr('gid');
		switch(val){
			case 'exitgroup':
				if(confirm('确定退出该小组')){
					$.getJSON($base_url+'/Workbench/exitGroup', {gid:gid}, function(data) {
						alert(data.info);
						if(data.status==1){
							location.href=$base_url+'/Workbench/myGroup';
						}
					});
				}
				break;
		}
	});


})