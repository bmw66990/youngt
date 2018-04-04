/**
 * Created by daishan on 2015/5/21.
 */
$(function () {
    $(":submit").click(function () {
        var id = $('#coupon').val();
        var act = $(this).attr('name');
        if (id) {
            btnStatus(act,'hide');
            var url =$('form').attr('action');
            $.post(url,{id:id,act:act} ,function(data){
                $('#success').hide();
                $('#error').hide();
                showMsg(data.status,data.content);
                btnStatus(act,'show');
            })
        } else {
            showMsg(-1,'请输入券号');
        }
        return false;
    });

    $(".ui-cardnum-show").focus(function () {
        $('#success').hide();
        $('#error').hide();
        $(this).keyup(function () {
            var num = $(this).val();
            num = num.replace(/\s+/g, "");//将输入的内容去掉空格传入ui-cardnum-hide
            $(this).next().val(num);
            num = num.replace(/(.{4})/g, "$1 ");//将ui-cardnum-hide得到的内容每4个字符插入一个空格传回去
            $(this).val(num);
        });
    });
    function showMsg(status,data){
        if(status == 1){
            $('#success').html(data);
            $('#success').show();
        }else{
            $('#error').html(data);
            $('#error').show();
        }
    }
    function btnStatus(act,status){
        if(act == 'check'){
            if(status == 'hide'){
                $('.btn[name=check]').attr('value', '查询中…');
                $('.btn[name=check]').attr('disabled', 'disabled')
            }else if(status == 'show'){
                $('.btn[name=check]').removeAttr('disabled');
                $('.btn[name=check]').attr('value', '查询券号');
            }
        }else if(act == 'consume'){
            if(status == 'hide'){
                $('.btn[name=consume]').attr('value', '验证中…');
                $('.btn[name=consume]').attr('disabled', 'disabled')
            }else if(status == 'show'){
                $('.btn[name=consume]').removeAttr('disabled')
                $('.btn[name=consume]').attr('value', '点击消费');
            }
        }
    }
});
