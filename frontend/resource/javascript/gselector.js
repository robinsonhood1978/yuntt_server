/* 该JS仅适用于PC端 */
$(function(){

	$('body').on("click", '.J_GselectorAdd', function(){
		update_DATA('add',$(this).attr('goods_id'),$(this).attr('goods_name'));

		$(this).attr('class','J_GselectorDel btn-gselector-del');
		$(this).text(lang.drop);

		update_select_list();
	});
	
	$('body').on("click", '.J_GselectorDel', function(){
		update_DATA('drop',$(this).attr('goods_id'),'');

		$('*[ectype="gselector-goods-list"]').find('a[goods_id="'+$(this).attr('goods_id')+'"]').attr('class', 'J_GselectorAdd btn-gselector-add');
		$('*[ectype="gselector-goods-list"]').find('a[goods_id="'+$(this).attr('goods_id')+'"]').text(lang.selected);
		
		$(this).attr('class', 'J_GselectorAdd btn-gselector-add');
		$(this).text(lang.selected);
				
		update_select_list();
	});
	$('body').on("click", '.J_GoodsDel', function(){
		if($(this).parent().parent().parent().find('li').length == 1) {
			$(this).parent().parent().parent().html('<div class="pt5 pb5 align2 gray-color">'+lang.add_records+'</div>');
		}
		$(this).parent().parent().remove();
		updatePriceTotal();
	});
});

function init()
{
	DATA_LIST_TEMP = [];
	$.each($('*[ectype="goods_list"] li'), function(){
		update_DATA('add',$(this).find('.cell-input input').val(),$(this).find('.cell-title a').text());
	});
	showPage(1);
}

/* 更新弹窗中商品选择列表，更新分页，将已选择的商品设为选中 */
function update_gselector_list(data)
{
	var html = '';
	var goods = $('*[ectype="gselector"]').attr('gs_type');
	if(data.goodsList.length != 0) {
		$.each(data.goodsList,function(i,item){
			html += '<ul class="clearfix"><li class="col-1 center clearfix"><div class="pic float-left"><a href="'+url([goods+'/index', {id: item.goods_id}])+'" target="_blank"><img width="40" height="40" src="'+url_format(item.default_image)+'" /></div><div class="desc float-left"><a href="'+url([goods+'/index', {id: item.goods_id}])+'" target="_blank">'+item.goods_name+'</a></div></li><li class="col-2"><span class="price">'+item.price+'</span></li><li class="col-3">'+item.stock+'</li><li class="col-4 center"><a href="javascript:;" class="J_GselectorAdd btn-gselector-add" goods_name="'+item.goods_name+'" goods_id="'+item.goods_id+'">'+lang.selected+'</a></li></ul>';
		});
	}
	else {
		html = '<div class="notice-word mt10"><p>'+lang.no_records+'</p></div>';
	}
	$('*[ectype="gselector-goods-list"]').html(html);
	
	/* 更新分页 */
	$('*[ectype="gselector-page-info"]').html(data.pagination);
	
	/* 设置选中，将选中的商品修改为删除按钮 */
	$.each(DATA_LIST_TEMP, function(i,item) {
		$('*[ectype="gselector-goods-list"]').find('a[goods_id="'+item.goods_id+'"]').attr('class','J_GselectorDel btn-gselector-del');
		$('*[ectype="gselector-goods-list"]').find('a[goods_id="'+item.goods_id+'"]').text(lang.drop);
	});
}
/* 更新弹窗中已选择的商品的列表 */
function update_select_list(){
	if(DATA_LIST_TEMP.length == 0) {
		$('.J_ListAdded').hide();
		msg(lang.add_records);
	}
	else
	{
		var goods = $('*[ectype="gselector"]').attr('gs_type');
		$('.J_ListAdded').show();
		$('.J_Warning').hide();
		$('*[ectype="sel-list"]').html('');
		$.each(DATA_LIST_TEMP, function(i,item){
			html = '<li><a href="'+url([goods+'/index', {id: item.goods_id}])+'" target="_blank">'+item.goods_name+'</a><a href="javascript:;" class="J_GselectorDel btn-gselector-del" goods_id="'+item.goods_id+'"></a></li>';
			$('*[ectype="sel-list"]').append(html);
		});
	}
}

function showPage(page)
{
	goods_name = $('#gs_goods_name').val();
	sgcate_id = $('#gs_sgcate_id').val();
	
	$.getJSON(url(['gselector/query'+$('*[ectype="gselector"]').attr('gs_type')]), {keyword:goods_name,sgcate_id:sgcate_id,page:page},function(data){
		if(data.done){
			update_gselector_list(data.retval);
			update_select_list();
		}
	});
}

