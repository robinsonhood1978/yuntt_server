$(function(){
	$('.page-auth .form .input').focus(function(){
		$(this).removeClass('hover');
		$(this).addClass('focus');
	});
	$('.page-auth .form .input').keydown(function(){
		$(this).siblings('.error').hide();
	});
	$('.page-auth .form .input').hover(function(){
		$(this).removeClass('hover');
		$(this).addClass('hover');
	},function(){
		$(this).removeClass('hover');
	});
	$('.page-auth .form .input').blur(function(){
		$(this).removeClass('hover');
		$(this).removeClass('focus');
	});	
	
	$('.J_GlobalImageAdsBotton').click(function(){
		$(this).hide();
		$(this).parent().slideUp();
	});
	$('.tabOne').mouseover(function(){
		var liIndex = $(this).parent('.tabList').children('.tabOne').index(this);
		var liWidth = $(this).width();
		$(this).addClass('active').siblings().removeClass('active');
		$(this).parent('.tabList').parent('.tabSwitcher').find('.tabContent').eq(liIndex).show().siblings().hide();
		$(this).parent('.tabList').next('.arrow').stop(false,true).animate({'left' : liIndex * liWidth + 'px'},500);
	});
	$('.J_tab li').mouseover(function(){
		$(this).addClass('on').siblings('li').removeClass('on');
		var index = $(this).index();
		$(this).parent().siblings('.tab-content').find('>li:eq('+index+')').show().siblings().hide();
	});
	
	// 只在首页运行（楼层跳转）
	if($('.J_FloorNav').length >　0) 
	{
		$('*[name="jd_floor_one"], *[name="jd_floor_two"]').each(function(index, element) {
			var floorName = $(this).find('.title i').html();
			var titleName = $(this).find('.title span').html();
			$('.J_FloorNav').append('<a href="javascript:;" class="mui-lift-nav-num fp-iconfont" navid="'+$(this).attr('id')+'"><b>'+floorName+'</b><em>'+titleName+'</em></a>');
			
		});
		
		// 获取最后一个楼层的距离顶部的距离
		var lastFloorTop = $('*[area="col-3"]').children('div').length > 0 ?  $('*[area="col-3"]').children('div:last').offset().top : 0;
		
		$(window).bind("scroll",function(){ 
			var fnav_height = $(".J_FloorNav").length > 0 ? $(".J_FloorNav").height() : 0;
			var floor_left = ($(window).width()-1200)/2-37;
			var floor_top = ($(window).height()-fnav_height)/2;
			$(".J_FloorNav").css({'left':floor_left,'top':floor_top});
			
			// 滑到底部消失
			if($(window).scrollTop() <= 1000 || ($(window).scrollTop()>lastFloorTop-fnav_height)) {
				$(".J_FloorNav").fadeOut();
			}
			else 
			{
				$(".J_FloorNav").fadeIn();
			
				$('.floor').parent().each(function(index, element) {
					//layer.msg($(this).offset().top);1354
					if($(window).scrollTop()>$(this).offset().top-$(window).height()/2 && $(window).scrollTop()<$(this).offset().top+$(this).outerHeight()-$(window).height()/2){
						$('.J_FloorNav a[navid='+$(this).attr("id")+']').addClass("current");
						$(this).find('.floor').find('.title').find('i').addClass("current");
					}else{
						$('.J_FloorNav a[navid='+$(this).attr("id")+']').removeClass("current");	
						$(this).find('.floor').find('.title').find('i').removeClass("current");			
					}
				});
			}
		});
		$(".J_FloorNav a").click(function(){
			var pos = $("#"+$(this).attr("navid")).offset().top;
			$('html,body').animate({'scrollTop':pos},500);
			$(this).addClass("current");
		});
	}
});