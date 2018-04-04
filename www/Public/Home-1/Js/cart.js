$(function(){
	// +
	$(".plus").die().live('click',function(){
		var $input=$(this).parent().children('input');
		var quantity=parseInt($input.val());
        quantity = quantity + 1;
        if(quantity>500){
        	quantity=500;
        }
        $(this).parent().find('.minus').removeClass('minus-disabled');
        updateCart($input, quantity);
	});

	// -
	$(".minus").die().live('click',function(){
		var $input=$(this).parent().children('input');
		var quantity=parseInt($input.val());
        quantity = quantity - 1;
        if (quantity <= 1) {
        	$(this).addClass('minus-disabled');
            quantity = 1;
        } 
        updateCart($input, quantity);
	});

	// 输入值
	$(".cart-item-num").die().live('keyup',function(){
		var quantity=parseInt($(this).val());
		if (!quantity || quantity <= 1) {
        	$(this).addClass('minus-disabled');
            quantity = 1;
        }else{
        	$(this).removeClass('minus-disabled');
        	if(quantity > 500) {
	        	quantity = 500;
	        }
        }   
        updateCart($(this), quantity);
	});

	function updateCart(obj,num){
		var team_id = obj.attr('tid');
		var price = obj.attr('price');
		Cart.createCart(team_id,price,num);

		var price=Number(obj.parents('tr').find('.team-price').text());
		obj.parents('tr').find('.team-sum').text((price*num).toFixed(2));
		obj.val(num);
		obj.parents('tr').find('.team-check').attr('checked',true);
		setCartTotal();
	}

	//全选
	$("#all-check").die().live('click',function(){
		var state;
		if(this.checked){
			$(".team-check").attr('checked',true);
			state='Y';
		}else{
			$(".team-check").attr('checked',false);
			state='N';
		}
		Cart.allSelectCart(state);
		setCartTotal();
		
	});

	$(".team-check").die().live('click', function(){
		var state;
		if(this.checked){
			state='Y';
		}else{
			state='N';
		}
		Cart.selectCart($(this).val(), state);
		setCartTotal();
	});

	//删除
	$(".del-cart").die().live('click',function(){
		var team_id=$(this).attr('tid');
		Cart.delCart(team_id);

		if(ISLOGIN) {
			//登陆直接修改数据库
			var $this=$(this);
			$.get($base_url+'/Cart/delCart/team_id/'+team_id,function(data){
				if(data.status!=1){
					alert(data.info);
				}
			});

		}
		$(this).parents("tr").remove();
		setTopCart();
	});

	function setCartTotal(){
		var data=Cart.getTotal();
		if(data.srcNum == 0) {
			$(".cart-content").text('当前购物车为空');			
		} else {
			$("#cart-total").text(data.total);
			$("#cart-num").text(data.num);
			$("#is-cookie").val(1);		

			if(data.num == 0) {
				$(".btn-buy").attr('disabled', true);
			}else{
				$(".btn-buy").removeAttr('disabled');
			}

			if(data.num != data.srcNum) {
				$("#all-check").attr('checked',false);
			}else{
				$("#all-check").attr('checked',true);
			}
		}
	}
})


// 购物车
var Cart = {

		list:[],

		init: function() {
			//绑定操作
		},

		getCartList: function() {
			var str = $.cookie('cart');
			if(str) {
				this.list = jQuery.parseJSON(str);
			}
		},

		setCartCookie: function() {
			if(this.list.length > 0){
				$.cookie('cart', JSON.stringify(this.list),{path:'/'});
			} else {
				$.cookie('cart', '',{path:'/'});
			}
		},

		createCart: function(team_id,price,num) {		
			if(this.isExist(team_id)) {
				this.updateCart(team_id,price,num);
			}else{
				this.addCart(team_id,price);
			}
			this.setCartCookie();
		},

		isExist: function(team_id) {
			this.getCartList();
			var len = this.list.length;
			for(var i=0;i<len;i++) {
				var cur=this.list[i];
				if(cur.team_id == team_id) {
					return true;
					break;
				}
			}
			return false;
		},

		addCart: function(team_id,price) {
			var data = {
				team_id:team_id,
				num:1,
				price:price,
				state:'Y'
			}
			if(this.list.length>=20){
				alert('购物车只能加入20个团单');
				return false;
			}
			this.list.push(data);
		},

		updateCart: function(team_id,price,num) {
			var len = this.list.length;	
			for(var i=0;i<len;i++) {
				var cur=this.list[i];
				if(cur.team_id == team_id) {
					cur.price = price;
					if(num==undefined){
						cur.num += 1;
					} else {
						cur.num = num;
					}
					break;
				}
			}
		},

		delCart: function(team_id) {
			if(this.isExist(team_id)) {
				var len=this.list.length;
				for(var i=0;i<len;i++) {
					var cur=this.list[i];
					if(cur.team_id == team_id) {
						this.list.splice(i,1);
						break;
					}
				}
				this.setCartCookie();
			}
		},

		getTotal: function() {
			this.getCartList();
			var total = num = 0;
			var len = this.list.length;
			for(var i=0;i<len;i++) {
				var cur=this.list[i];
				if(cur.state=='Y'){
					num += 1;
					total += cur.num * cur.price;
				}
			}
			return {
				total:total.toFixed(2),
				num:num,
				srcNum:len
			}
		},

		selectCart: function(team_id,state) {		
			if(this.isExist(team_id)) {
				console.log(this.list);
				var len = this.list.length;
				for(var i=0;i<len;i++) {
					var cur=this.list[i];
					if(cur.team_id == team_id) {
						cur.state = state;
						this.setCartCookie();
						break;
					}
				}
			}
		},

		allSelectCart: function(state) {
			this.getCartList();
			var len = this.list.length;
			for(var i=0;i<len;i++) {
				var cur=this.list[i];
				cur.state = state;
			}
			this.setCartCookie();
		}
	}

function setTopCart(){
	var data=Cart.getTotal();
	$("#user-cart-nums").text(data.srcNum);
}

//添加购物车
function createCart(team_id,price){
	Cart.createCart(team_id, price);
	if(ISLOGIN){
		$.post($base_url + "/Cart/addCart",{team_id:team_id});
	}
	setTopCart();
	cartAlert();
}

// 加入购物车的弹窗,后期根据需求修改
function cartAlert(){
	alert('成功加入购物车');
}