function gs_callback(id)
{
	var goods_ids = '';
	$.each(DATA_LIST_TEMP, function(i,item){
		goods_ids += ',' + item.goods_id;
	});
	ids = goods_ids.substr(1);
	
	if(ids.length == 0){
		$('.J_ListAdded').hide();
        msg(lang.add_records);
	}else{
		gs_query_info(ids);
		DialogManager.close(id);
	}
}
function gs_query_info(goods_ids, toolId)
{
	var goods = $('*[ectype="gselector"]').attr('gs_type');
	$.getJSON(url([$('*[ectype="gselector"]').attr('gs_id')+'/query']), {id:goods_ids, toolId: toolId}, function(data) {
		if(data.done){
			$('*[ectype="goods_list"]').html('');
			if(data.retval.goodsList.length == 0) {
				$('*[ectype="goods_list"]').html('<div class="pt5 pb5 align2 gray-color">'+lang.add_records+'</div>');
			} 
			$.each(data.retval.goodsList,function(i,item){
				$('*[ectype="goods_list"]').append('<li class="clearfix"><p class="cell-input"><input name="selected[]" type="hidden" value="'+item.goods_id+'" /></p><p class="cell-thumb float-left"><a href="'+url([goods+'/index', {id: item.goods_id}])+'" target="_blank"><img src="'+url_format(item.default_image)+'" width="50" height="50" /></a></p><p class="cell-title float-left"><a href="'+url([goods+'/index', {id: item.goods_id}])+'" target="_blank">'+item.goods_name+'</a></p><p class="J_getPrice cell-price float-left" price="'+item.price+'">'+item.price+'</p><p class="cell-action float-left"><a class="J_GoodsDel" href="javascript:;">'+lang.drop+'</a></p></li>');
			});
			updatePriceTotal();
		}
	});
}

function update_DATA(flow, goods_id, goods_name)
{
	if(flow == 'add') {
		DATA_LIST_TEMP.push({goods_id:goods_id,goods_name:goods_name});
	}
	else if(flow == 'drop') {
		DATA_LIST_TEMP_NEW = [];
		$.each(DATA_LIST_TEMP, function(i,item){
			if(item.goods_id != goods_id) {
				DATA_LIST_TEMP_NEW.push(item);
			}
		});
		DATA_LIST_TEMP = DATA_LIST_TEMP_NEW;
	}
}

function msg(msg){
    $('.J_Warning').show();
    $('.J_Warning>p').text(msg);
    //window.setTimeout(function(){
        //$('.J_Warning').hide();
    //},6000)
}

function gs_submit(id,name,callback){
    if(id.length == 0){
        msg('id_mission');
    }
	if(callback.length > 0){
		eval(callback+'("'+id+'")');
	}
}

/* 更新选中的宝贝总价（目前只有搭配套餐用到） */
function updatePriceTotal()
{
	price_min = price_max = 0;
	$('*[ectype="goods_list"] .J_getPrice').each(function() {
		price = $(this).attr('price').split('-');
		if(price[0] != undefined) {
			price_min += Number(price[0]);
		}
		if(price[1] != undefined) {
			price_max += Number(price[1]);
		} else price_max += Number(price[0]);
	});
	price_min = price_min.toFixed(2);
	price_max = price_max.toFixed(2);
	
	if(price_max > price_min) {
		$('.J_priceTotal').val(price_min+'~'+price_max);
	} else $('.J_priceTotal').val(price_min);
}
function add_uploadedfile(file_data)
{
	if(file_data.instance == 'desc_image'){
		$('#desc_images').append('<li style="z-index:4" file_name="'+ file_data.file_name +'" file_path="'+ url_format(file_data.file_path) +'" ectype="handle_pic" file_id="'+ file_data.file_id +'"><input type="hidden" name="desc_file_id[]" value="'+ file_data.file_id +'"><div class="pic" style="z-index: 2;"><img src="' + url_format(file_data.file_path) +'" width="80" height="80" alt="'+ file_data.file_name +'" /></div><div ectype="handler" class="bg" style="z-index: 3;display:none"><p class="operation"><a href="javascript:void(0);" class="cut_in" ectype="insert_editor" ecm_title="'+lang.insert_editor+'"></a><span class="delete" ectype="drop_image" ecm_title="'+lang.drop+'"></span></p></div></li>');
		trigger_uploader();
		if(EDITOR_SWFU.getStats().progressNum == 0){
			window.setTimeout(function(){
				$('#editor_uploader').css('opacity', 0);
				$('*[ectype="handle_pic"]').css('z-index', 999);
			},5000);
		}
	}
}
function drop_image(file_id)
{
    if (confirm(lang.uploadedfile_drop_confirm))
	{ 
		$.getJSON(url([$('*[ectype="gselector"]').attr('gs_id')+'/deleteimage']), {id:file_id}, function(data) {
			
			if (data.done)
 			{
        		$('*[file_id="' + file_id + '"]').remove();
        	}
     		else
   			{
          		layer.msg(data.msg);
   			}
		});
	}
}