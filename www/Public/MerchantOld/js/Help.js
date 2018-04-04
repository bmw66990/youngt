$(function() {
    //js切换副标题内容
    $("#tabs").tabs({active: $('#sid').val()});
    $("#tabs-1").tabs();
    $(".accordion").accordion({
        collapsible: true,
        heightStyle: "content",
    });
});
