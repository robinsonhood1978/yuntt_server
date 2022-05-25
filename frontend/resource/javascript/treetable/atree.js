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
                        var if_show = "";
                        //给每一个异步取出的数据添加伸缩图标后者无状态图标
                        if(res[i].switchs)
                        {
                           src =  "<i class='layui-icon layui-icon-addition' ectype='flex' controller='"+controller+"' status='open' fieldid="+res[i].cate_id+" onclick='secajax($(this))'></i>";
                        }
                        else
                        {
                           src =  "<i class='tv-item' fieldid='"+res[i].cate_id+"'></i>";
                        }
                        //给每一个取出的数据添加是否显示标志
                        if(res[i].if_show == '1')
                        {
                            if_show = "<i class='layui-icon layui-icon-ok layui-font-blue' ectype='inline_edit' fieldname='if_show' fieldid='"+res[i].cate_id+"' fieldvalue='1' controller='"+controller+"'></i>";
                        }
                        else
                        {
                            if_show = "<i class='layui-icon layui-icon-ok layui-font-gray' ectype='inline_edit' fieldname='if_show' fieldid='"+res[i].cate_id+"' fieldvalue='0' controller='"+controller+"'></i>";
                        }
						
                        //构造每一个tr组成的字符串，标语添加
                        str+="<tr class='row"+id+"'><td class='align_center w30'><input type='checkbox' class='checkitem' value='"+res[i].cate_id+"' /></td>"+
                        "<td class='node' width='50%'><i class='preimg'></i>"+src+"<span ectype='inline_edit' fieldname='cate_name' fieldid='"+res[i].cate_id+"' required='1' controller='"+controller+"' >"+res[i].cate_name+"<i class='layui-icon layui-icon-edit layui-font-14 layui-font-blue'></i></span></td>"+
						"<td class='align_center'><span ectype='inline_edit' fieldname='sort_order' fieldid='"+res[i].cate_id+"' datatype='number' controller='"+controller+"' >"+res[i].sort_order+"<i class='layui-icon layui-icon-edit layui-font-14 layui-font-blue'></i></span></td>"+
						"<td class='align_center'>"+if_show+"</td>"+
						"<td class='handler bDiv' style='background:none; width:200px; text-align:left;'><a class='btn blue' uri='"+url([controller+'/edit', {id:res[i].cate_id}])+"' ectype='dialog' dialog_id='dialog' dialog_title="+lang.edit+" dialog_width='500'><i class='layui-icon layui-icon-edit layui-font-12'></i>"+lang.edit+"</a> <a class='btn red J_AjaxRequest' uri='"+url([controller+'/delete', {id:res[i].cate_id}])+"' confirm='"+lang.drop_confirm+"'><i class='layui-icon layui-icon-close layui-font-12'></i>"+lang.drop+"</a> <a  class='btn green' uri='"+url([controller+'/add', {pid:res[i].cate_id}])+"' ectype='dialog' dialog_id='dialog' dialog_title="+lang.add_child+" dialog_width='500'><i class='layui-icon layui-icon-add-1 layui-font-12'></i>"+lang.add_child+"</a></td></tr>";
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
//异步请求回来的数据的再次添加异步伸缩行为
function secajax(ob)
{
	var controller = $(ob).attr('controller');
    var status = $(ob).attr("status");
    var id = $(ob).attr("fieldid");
 	if(status == 'open') {
    	var pr  = $(ob).parent('td').parent('tr');
   		var pid = pr.attr('class');
     	var sr  = pr.clone();
     	var td2 = sr.find("td:eq(1)");
   		td2.prepend("<i class='preimg'></i>")
     		.children('span')
   			.remove().end()
    		.find("i[ectype=flex]").remove();
		var td2html = td2.html();

		$.getJSON(url([controller+'/child']), {id: id}, function(data){
		if(data.done) {
 			var str = "";
      		var res = data.retval;
    		for(var i = 0; i < res.length; i++) {
         		var src = "";
          		var if_show = "";
           		var add_child = '';
       			if(res[i].switchs) {
     				src =  "<i class='layui-icon layui-icon-addition' ectype='flex' controller='"+controller+"' status='open' fieldid="+res[i].cate_id+" onclick='secajax($(this))'></i><span ectype='inline_edit' fieldname='cate_name' fieldid='"+res[i].cate_id+"' required='1' controller='"+controller+"' >"+res[i].cate_name+"<i class='layui-icon layui-icon-edit layui-font-14 layui-font-blue'></i></span>";
       			} else {
        			src =  "<i class='tv-item' fieldid='"+res[i].cate_id+"'></i><span ectype='inline_edit' fieldname='cate_name' fieldid='"+res[i].cate_id+"' required='1' controller='"+controller+"' >"+res[i].cate_name+"<i class='layui-icon layui-icon-edit layui-font-14 layui-font-blue'></i></span>";
  				}
          		if(res[i].add_child) {
        			add_child =  " <a class='btn green' uri='"+url([controller+'/add', {pid:res[i].cate_id}])+"' ectype='dialog' dialog_id='dialog' dialog_title="+lang.add_child+" dialog_width='500'><i class='layui-icon layui-icon-add-1 layui-font-12'></i>"+lang.add_child+"</a>";
				}
      			var itd2 = td2html+src;
				if(res[i].if_show == '1') {
   					if_show = "<i class='layui-icon layui-icon-ok layui-font-blue' ectype='inline_edit' fieldname='if_show' fieldid='"+res[i].cate_id+"' fieldvalue='1' controller='"+controller+"'></i>";
    			} else {
      				if_show = "<i class='layui-icon layui-icon-ok layui-font-gray' ectype='inline_edit' fieldname='if_show' fieldid='"+res[i].cate_id+"' fieldvalue='0' controller='"+controller+"'></i>";
          		}
          		str+="<tr class='"+pid+" row"+id+"'><td class='align_center w30'><input type='checkbox' class='checkitem' value='"+res[i].cate_id+"' /></td>"+
        			"<td class='node' width='50%'>"+itd2+"</td>"+
					"<td class='align_center'><span ectype='inline_edit' fieldname='sort_order' fieldid='"+res[i].cate_id+"' datatype='number' controller='"+controller+"' >"+res[i].sort_order+"<i class='layui-icon layui-icon-edit layui-font-14 layui-font-blue'></i></span></td>"+
					"<td class='align_center'>"+if_show+"</td>"+
					"<td class='handler bDiv' style=' background:none; width:200px; text-align:left;'><a class='btn blue' uri='"+url([controller+'/edit', {id:res[i].cate_id}])+"' ectype='dialog' dialog_id='dialog' dialog_title="+lang.edit+" dialog_width='500'><i class='layui-icon layui-icon-edit layui-font-12'></i>"+lang.edit+"</a> <a class='btn red J_AjaxRequest' confirm='"+lang.drop_confirm+"' uri='"+url([controller+'/delete', {id:res[i].cate_id}])+"'><i class='layui-icon layui-icon-close layui-font-12'></i>"+lang.drop+"</a>" + add_child + "</td></tr>";
				}
				pr.after(str);
             	change_background();
            	$('span[ectype="inline_edit"]').unbind('click');
    		}
 		});
    	$(ob).attr('class',"layui-icon layui-icon-subtraction");
   		$(ob).attr('status','close');
    }
 	if(status == "close") {
    	$('.row' + id).hide();
   		$(ob).attr('class',"layui-icon layui-icon-addition");
   		$(ob).attr('status','open');
 	}
}

function change_background()
{
    $("tbody tr:not(.no_data)").hover( function() {
        $(this).css({background:"#F8FBFD"});
    }, function() {
        $(this).css({background:"#ffffff"});
    });
}