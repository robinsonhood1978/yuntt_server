$(function(){
	$('.select-param .tan li').click(function(){
		var key = $(this).parent().attr('ectype');
		var value = $(this).attr('v');
		if(value == '' || value == undefined){
			dropParam(key);
			return false;
		}
		replaceParam(key,value);
        return false;
	});
	
	$('.attr-bottom .show-more').click(function(){
		$(this).parent().parent().children('.by-category').find('dl.hidden').toggle();
		if($(this).find('span').html() == lang.expand){
			$(this).find('span').html(lang.fold);
			$(this).attr('class', 'hide-more');
		} else {
			$(this).find('span').html(lang.expand);
			$(this).attr('class', 'show-more');
			
		}
	});
	$('.search-by .more-it').click(function(){
		$(this).parent().parent().find('.hidden').toggle();
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
	
	$('.view-all-goods').click(function(){
		$(this).parent().parent().parent().children('.store-goods').toggle();
		var icon = $(this).children('i').attr('class');
		if(icon == 'put-icon')
		{
			$(this).children('i').attr('class','drop-icon');
		}
		else
		{
			$(this).children('i').attr('class','put-icon');
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
		if (pKey == key)
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
