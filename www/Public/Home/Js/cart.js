$(function() {
    // +
    $(".plus").die().live('click', function() {
        var $input = $(this).parent().children('input');
        var quantity = parseInt($input.val());
        quantity = quantity + 1;
        if (quantity > 50) {
            quantity = 50;
        }
        $(this).parent().find('.minus').removeClass('minus-disabled');
        updateCart($input, quantity);
    });

    // -
    $(".minus").die().live('click', function() {
        var $input = $(this).parent().children('input');
        var quantity = parseInt($input.val());
        quantity = quantity - 1;
        if (quantity <= 1) {
            $(this).addClass('minus-disabled');
            quantity = 1;
        }
        updateCart($input, quantity);
    });

    // 输入值
    $(".cart-item-num").die().live('keyup', function() {
        var quantity = parseInt($(this).val());
        if (!quantity || quantity <= 1) {
            $(this).addClass('minus-disabled');
            quantity = 1;
        } else {
            $(this).removeClass('minus-disabled');
            if (quantity > 50) {
                quantity = 50;
            }
        }
        updateCart($(this), quantity);
    });

    function updateCart(obj, num) {
        var team_id = obj.attr('tid');
        var price = obj.attr('price');
        Cart.createCart(team_id, price, num, true);

        var price = Number(obj.parents('tr').find('.team-price').text());
        obj.parents('tr').find('.team-sum').text((price * num).toFixed(2));
        obj.val(num);
        obj.parents('tr').find('.team-check').attr('checked', true);
        setCartTotal();
    }

    //全选
    $("#all-check").die().live('click', function() {
        var state;
        if (this.checked) {
            $(".team-check").attr('checked', true);
            state = 'Y';
        } else {
            $(".team-check").attr('checked', false);
            state = 'N';
        }
        Cart.allSelectCart(state);
        setCartTotal();

    });

    $(".team-check").die().live('click', function() {
        var state;
        if (this.checked) {
            state = 'Y';
        } else {
            state = 'N';
        }
        Cart.selectCart($(this).val(), state);
        setCartTotal();
    });

    //删除
    $(".del-cart").die().live('click', function() {
        var team_id = $(this).attr('tid');
        Cart.delCart(team_id);

        if (ISLOGIN) {
            //登陆直接修改数据库
            var $this = $(this);
            $.get($base_url + '/Cart/delCart/team_id/' + team_id, function(data) {
                if (data.status != 1) {
                    alert(data.info);
                }
            });

        }
        $(this).parents("tr").remove();
        setTopCart();
        setCartTotal();
    });

    function setCartTotal() {
        var data = Cart.getTotal();
        if (data.srcNum == 0) {
            $(".cart-content").html($("#empty-cart").html());
        } else {
            $("#cart-total").text(data.total);
            $("#cart-num").text(data.num);
            $("#is-cookie").val(1);

            if (data.num == 0) {
                $(".btn-buy").attr('disabled', true);
            } else {
                $(".btn-buy").removeAttr('disabled');
            }

            if (data.num != data.srcNum) {
                $("#all-check").attr('checked', false);
            } else {
                $("#all-check").attr('checked', true);
            }
        }
    }
})


// 购物车
var Cart = {
    list: [],
    init: function() {
        //绑定操作
    },
    getCartList: function() {
        var str = $.cookie('cart');
        if (str) {
            this.list = jQuery.parseJSON(str);
        }
    },
    setCartCookie: function() {
        if (this.list.length > 0) {
            $.cookie('cart', JSON.stringify(this.list), {path: '/',expires: 7,domain:'.qtw.com'});
        } else {
            $.cookie('cart', '', {path: '/'});
        }
    },
    createCart: function(team_id, price, num, flag) {
        this.getCartList();
        num = parseInt(num);
        price = price - 0;
        if (this.isExist(team_id)) {
            this.updateCart(team_id, price, num, flag);
        } else {
            this.addCart(team_id, price, num);
        }
        this.setCartCookie();
        // 添加百分点
        try{
            _BFD.AddCart(team_id,price,num);
        }catch(e){
            
        }
        return true;
    },
    isExist: function(team_id) {
        this.getCartList();
        var len = this.list.length;
        for (var i = 0; i < len; i++) {
            var cur = this.list[i];
            if (cur.team_id == team_id) {
                return true;
                break;
            }
        }
        return false;
    },
    addCart: function(team_id, price, num) {
        var data = {
            team_id: team_id,
            num: num,
            price: price,
            state: 'Y'
        }
        if (this.list.length >= 10) {
            alert('购物车只能加入10个团单');
            return false;
        }
        this.list.push(data);
    },
    updateCart: function(team_id, price, num, flag) {
        var len = this.list.length;
        for (var i = 0; i < len; i++) {
            var cur = this.list[i];
            if (cur.team_id == team_id) {
                cur.price = price;
                if (flag) {
                    cur.num = num;
                } else {
                    cur.num += num;
                }
                if(cur.num>50){
                    cur.num=50;
                }
                cur.state = 'Y';
                break;
            }
        }
    },
    delCart: function(team_id) {
        if (this.isExist(team_id)) {
            var len = this.list.length;
            for (var i = 0; i < len; i++) {
                var cur = this.list[i];
                if (cur.team_id == team_id) {
                    this.list.splice(i, 1);
                    break;
                }
            }
            this.setCartCookie();
        }
    },
    getTotal: function() {
        this.getCartList();
        var total = 0;
        var num = 0;
        var len = this.list.length;
        for (var i = 0; i < len; i++) {
            var cur = this.list[i];
            if (cur.state == 'Y') {
                num += 1;
                total += cur.num * cur.price;
            }
        }
        return {
            total: total.toFixed(2),
            num: num,
            srcNum: len
        }
    },
    selectCart: function(team_id, state) {
        if (this.isExist(team_id)) {
            var len = this.list.length;
            for (var i = 0; i < len; i++) {
                var cur = this.list[i];
                if (cur.team_id == team_id) {
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
        for (var i = 0; i < len; i++) {
            var cur = this.list[i];
            cur.state = state;
        }
        this.setCartCookie();
    }
}
function setTopCart() {
    window.setTimeout(function() {
        if (getTopCartList) {
            getTopCartList();
        }
    }, 300);
    var data=Cart.getTotal();
    $("#alert-cart-num").text(data.srcNum);
}

//添加购物车
function createCart(team_id, price, num) {
    num = parseInt(num);
    if (num > 50) {
        num = 50;
    }
    if (!num || num < 1) {
        num = 1;
    }
    
    if(Cart.createCart(team_id, price, num)){
        if (ISLOGIN) {
            $.post($base_url + "/Cart/addCart", {team_id: team_id, num: num});
        }
        setTopCart();
        $("#cart-alert-click").trigger('click');
    }
    return false;
}

// 加入购物车的弹窗,后期根据需求修改
$(function(){
    $("#cart-alert-click").fancybox({
        'showCloseButton':false
    });
    $("#continue-cart").click(function(){
        $.fancybox.close();
    });
})
