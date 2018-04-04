
var kindEditorItems = [
    'undo', 'redo', 'copy', 'paste',
    'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
    'justifyfull', 'insertunorderedlist', 
    'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
    'image', 'multiimage',
    'table',  'link'
];


// 校验并提交表单
function ajax_check_submit_from(self) {
    var $this = $(self);
    var $form = $this.parents('form#partner-form');
    var $href = $this.attr('check_url');
    var $data = $form.serialize();
    if ($this.hasClass('disabled')) {
        return false;
    }
    show_message_tip({});
    var btn_html = $this.val();
    $this.addClass('disabled');
    $this.val('正在校验...');
    $.post($href, $data, function(res) {
        $this.removeClass('disabled');
        $this.val(btn_html);
        if (res.code != 0 && res.error) {
            if (show_message_tip) {
                show_message_tip(res);
            } else {
                window.alert('操作失败');
            }
            return false;
        }
        $this.val('正在提交...');
        $form.submit();
        return false;
    });
    return false;
}