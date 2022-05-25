/*!

 @Name：ShopWind infinite js v0.1
 $Author：shopwind
 $Site：https://www.shopwind.net
 @Date：2017-5-7
 @License：MIT
 $('.aa').infinite({url:'', params:{a:'a', b:'b'}})
 */
;(function($) {
	"use strict";
	var target = null;
	var template = null;
	var locked = false;
	var totalPage = 0;
	var opts = {
		"url": null,
		"page": 1,
		"pageper": 4,
		"maxshow" : 100,
		"params": '',
		"type": 'get',
		"format": 'json',
		"offset": 100,
		"loadType": 'scroll',
		"callback": function(e){}
	}

	var methods = {
		// 初始化
		init: function(options) {
			target = $(this);
			template = target.find('.infinite-template');
			if (options) {
				$.extend(opts, options);
			}
			methods.getData();
			
			// 下拉刷新加载数据
			//if(opts.loadType == 'scroll') {
				$(window).scroll(methods.checkScroll);
			//}
			// 点击加载数据
			//else if(opts.loadType == 'click') {
				target.find('.infinite-more').click(methods.checkClick);
			//}

			var method = {};
			//获取当前页码
			return method.getPager = function() {
					return opts.page;
				},
				//刷新当前页
				method.reload = function() {
					methods.getData();
				},
				//重新加载
				method.onload = function(options) {
					if (options) {
						opts.params = options;
					}
					opts.page = 1;
					methods.getData();
				},
				//获取总页数
				method.getTotalPage = function() {
					return totalPage;
				},
				method
		},
		
		// 请求URL（需考虑伪静态的情况）
		getUrl: function() {
			var uri = '';
			if(opts.url == undefined || opts.url == null || opts.url == '') {
				uri = window.location.href;
			} else {
				uri = opts.url + ((opts.url.indexOf('?') > -1) ? '&' : '?') + methods.getParam();
			}
			var depr = (uri.indexOf('?') > -1) ? '&' : '?';
			return replace_all(uri + depr + 'page=' + opts.page + '&pageper=' + opts.pageper + '&ts=' + Math.random(), '&amp;', '&');
		},
		
		// 请求参数
		getParam: function() {
			var str = '';
			if(opts.type.toUpperCase() == 'GET' && opts.params) {
				$.each(evil(opts.params), function(k, v) {
					if(v != null) {
						str += '&'+k+'='+v;
					}
				});
				str = str.substr(1);
			}
			return str;
		},

		// 请求数据
		getData: function() {
			locked = true;
			var uri = methods.getUrl();
			//alert(uri);
			$.ajax({
				url: uri,
				type: opts.type,
				dataType: opts.format,
				data: opts.params,
				async: false,
				success: function(data) {
					//console.log(data);
					totalPage = parseInt(data.totalPage);
					
					// 延时使页面能看到加载效果
					setTimeout(function() {
						opts.callback(data.result, opts.page, target, template);
						methods.loading(false, data.result.length > 0 ? false : true);
						opts.page++;
						locked = false;
					}, parseInt(opts.page) > 1 ? 100 : 0);
					template.remove();
				}
			});
		},

		// 监听滚动
		checkScroll: function() {
			var scrollTop = $(window).scrollTop() + parseInt(opts.offset); 
			var documentHeight = $(document).height() - $(window).height();
			
			if (scrollTop >= documentHeight) {
				
				methods.loading(true, false);
				
				if(parseInt(opts.page) > totalPage || (parseInt(opts.maxshow) < parseInt(opts.page) * parseInt(opts.pageper))) {
					methods.loading(false, true);
				} else if(locked == false) {
					methods.getData();
				}
			}
		},
		
		// 监听点击加载
		checkClick: function() {
			if(locked == false) {
				methods.loading(true, false);
				methods.getData();
			}
		},
		
		// 显示和隐藏加载效果
		loading: function(show, end) {
			if(show == true) {
				target.find('.infinite-bottom').hide();
				target.find('.infinite-more').hide();
				target.find('.infinite-loading').show();
			}
			else
			{
				target.find('.infinite-loading').hide();
				target.find('.infinite-more').show();
				if(end == true) {
					target.find('.infinite-bottom').show();
					target.find('.infinite-more').hide();
					if($.trim(target.find('.infinite-result').html()) == '') {
						target.find('.infinite-bottom').hide();
						target.find('.infinite-empty').show();
					}
				}
			}
		}
	}

	// $.fn.infinite = function(options) {
	// return init(options, $(this));
	// }

	$.fn.infinite = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method == 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + method + ' does not exist!');
		}
	}
})(jQuery)