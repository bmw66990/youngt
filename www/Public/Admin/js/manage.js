/**
 * Created by daishan on 2015/6/12.
 */
laydate.skin('molv');
var start = {
    elem: '#start',
    format: 'YYYY-MM-DD',
    max: '2099-06-16 23:59:59', //最大日期
    //istime: true,
    istoday: false,
    choose: function(datas){
        end.min = datas; //开始日选好后，重置结束日的最小日期
        end.start = datas //将结束日的初始值设定为开始日
    }
};
var end = {
    elem: '#end',
    format: 'YYYY-MM-DD',
    max: '2099-06-16 23:59:59',
    istoday: false,
    choose: function(datas){
        start.max = datas; //结束日选好后，重置开始日的最大日期
    }
};
laydate(start);
laydate(end);