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
						var menuType = "";
						var linkUrl = "";
                        //给每一个异步取出的数据添加伸缩图标后者无状态图标
                        if(res[i].switchs)
                        {
                           src =  "<i class='layui-icon layui-icon-addition' ectype='flex' controller='"+controller+"' status='open' fieldid="+res[i].id+" ></i>";
                        }
                        else
                        {
                           src =  "<i class='tv-item' fieldid='"+res[i].id+"'></i>";
                        }
						if(res[i].type == 'view') 
						{
							menuType = lang.to_url;
							linkUrl = res[i].link;
						}
						else
						{
							menuType = lang.send_msg;
							linkUrl = '-';
						}

                        //构造每一个tr组成的字符串，标语添加
                        str+="<tr class='row"+id+"'><td class='align_center w30'><input type='checkbox' class='checkitem' value='"+res[i].id+"' /></td>"+
                        "<td class='node'><i class='preimg'></i>"+src+"<span ectype='inline_edit' fieldname='name' fieldid='"+res[i].id+"' required='1' controller='"+controller+"' >"+res[i].name+"<i class='layui-icon layui-icon-edit layui-font-14 layui-font-blue'></i></span></td>"+
						"<td class='align_center'><span>"+menuType+"</span></td>"+
						"<td class='align_center'>"+linkUrl+"</td>"+
						"<td class='align_center'><span ectype='inline_edit' fieldname='sort_order' fieldid='"+res[i].id+"' datatype='number' controller='"+controller+"' >"+res[i].sort_order+"<i class='layui-icon layui-icon-edit layui-font-14 layui-font-blue'></i></span></td>"+
						"<td class='handler bDiv' style='background:none; width:210px; text-align:left;'><a class='btn blue' href='"+url([controller+'/edit', {id:res[i].id}])+"'><i class='layui-icon layui-icon-edit layui-font-12'></i>"+lang.edit+"</a> <a class='btn red J_AjaxRequest' uri='"+url([controller+'/delete', {id:res[i].id}])+"' confirm='"+lang.drop_confirm+"'><i class='layui-icon layui-icon-close layui-font-12'></i>"+lang.drop+"</a></td></tr>";
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