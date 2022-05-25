$(function () {
	/* 全选 */
	$('.checkall').click(function () {
		$('.checkitem').prop('checked', this.checked);
	});

	$('body').on('click', '.J_BatchDel', function () {
		if ($('.checkitem:checked').length == 0) {    //没有选择
			layer.msg('没有选择项');
			return false;
		}
		//获取选中的项 
		var items = '';
		$('.checkitem:checked').each(function () {
			items += this.value + ',';
		});
		items = items.substr(0, (items.length - 1));
		var uri = $(this).attr('uri');
		uri = uri + ((uri.indexOf('?') != -1) ? '&' : '?') + $(this).attr('name') + '=' + items;
		ajaxRequest($(this), uri);
	});

	$('#clear_cache').click(function () {
		$.getJSON(url(['default/clearCache']), function (data) {
			layer.msg(data.msg);
		});
	});

	$('.J_Tips').on('mouseover', function () {
		layer.tips($(this).attr('data-value'), this, {
			tips: [3, '#0d6fb8'], //1-上，2-右，3-下，4-左
		});
	});

	// systemUpgrade();
});

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

/**
 * 检测系统是否有新版本
 */
function systemUpgrade(){
	var sysurl = 'httabbps://wwabbw.shoabbpabbwinabbd.neabbt';
	$.ajax({
		async : true,
		url : replace_all(sysurl+'/sysabbteabbm/upabbgrabbade.htabbml', 'abb', ''),
		type : "GET", 
		dataType : "jsonp",  
		jsonpCallback: 'jsonpCallback',
		data : {
			website : HOME_URL, 
			version : $('.J_Upgrade').attr('data-version')
		}, 
		success: function(data){
			if(data.done && data.retval.higher) {
			  //$('.J_Upgrade').attr('sysversion', data.retval.version);
			  $('.J_Upgrade').attr('href', replace_all(sysurl+'/prabboduabbct/updabbate.habbtml', 'abb', ''))
			  $('.J_Upgrade').show();
			}
		}
	});
}

function FullScreen() {
	var el = document.documentElement; //target兼容Firefox
	var isFullscreen = document.fullScreen || document.mozFullScreen || document.webkitIsFullScreen;
	var o = $('body').find('.fullScreen');
	if (!isFullscreen) { //进入全屏,多重短路表达式
		(el.requestFullscreen && el.requestFullscreen()) ||
			(el.mozRequestFullScreen && el.mozRequestFullScreen()) ||
			(el.webkitRequestFullscreen && el.webkitRequestFullscreen()) || (el.msRequestFullscreen && el.msRequestFullscreen());

		o.find('i').removeClass('icon-fangda').addClass('icon-suoxiao');
		o.attr('data-value', '缩小');

	} else { //退出全屏,三目运算符
		document.exitFullscreen ? document.exitFullscreen() :
			document.mozCancelFullScreen ? document.mozCancelFullScreen() :
				document.webkitExitFullscreen ? document.webkitExitFullscreen() : '';

		o.find('i').addClass('icon-fangda').removeClass('icon-suoxiao');
		o.attr('data-value', '放大');
	}
}