$(function(){
	
	//给每个可编辑的小图片的父元素添加可编辑标题
	$('i[ectype="inline_edit"]').parent().attr('title',lang.editable);
	
	//给需要修改的位置添加修给行为
	$('body').on('click', 'span[ectype="inline_edit"]', function() {
		var controller = $(this).attr('controller');
		var s_value  = $(this).text();
		var s_name   = $(this).attr('fieldname');
		var s_id     = $(this).attr('fieldid');
		var req      = $(this).attr('required');
		var type     = $(this).attr('datatype');
		var max      = $(this).attr('maxvalue');
		$('<input type="text">').css({border:'1px solid #ccc',width:'80%',height:'20px'})
			.attr({value:s_value,size:5})
			.appendTo($(this).parent())
			.focus()
			.select()
			.keyup(function(event){
				if(event.keyCode == 13) {
					if(req){
						if(!required($(this).prop('value'),s_value,$(this))) {
							return;
						}
					}
					if(type) {
						if(!check_type(type,$(this).prop('value'),s_value,$(this))) {
							return;
						}
					}
					if(max) {
						if(!check_max($(this).prop('value'),s_value,max,$(this))) {
							return;
						}
					}
					$(this).prev('span').show().text($(this).prop("value"));
					$.getJSON(url([controller+'/editcol']), {id:s_id,column:s_name,value:$(this).prop('value')},function(data){
						if(!data.done) {
							$('span[fieldname="'+s_name+'"][fieldid="'+s_id+'"]').text(s_value);
						}
						layer.msg(data.msg);
						return false;
					});
					$(this).remove();
				}
			})
			.blur(function(){
				if(req) {
					if(!required($(this).prop('value'),s_value,$(this))) {
						return;
					}
				}
				if(type) {
					if(!check_type(type,$(this).prop('value'),s_value,$(this))) {
						return;
					}
				}
				if(max) {
					if(!check_max($(this).prop('value'),s_value,max,$(this))){
						return;
					}
				}
				$(this).prev('span').show().text($(this).prop('value'));
				$.getJSON(url([controller+'/editcol']), {id:s_id,column:s_name,value:$(this).prop('value')},function(data){
					if(!data.done) {
						$('span[fieldname="'+s_name+'"][fieldid="'+s_id+'"]').text(s_value);
					}
					layer.msg(data.msg);
					return false;
				});
				$(this).remove();
			});
			$(this).hide();
	});
	$('body').on('click', 'em[ectype="inline_edit"]', function(){
		var controller = $(this).attr('controller');
		var i_id    = $(this).attr('fieldid');
		var i_name  = $(this).attr('fieldname');
		var i_class   = $(this).attr('class');
		var i_val   = ($(this).attr('fieldvalue'))== 0 ? 1 : 0;
		$.getJSON(url([controller+'/editcol']), {id:i_id,column:i_name,value:i_val},function(data){
			if(data.done) {   
				if(i_val == 1){
					$('em[fieldname="'+i_name+'"][fieldid="'+i_id+'"]').attr({'class':'yes','fieldvalue':1});
					$('em[fieldname="'+i_name+'"][fieldid="'+i_id+'"]').html('<i class="fa fa-check-circle"></i>是');
					return;
				} else {
					$('em[fieldname="'+i_name+'"][fieldid="'+i_id+'"]').attr({'class':'no','fieldvalue':0});
					$('em[fieldname="'+i_name+'"][fieldid="'+i_id+'"]').html('<i class="fa fa-ban"></i>否');
					return;
				}
			} else {
				layer.msg(data.msg);
			}
		});
	});
	//PC 行内修复
	$('body').on('click', 'span[ectype="editobj"]', function(){
		var controller = $(this).parents('[ectype="table_item"]').attr('controller');
		var i_id    = $(this).parents('[ectype="table_item"]').attr('idvalue');
		var i_name  = $(this).attr('fieldname');
		var i_val   = ($(this).attr('fieldvalue'))== 0 ? 1 : 0;
		var obj = $(this);
		$.getJSON(url([controller+'/editcol']), {id:i_id,column:i_name,value:i_val},function(data){
			if(data.done) {  
				if(i_val == 1){
					obj.attr({'class':'right_ico','fieldvalue':1});
					return;
				} else {
					obj.attr({'class':'wrong_ico','fieldvalue':0});
					return;
				}
			} else {
				layer.msg(data.msg);
			}
		});
	});
	//给需要修改的图片添加异步修改行为
	$('body').on('click', 'i[ectype="inline_edit"]', function(){
		var controller = $(this).attr('controller');
		var i_id    = $(this).attr('fieldid');
		var i_name  = $(this).attr('fieldname');
		var i_css   = $(this).attr('class');
		var i_val   = ($(this).attr('fieldvalue'))== 0 ? 1 : 0;
		$.getJSON(url([controller+'/editcol']), {id:i_id,column:i_name,value:i_val}, function(data){
			if(data.done) {
				if(i_css.indexOf('layui-icon-ok')>-1) {
					if(i_css.indexOf('layui-font-blue')>-1) {
						$('i[fieldid="'+i_id+'"][fieldname="'+i_name+'"]').attr({'class':'layui-icon layui-icon-ok layui-font-gray','fieldvalue':i_val});
					} else {
						$('i[fieldid="'+i_id+'"][fieldname="'+i_name+'"]').attr({'class':'layui-icon layui-icon-ok layui-font-blue','fieldvalue':i_val});
					}
				}
			} else {
				layer.msg(data.msg);
			}
		});
	});
});

//检查提交内容的必须项
function required(str,s_value,jqobj)
{
	if(str == '')
	{
   		jqobj.prev('span').show().text(s_value);
      	jqobj.remove();
      	layer.msg(lang.col_not_empty);
    	return 0;
	}
 	return 1;
}
//检查提交内容的类型是否合法
function check_type(type, value, s_value, jqobj)
{
	if(type == 'number')
 	{
   		if(isNaN(value))
    	{
            jqobj.prev('span').show().text(s_value);
            jqobj.remove();
            layer.msg(lang.only_number);
            return 0;
   		}
 	}
	if(type == 'int')
	{
     	var regu = /^-{0,1}[0-9]{1,}$/;
      	if(!regu.test(value))
   		{
        	jqobj.prev('span').show().text(s_value);
      		jqobj.remove();
          	layer.msg(lang.only_int);
           	return 0;
       	}
  	}
 	if(type == 'pint')
 	{
     	var regu = /^[0-9]+$/;
     	if(!regu.test(value))
   		{
        	jqobj.prev('span').show().text(s_value);
          	jqobj.remove();
          	layer.msg(lang.only_pint);
      		return 0;
    	}
  	}
	return 1;
}
//检查所填项的最大值
function check_max(str,s_value,max,jqobj)
{
	if(parseInt(str) > parseInt(max))
  	{
    	jqobj.prev('span').show().text(s_value);
     	jqobj.remove();
        layer.msg(lang.small_le+max);
      	return 0;
 	}
    return 1;
}