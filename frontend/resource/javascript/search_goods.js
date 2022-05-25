$(function(){
	$('.sub-images img').click(function(){
		$(this).parent().find('img').each(function(){
			$(this).removeClass('active');
		});
		$(this).addClass('active');
		$('.dl-'+$(this).attr('goods_id')).find('dt img').attr('src',$(this).attr('image_url'));
	});
	
	$("*[ectype='ul_prop'] a").click(function(){
		id = $(this).parents('*[ectype="ul_prop"]').attr('propsed')+'|'+this.id;
		if(id.substr(0,1) == '|') id = id.substr(1);
		replaceParam('props',id);
		return false;
	});
	$("*[ectype='ul_cate'] a").click(function(){
        replaceParam('cate_id', this.id);
        return false;
    });
    $("*[ectype='ul_brand'] a").click(function(){
        replaceParam('brand', this.id);
        return false;
    });
	
    $("*[ectype='ul_price'] a").click(function(){
        replaceParam('price', this.id);
        return false;
    });

    $("*[ectype='ul_region'] a").click(function(){
        replaceParam('s.region_id', this.id);
        return false;
    });
	
	$(".selected-attr a").click(function(){
		dropParam(this.id);
		return false;
	});
	
	$('.filter-price .ui-btn-s-primary').click(function(){
		start_price = $(this).parent().find('input[name="start_price"]').val();
		end_price   = $(this).parent().find('input[name="end_price"]').val();
		if(start_price >= end_price) {
			end_price = Number(start_price) + 200;
		}
		replaceParam('price', start_price+'-'+end_price);
		return false;
	});
	
	// 地区筛选
	$('.J_FilterArea').hover(function(){
		$(this).children('.fa-list').show();
	}, function(){
		$(this).children('.fa-list').hide();
	});
	$('.J_FilterArea .province li a').click(function(){
		region_id = $(this).attr('id');
		$('.J_FilterArea .province li').find('a').each(function(){
			$(this).removeClass('selected');
		});
		$(this).addClass('selected');
		getCity(region_id);
	});
	$('.J_FilterArea .city').on('click', 'li a', function(){
		replaceParam('region_id', this.id);
        return false;
	});
	$('.J_AllArea').click(function(){
		dropParam('region_id');
		return false;
	});
	$('.J_SelProvince').click(function(){
		addr_id = $('.J_FilterArea .province li a.selected').attr('id');
		if(addr_id) {
			replaceParam('region_id', addr_id);
		}
		return false;
	});
	
	 $("[ectype='order_by']").change(function(){
        replaceParam('orderby', this.value);
        return false;
    });

	if($('body').find('.J_ListSort').length >　0) {
		var a = $('.J_ListSort').offset().top;
		$(window).scroll(function () {
			if($(this).scrollTop() > a) 
			{
				$('.J_ListSort').addClass('fixed-show');
			}
			else $('.J_ListSort').removeClass('fixed-show');
		});
	}
	
	// 显示更多
	$('.attr-bottom .show-more').click(function(){
		$(this).parent().parent().find('.toggle').toggle(200);
		if($(this).find('span').html()==lang.expand){
			$(this).find('span').html(lang.fold);
			$(this).attr('class', 'hide-more');
		} else {
			$(this).find('span').html(lang.expand);
			$(this).attr('class', '');
		}
	});
	$('.each .pv .more-it').click(function(){
		$(this).parent('.pv').find('.hidden').toggle();
		if($(this).find('em').html() == lang.more)
		{
			$(this).find('em').html(lang.fold);
			$(this).find('i').addClass('foldUp');
		}
		else
		{
			$(this).find('em').html(lang.more);
			$(this).find('i').removeClass('foldUp');
		}
	});

});

/* 替换参数 */
function replaceParam(key, value)
{
	var params = location.search.substr(1).split('&');

    var found  = false;
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
        if (pKey == key)
        {
            params[i] = key + '=' + value;
            found = true;
        }
    }
    if (!found)
    {
        params.push(key + '=' + value);
    }
	var href = (window.location.href).split('?');
    location.assign(formatUrl(href[0] + '?' + params.join('&')));
}

/* 删除参数 */
function dropParam(key)
{
    var params = location.search.substr(1).split('&');
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
	
		if (pKey == 'props' || pKey == 'brand')
		{
			arr1 = arr[1];
			arr1 = arr1.replace(key,'');
			arr1 = arr1.replace("||",'|');
			if(arr1.substr(0,1)=="|") {
				arr1 = arr1.substr(1,arr1.length-1);
			}
			if(arr1.substr(arr1.length-1,1) == "|") {
				arr1 = arr1.substr(0,arr1.length-1);
			}
			params[i]=pKey + "=" + arr1;
		}
        if (pKey == key || params[i]=='props=' || params[i]=='brand=')
        {
            params.splice(i, 1);
        }
    }
    var href = (window.location.href).split('?');
    location.assign(formatUrl(href[0] + '?' + params.join('&')));
}

function formatUrl(href)
{
	href = href.replace('?&', '?');
	if($.inArray(href.substr(href.length-1,1), ['?', '&']) > -1) {
		return href.substr(0, href.length-1);
	}
	return href;
}

function getCity(region_id)
{
	if($.inArray(region_id, [0,'', undefined]) > -1) {
		region_id = $('.J_FilterArea .province li:first').children('a').attr('id');
	}

	$('.J_GetCity').html('');
	$.getJSON(url(['search/getcity']), {region_id: region_id}, function(data) {
		if(data.done){
			$.each(data.retval, function(i, item){
				if(item.selected==1) style="class='selected'"; else style="";
				$('.J_GetCity').append('<li><a '+style+' href="javascript:;" id="'+item.region_id+'">'+item.region_name+'</a></li>');
			});
		}
	});
}
function setFilterPrice(filter_price)
{
	if(filter_price) {
		filter_price = filter_price.split('-');
		$('input[name="start_price"]').val(filter_price[0]);
		$('input[name="end_price"]').val(filter_price[1]);
	}
}
function setFilterOrder(orderby, o)
{
	var css = '';
	
	if(orderby) 
	{
		var array = orderby.split('|');
		switch (array[1]){
			case 'desc' : 
				css = 'order-down btn-order-cur';
			break;
			case 'asc' :
				css = 'order-up btn-order-cur';
			break;
			default : 
				css = 'order-down-gray';
		}
		$('.btn-order a[ectype="'+array[0]+'"]').attr('class','btn-order-click '+css);
	}
	
	if(o != undefined)
	{
		if(o.id == ''){
			dropParam('orderby')
			return false;
		}
		else
		{
			dd = "|desc";
			if(orderby != '') {
				var array = orderby.split('|');
				if(array[0] == o.id && array[1] == "desc")
					dd = "|asc";
				else dd = "|desc";
			}
			replaceParam('orderby', o.id + dd);
			return false;
		}
	}
}
