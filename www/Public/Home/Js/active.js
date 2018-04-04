$(function() {
    //back top
    $backClick = $(".backTopBtn");
    $backClick.click(
            function() {
                $("html, body").animate({
                    scrollTop: 0
                }, 300);
            }
    );

    //back top div 
    $(window.document).scroll(
            function() {
                if ($(document).scrollTop() < 100) {
                    scrollTopValu = 50;
                }
                else {
                    scrollTopValu = $(document).scrollTop();
                }
                $("#nav").animate({
                    top: scrollTopValu
                }, 100)
            }
    );
})
