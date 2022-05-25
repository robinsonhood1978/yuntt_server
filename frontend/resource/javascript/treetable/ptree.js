$(function(){
    change_background();
	
    //给图标的加减号添加展开收缩行为
    $('i[ectype="flex"]').click(function(){
		var controller = $(this).attr('controller');
        var status = $(this).attr("status");
        var id = $(this).attr("fieldid");
        //状态是加号的事件
        if(status == 'open')
        {
            var pr = $(this).parent('td').parent('tr');
            $.getJSON(url([controller+'/child']), {id: id}, function(data){
                if(data.done)
                {
                    var str = "";
                    var res = data.retval;
					
                    for(var i = 0; i < res.length; i++)
                    {
                        var src = "";
                        var status = "";
                        src =  "<i class='tv-item' fieldid='"+res[i].vid+"'></i>";
                        
                        //给每一个取出的数据添加是否显示标志
                        if(res[i].status == '1')
                        {
                            status = "<i class='layui-icon layui-icon-ok layui-font-blue' ectype='inline_edit' fieldname='pv_status' fieldid='"+res[i].vid+"' fieldvalue='1' controller='"+controller+"'></i>";
                        }
                        else
                        {
                            status = "<i class='layui-icon layui-icon-ok layui-font-gray' ectype='inline_edit' fieldname='pv_status' fieldid='"+res[i].vid+"' fieldvalue='0' controller='"+controller+"'></i>";
                        }
                        //构造每一个tr组成的字符串，标语添加
                        str+="<tr class='row"+res[i].pid+"'><td class='align_center w30'><input name='vid[]' type='checkbox' class='checkitem' value='"+res[i].vid+"' /></td>"+
                        "<td class='node' width='50%'><i class='preimg'></i>"+src+"<span>"+res[i].pvalue+"</span>";
						if(res[i].is_color == 1) {
							if(res[i].color !=''){
								str+= " <i class='prop-color' title='"+res[i].pvalue+"' style='background:"+res[i].color+"'></i>";
							}
							else {
								str += " <i class='prop-color duocai' title='"+res[i].pvalue+"'></i>";
							}
						}
						str+="</td><td class='align_center'><span ectype='inline_edit' fieldname='pv_sort_order' fieldid='"+res[i].vid+"' datatype='number' controller='"+controller+"' >"+res[i].sort_order+"<i class='layui-icon layui-icon-edit layui-font-14 layui-font-blue'></i></span></td>"+
            			"<td class='align_center'>"+status+"</td>"+
            			"<td class='handler bDiv' style='background:none; width:210px; text-align:left;'><a href='"+url([controller+'/editvalue', {id:res[i].vid}])+"' class='btn blue'><i class='layui-icon layui-icon-edit layui-font-12'></i>"+lang.edit+"</a> <a uri='"+url([controller+'/deletevalue', {id:res[i].vid}])+"' confirm='"+lang.drop_value_confirm+"' class='btn red J_AjaxRequest'><i class='fa fa-trash-o'></i>"+lang.drop+"</a></td></tr>";
                    }
					
                    //将组成的字符串添加到点击对象后面
                    pr.after(str);
                    change_background();
                    //解除行间编辑的绑定事件
                    $('span[ectype="inline_edit"]').unbind('click');
                }
            });
            $(this).attr('class',"layui-icon layui-icon-subtraction");
            $(this).attr('status','close');
        }
        //状态是减号的事件
        if(status == "close") {
            $('.row'+id).hide();
            $(this).attr('class',"layui-icon layui-icon-addition");
            $(this).attr('status','open');
        }
    });
});

function change_background()
{
    $("tbody tr:not(.no_data)").hover( function() {
        $(this).css({background:"#F8FBFD"});
    }, function() {
        $(this).css({background:"#ffffff"});
    });
}