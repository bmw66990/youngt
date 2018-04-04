$(function() {
    if ($(".reply").fancybox) {
        $(".reply").fancybox({
            'autoScale': false,
            'scrolling': 'no',
            'transitionIn': 'fade',
            'transitionOut': 'fade',
            'speedIn': 500,
            'speedOut': 500,
            'width': 400,
            'height': 400,
            'type': 'iframe'
        });
    }

    $("#reply").die().live('click', function() {
        var $content = $("#content").val();
        var $id = $('#id').val();
        var $href = $(this).parents('form').attr('action');
        $.post($href, {content: $content, id: $id}, function(res) {
            if (res.code && res.code != 0 && res.error) {
                window.alert(res.error);
            }else{
                window.alert('回复成功！');
            }
            parent.window.$.fancybox.close();
            parent.location.reload();

        }, 'json');
        return false;

    });
})