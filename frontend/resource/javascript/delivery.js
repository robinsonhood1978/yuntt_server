$(function(){
	
	// 表单验证
	$('.J_SubmitDelivery').click(function(){
		error = 0;
		if($('#delivery_template').find('input[name="name"]').val()==''){
			error++;
			layer.msg(lang.name_empty);
		}
		
		$('#delivery_template').find('input[group="dests"]').each(function(index, element) {
			if($(this).val() == ''){
				error++;
				layer.msg(lang.set_region_pls);
				$(this).parent().parent().css('background', '#F7E3B4');
			}
		});

		$('#delivery_template').find('.default_fee input[type="text"], .other_fee input[type="text"]').each(function(index, element) {
            if($(this).val() == '' || $(this).val() < 0 || isNaN($(this).val())){
				error++;
				$(this).css('border', '1px blue solid');
				layer.msg(lang.fee_and_quantity_must_number);
				$(this).val(1);
			}
        });
		if(error==0){
			$('#delivery_template').submit();
		} else return false;
	});
	
	$('#delivery_template').find('.default_fee input[type="text"], .other_fee input[type="text"]').keyup(function(){
		if($(this).val() >= 0 && !isNaN($(this).val())){
			$(this).css('border', '1px #7F9DB9 solid');
		} else {
			$(this).css('border', '1px blue solid');
			layer.msg(lang.fee_and_quantity_must_number);
			$(this).val(1);
		}
	});
	
	$('body').on("click", '.J_Province input[fileds="province"]', function(){
		if($(this).prop('checked')==true){
			$(this).parent().find('.citylist').find('input[fileds="city"]:enabled').prop('checked',true);
			if($(this).parent().find('.citylist').find('input[fileds="city"]').length > 0 && $(this).parent().find('.citylist').find('input[fileds="city"]:enabled').length == 0) {
				$(this).prop('checked', false).prop('disabled', true);
			}
			
			checkCount = $(this).parent().find('.citylist').find('input[fileds="city"]:checked').length;
			if(checkCount > 0) {
				$(this).parent().find('label[fileds="provinceName"]').find('i').html('('+checkCount+')');
			}
		} else {
			$(this).parent().find('.citylist').find('input[fileds="city"]').prop('checked',false);
			$(this).parent().find('label[fileds="provinceName"]').find('i').html('');
		}
	});
	
	$('body').on("click", '.J_CloseCity', function(){
		$(this).parent().parent().parent().parent().toggleClass('gareas-cur');
		$(this).parent().parent().parent().toggleClass('hidden');
	});
	
	$('body').on("click", '.J_ExpandCity', function(){
		$(this).next('.citylist').toggleClass('hidden');
		$(this).parent().toggleClass('gareas-cur');
		
	});
	
	$('body').on("click", '.J_Province input[fileds="city"]', function(){
		checkCount = 0;
		checkAll = true;
		$(this).parent().parent().find('input[fileds="city"]').each(function(index, element) {
			if($(this).prop('checked')==true){
				checkCount++;
			}
			else{
				checkAll = false;
			}
		});
		$(this).parent().parent().parent().parent().find('input[fileds="province"]').prop('checked', checkAll);
		if(checkCount==0) {
			$(this).parent().parent().parent().parent().find('label[fileds="provinceName"]').find('i').html('');
		} else {
			$(this).parent().parent().parent().parent().find('label[fileds="provinceName"]').find('i').html('('+checkCount+')');
		}
	});
	
	$('body').on("click", '.del_area', function(){
		type = $(this).attr('type');
		
		if($('#'+type+' tbody').find('tr').length > 1){
			$(this).parent().parent().remove();
		} else { // 如果删除的是最后一个 tr,则删除整个 table
			$('#'+type+' table').remove();
		}
	});
	
	$('body').on("click", '.add_area', function(){
		type = $(this).attr('type');
		area_id = new Date().getTime();
		
		tr = '<tr>' + 
				 	'<td class="cell-area">' +
					'	<div class="selected_area J_SelectedAreaName">'+lang.has_no_set_region+'</div>' +
					'	<input type="hidden" group="dests" name="'+type+'_dests[]" value="" />'+
					'	<a href="javascript:;" gs_id="gselector-delivery-'+type+area_id+'" gs_name="delivery_name" gs_callback="gs_callback" gs_title="'+lang.edit_template+'" gs_width="660" gs_type="delivery" gs_store_id="" ectype="gselector"  gs_uri="'+url(['gselector/delivery'])+'" name="gselector-delivery" id="gselector-delivery" class="btn-add-product">'+lang.edit+'</a>' +
					'</td>'+
					'<td><input type="text" class="input" value="1" name="'+type+'_start[]" /></td>'+
					'<td><input type="text" class="input" value="10" name="'+type+'_postage[]" /></td>'+
					'<td><input type="text" class="input" value="1" name="'+type+'_plus[]" /></td>'+
					'<td><input type="text" class="input" value="0" name="'+type+'_postageplus[]" /></td>'+
					'<td><a href="javascript:;" class="del_area" type="'+type+'">'+lang.drop+'</a></td>'+
				 '</tr>';

		// 如果有 thead
		if($('#'+type+' tbody').find('tr').length>0){
			$('#'+type+' tbody').append(tr);
		}
		else
		{
			html = '<table border="0" cellpadding="0" cellspacing="0">'+
				 	'<thead>'+
						'<tr>'+
							'<th class="cell-area">'+lang.transport_to+'</th>'+
							'<th>'+lang.first_piece+'</th>'+
							'<th>'+lang.first_fee+'</th>'+
							'<th>'+lang.step_piece+'</th>'+
							'<th>'+lang.step_fee+'</th>'+
							'<th>'+lang.handle+'</th>'+
						'</tr>'+
					'</thead>'+
					'<tbody>';
				 
			html += tr + '</tbody></table>';
			
			$('#'+type+' .fee_list').append(html);
		}
	});
		
});

