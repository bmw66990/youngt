$(function () {
    $(".erg:gt(0)").hide();
    $(".nav ul li").each(function (index) {
        $(this).click(function () {
            $(".erg").eq(index).show().siblings().hide();
        })  
    })

})