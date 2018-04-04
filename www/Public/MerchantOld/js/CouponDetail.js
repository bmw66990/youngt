$(function(){
    
    // 选择分店查询
    $('select#partner_select_id').die().live('change', function() {
        var $id = $(this).val();
        var $form = $('#couponDetailSearch');
        $form.find('#partner_id').val($id);
        $form.submit();
        return false;
    });
    
    // 选择排序 查询
    $('a.order').die().live('click',function(){
        var $id = $(this).attr('lang');
        var $form = $('#couponDetailSearch');
        $form.find('#order').val($id);
        $form.submit();
        return false;
    });
})