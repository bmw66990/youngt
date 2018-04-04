$(function() {

    // 对浏览器初始化设置
    var init = function() {
        // document.onkeypress = banBackSpace;
        //禁止后退键 作用于IE、Chrome
        // document.onkeydown = banBackSpace;
        window.history.forward(1);//屏蔽浏览器自带的后退键
    }();

    // 点击打印
    $('#print-result').die().live('click', function() {
        var LODOP = getLodop();
        if ((LODOP == null) || (typeof (LODOP.VERSION) == "undefined")) {

        }
        try {
            LODOP.PRINT_INIT("");
            LODOP.SET_PRINT_PAGESIZE(3, "0", "0.2mm", "");
            LODOP.ADD_PRINT_HTM(1, 0, "100%", "1", $("#copy").html());
            LODOP.PRINT();
        } catch (e) {
            return false;
        }

        return false;
    });

});