$(function(){
	if($('body').find('.J_GlobalPop').length > 0 && !$('body').find('.J_GlobalPop').is(":hidden")) {
		$(".J_GlobalPop").slide({
			titOnClassName:"hover", type:"menu", titCell:".item", targetCell:".J_GlobalPopSub",effect:"slideDown",
			delayTime:300,triggerTime:0,defaultPlay:false, returnDefault:true
		});
	}
	if($('body').find('.J_SearchFixed').length > 0) {
		$(window).scroll(function() {
        	if ($(window).scrollTop() > 100) {
            	$(".J_SearchFixed").show();
         	} else {
            	$(".J_SearchFixed").hide();
         	}
     	});
	}
	
	if($('body').find('.backtop').length > 0) {
		$(".backtop").hide();
		
    	$(window).scroll(function() {
        	if ($(window).scrollTop() > 320) {
            	$(".backtop").show();
         	} else {
            	$(".backtop").hide();
         	}
     	});
	}
	 $('.backtop').click(function(){
		 $("html,body").animate({scrollTop: 0}, 500);
	 });
	
	$('.J_ShowCategory .allcategory').hover(function(){
		$(this).find('.allcategory-list').show();
	},function(){
		$(this).find('.allcategory-list').hide();
	});
	
	if($('body').find('.J_SearchType li').length > 0) {
		initSearchType($('.J_SearchType li'));
	}
	
	$('.J_SearchType').hover(function(){
		$(this).addClass('hover');
	}, function(){
		$(this).removeClass('hover');
	});
	$('.J_SearchType li').click(function(){
		clickSearchType($(this));
	});
   
	$('.J_GlobalImageAdsBotton').click(function(){
		$(this).hide();
		$(this).parent().slideUp();
	});
	
	$('.J_SwtcherInput').click(function(){
		$(this).toggleClass('checked');
	});

	// 加载头部分类
	loadGcategories();
})

// 页面刷新后初始化搜索框筛选类型
function initSearchType()
{
	var selected = $('.J_SearchType li').parent().find('li.current').html();
	var selectedValue = $('.J_SearchType li').parent().find('li.current').find('span').attr('value');
	var first = $('.J_SearchType li').parent().find('li:first').html().replace('<b></b>', '');
	
	$('.J_SearchType li').parent().find('li.current').html(first).removeClass('current');
	$('.J_SearchType li').parent().find('li:first').html(selected).addClass('current').find('span').after('<b></b>');
	$('.J_SearchType li').parent().find('li:first').show();
	
	$('.J_SearchType li').parent('form').attr('action', url(['search/'+selectedValue]));
	$('.J_SearchType li').parent('form').find('input[name="r"]').val('search/'+selectedValue);
}
//  用户点击搜索框筛选类型事件
function clickSearchType(o)
{
	var selected = o.html();
	var selectedValue = o.find('span').attr('value');
	var first = o.parent().find('li:first').html().replace('<b></b>', '');
		
	o.parent().find('li:first').html(selected).addClass('current').find('span').after('<b></b>');
	o.html(first).removeClass('current');

	o.parents('form').attr('action', url(['search/'+selectedValue]));
	o.parents('form').find('input[name="r"]').val('search/'+selectedValue);
	
	o.parent().removeClass('hover');
}

/* 加载页面头部下级商品分类 */
function loadGcategories()
{
	var allId = '';
	$('.J_Gcategory .item').each(function(i, item) {
		allId += '|' + $(this).attr('item-id');
	});
	if(allId.length == 0) return false;

	$.getJSON(url(['category/list', {allId: allId.substr(1)}]), function(data) {
		if (data.done){
			$.each(data.retval, function(key, list) {
				var ul = '';
				var dl = '';
				$.each(list, function(i, item){
					ul += '<li class="clearfix"><a href="'+url(['search/index', {cate_id: item.cate_id}])+'">'+item.cate_name+'<i>></i></a></li>';
						
					dl += '<dl class="clearfix">'+
								'<dt class="float-left"><a href="'+url(['search/index', {cate_id: item.cate_id}])+'"><strong>'+item.cate_name+'</strong></a></dt>';

					var  dd = '';
					$.each(item.children, function(k, v) {
						dd += '<a href="'+url(['search/index', {cate_id: v.cate_id}])+'">'+v.cate_name+'</a>';
					})
					dl += '<dd class="float-left">'+dd+'</dd></dl>';
				})
				html = '<ul class="clearfix">'+ul+'</ul>'+dl;
				$('div[item-id="'+key+'"]').find('.pop .catlist').html(html);
			})				
		}
	})
}

/**
 * 上传图片前，显示（获取）图像信息
 * @param {obj} obj 
 */
 function getTempPathcallback(obj) {
	getTempPath(obj, function (res) {
		var imgObj = $(obj).parent().find('.type-file-image');
		if (imgObj.find('img').length > 0) {
			imgObj.find('img').attr('src', res);
		} else {
			imgObj.html('<img class="block" src="' + res + '"><span>修改图片</span>');
		}
	});
}