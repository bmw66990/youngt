var tabsSwiper = new Swiper('.swiper-container', {
    speed: 500,
    onSlideChangeStart: function() {
        $(".tabs .active").removeClass('active');
        $(".tabs a").eq(tabsSwiper.activeIndex).addClass('active');
    }
});


$(function() {

    $(".tabs a").on('touchstart mousedown', function(e) {
        e.preventDefault()
        $(".tabs .active").removeClass('active');
        $(this).addClass('active');
        tabsSwiper.swipeTo($(this).index());
    });

    $(".tabs a").click(function(e) {
        e.preventDefault();
    });

    $('div.container-fluid').on('click', 'input#search', function() {
        var href = $(this).attr('load_href');
        window.location.href = href;
        return false;
    });

    $(".mainmenu").click(function() {
        $(".submenu").stop(false, true).hide();
        var submenu = $(this).parent().next();
        submenu.css({
            position: 'absolute',
            top: 40 + 'px',
            left: 0 + 'px',
            zIndex: 1000,
            width: 100 + '%',
            background: '#fff',
        });
        submenu.stop().slideDown(300);

        $('#fullbg').css('display', 'block');
    });

    $("#fullbg").click(function() {
        $(".submenu").stop(false, true).hide();
        $('#fullbg').css('display', 'none');
    });

    $('img').lazyload({
        effect: 'fadeIn'
    });

    // 分类搜索界面
    $('div.container-fluid').on('click', 'a.search-team-list-all', function() {
        var data = $(this).attr('data_value');
        var obj_id = $(this).attr('value_id');
        var $form = $('form#team-search-list-form');
        $form.find(obj_id).val(data);
        return true;
    });

    $('div.container-fluid').on('click', 'a.search-team-list-one', function() {
        var data = $(this).attr('data_value');
        var obj_id = $(this).attr('value_id');
        var $form = $('form#team-search-list-form');
        $form.find(obj_id).val(data);

        // 拼type的值
        var type = $form.find('#type_one').val();
        var type_two = $form.find('#type_two').val();
        if ($.trim(type_two)) {
            type = type + '@' + type_two;
        }
        $form.find('#type').val(type);
        $form.find('#lastId').val('');
        $form.find('#end_id').val('');
        $form.submit();
        return false;
    });


    $('div.container-fluid').on('click', 'a.search-team-list-submit', function() {
        var $form = $('form#team-search-list-form');
        var value_id = $(this).attr('value_id');
        $form.find('#lastId').val('');
        $form.find('#end_id').val('');
        $form.find(value_id).val('');
        $form.submit();
        return false;
    });

    //
    $('div.container-fluid').on('click', 'a.view-other-team-list', function() {
        var show_list = $(this).attr('show_list');
        $(this).hide();
        $(show_list).show();
        return false;
    });

    // next-page-show
    $('div.container-fluid').on('click', 'a.next-page-show', function() {
        var last_id = $(this).attr('last_id');
        var end_id = $(this).attr('end_id');
        var $form = $('form#team-search-list-form');
        $form.find('#lastId').val(last_id);
        $form.find('#end_id').val(end_id);
        $form.submit();
        return false;
    });



})

