$(function() {

    // 菜单设置选中状态
    var setMenu = function() {
        var $localhref = window.location.href.toLowerCase();
        $('dd>div.f-pr').each(function(i) {
              var $href = $(this).find('a').attr('href');
                $href = $href.substring(0, $href.indexOf('.')).toLowerCase();
                 if ($href && $localhref.indexOf($href) >= 0) {
                    $(this).addClass('current');
                }
        });
    }();
   

    $("#editPwd").fancybox({
        'autoScale': false,
        'scrolling': 'no',
        'transitionIn': 'fade',
        'transitionOut': 'fade',
        'speedIn': 500,
        'speedOut': 500,
        'width': 422,
        'height': 322,
        'type': 'iframe'
    });

    $('.close').die().live('click', function() {
        $(this).parents('div.container').hide();
        return false;
    });

    // 弹出层关闭
    $('div.modal button.close,div.modal div.modal-footer button').die().live('click', function() {
        $(this).parents('div.modal').hide();
        return false;
    });

    $("#beginTime").datepicker({
        altField: "#beginTime",
        altFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
        monthNamesShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
        dayNamesMin: ["日", "一", "二", "三", "四", "五", "六"]
    });
    $("#endTime").datepicker({
        altField: "#endTime",
        altFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
        monthNamesShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
        dayNamesMin: ["日", "一", "二", "三", "四", "五", "六"]
    });

})

