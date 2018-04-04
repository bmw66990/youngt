$(function(){
    
    // 选择分店查询
    $('select#partner_select_id').die().live('change', function() {
        var $id = $(this).val();
         window.location.href = $base_url+'/Team/index/partner_id/' + $id;
        return false;
    });
})