function bind(id)
{
	// 获取指定地区运费的地区ID
	$('*[gs_id="'+id+'"]').parent().find('input[group="dests"]').each(function(index, element) {
		dests = $(this).val().split('|');
    });
	
	// 设置选择的地区为选中状态
	$.each(dests, function(i,item){
		$('.J_Province').children().find('input[value="'+item+'"]').prop('checked', true);
	});
	
	// 设置在其他行选中的为不可选状态
	var destsall = '';
	//$('input[group="dests"]').each(function(index, element) { // 不限是ems还是快递行列的，可以用这个
	$('*[gs_id="'+id+'"]').parents('.section').find('input[group="dests"]').each(function(index, element) {
  		if($(this).val() != '') {
			destsall += '|' + $(this).val();
		}
    });
	destsall = destsall.substr(1).split('|');
	for(i=0; i<destsall.length; i++) {
		if($.inArray(destsall[i], dests) < 0) {
			input = $('.J_Province').children().find('input[value="'+destsall[i]+'"]');
			input.prop("disabled", true);
			if(input.attr('fileds') == 'province') {
				input.prop('checked', false);
				input.parent().find('.citylist').find('input[fileds="city"]').prop('disabled', true);
			}
		}
	}
	
	// 如果省选中的话，设置该省下面的所有城市为选中状态
	$('.J_Province input[fileds="province"]').each(function(index, element) {
        if($(this).prop('checked')==true) {
			$(this).parent().find('.citylist').find('input[fileds="city"]').prop('checked',true);
		}
		if($(this).parent().find('.citylist').find('input[fileds="city"]').length > 0 && $(this).parent().find('.citylist').find('input[fileds="city"]:enabled').length == 0) {
			$(this).prop('checked', false).prop('disabled', true);
		}
    });
	
	// 计算城市选中的数量，赋值到省后面
	$('.J_Province').find('.citylist').each(function(index, element) {
        checkCount = $(this).find('input[type="checkbox"]:checked').length;
		if(checkCount > 0) {
			$(this).parent().find('label[fileds="provinceName"]').find('i').html('('+checkCount+')');
		}
    });
}

function gs_callback(id)
{
	dests = AreaName = '';
	$('.J_Province').find('input[fileds="province"]').each(function(index, element) {
		if($(this).prop('checked')==true){
			dests += '|'+$(this).val();
			AreaName += ','+$(this).attr('title');
		}
		else
		{
			// 城市
			$(this).parent().find('.citylist').find('input[fileds="city"]').each(function(index, element) {
				if($(this).prop('checked')==true){
					dests += '|'+$(this).val();
					AreaName += ','+$(this).attr('title');
				}
			});
		}
	});
	if(dests.length==0){
		msg(lang.has_no_region);return false;
	}
	$('*[gs_id="'+id+'"]').parent().find('input[group="dests"]').val(dests.substr(1));
	
	if(AreaName.length==0) {
		AreaName = lang.has_no_set_region;
	} else AreaName = AreaName.substr(1);
	
	$('*[gs_id="'+id+'"]').parent().find('.J_SelectedAreaName').html(AreaName);
	
	DialogManager.close(id);

}

function msg(msg){
    $('.J_Warning').show();
    $('.J_Warning').text(msg);
    window.setTimeout(function(){
        $('.J_Warning').hide();
    },6000)
}
