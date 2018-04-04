$(function() {
    if ($(".order-deliver-goods-view").fancybox) {
        $(".order-deliver-goods-view").fancybox({
            'autoScale': false,
            'scrolling': 'yes',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 530,
            'height': 500,
            'type': 'iframe'
        });
        $(".order-logistics-view").fancybox({
            'autoScale': false,
            'scrolling': 'yes',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 750,
            'height': 505,
            'type': 'iframe'
        });
    }
    
    // 点击确认发货
    $('#do-order-deliver-goods-btn').die().live('click',function(){
        var $this = $(this);
        var $form = $this.parents('form#do-order-deliver-goods-form');
        
        if ($this.hasClass('disabled')) {
            return false;
        }
        var href =  $form.attr('action');
        var data =  $form.serialize();
        var express_id = $form.find('#express_id').val();
        var express_no = $form.find('#express_no').val();
        if(!$.trim(express_id)){
           parent. window.alert('请选择快递！');
            return false;
        }
         if(!$.trim(express_no)){
              parent. window.alert('请输入快递单号！');
              return false;
        }
        
        $this.addClass('disabled');
        $this.html('正在处理...');
         $.post(href,data, function(res) {
            $this.removeClass('disabled');
            $this.html('确定发货');
            if (res.code && res.code != 0 && res.error) {
                parent.window.alert(res.error);
                return false;
            }
            parent.window.alert('处理成功！');
            parent.window.location.reload();
        }, 'json');
        return false;
        
    })

})
