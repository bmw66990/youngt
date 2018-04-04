function ajaxGet(url, ajaxData, obj) {
    $.get(url, ajaxData, function(data) {
        $(obj).html(data);
    });
}

function dfb_common() {
    try {
        if (window["_BFD"]['BFD_INFO']['user_id']) {
            window["_BFD"]['BFD_USER']['user_id'] = window["_BFD"]['BFD_INFO']['user_id'];
        }
    } catch (e) {

    }
}
