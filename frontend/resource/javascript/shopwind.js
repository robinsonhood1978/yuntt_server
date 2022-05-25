$(function () {
	$.fn.serializeJson = function () {
		var serializeObj = {};
		$(this.serializeArray()).each(function () {
			// use condition for url route disable
			if (this.name != 'r') serializeObj[this.name] = this.value;
		});
		return serializeObj;
	};

	/* 通用异步请求(FORM) */
	$('body').on('click', '.J_AjaxSubmit', function () {

		var method = $(this).parents('form').attr('method').toUpperCase();
		var uri = window.location.href;
		var ret_url = $(this).parents('form').find('.J_AjaxFormRetUrl').val();
		var formObj = $(this).parents('form');
		var confirm = $(this).attr('confirm');

		if (confirm) {
			layer.confirm(confirm, { title: lang.notice }, function (index) {
				layer.close(index);
				ajaxSubmit(method, uri, formObj, ret_url, $(this));
			}, function (index) {
				layer.close(index);
				return false;
			});

			return false;
		}

		// 防止重复提交
		$(this).prop('disabled', true);
		ajaxSubmit(method, uri, formObj, ret_url, $(this));
		return false;
	});

	$('body').on('click', '.J_AjaxRequest', function () {
		ajaxRequest($(this));
	});
});

function ajaxRequest(obj, uri, callback) {
	var uri = (typeof uri == 'undefined') ? obj.attr('uri') : uri;
	if ($.trim(obj.attr('confirm')) != '') {
		layer.open({
			content: obj.attr('confirm'), btn: [lang.confirm, lang.cancel],
			yes: function (index) {
				$.getJSON(uri, function (data) {
					if (data.done) {
						if (!isMobileDevice()) {
							layer.msg(data.msg, {
								end: function () {
									if (typeof callback == 'function') {
										return callback();
									}
									window.location.reload();
								}
							});
						}
						else {
							layer.open({
								content: data.msg, time: 3, end: function () {
									if (typeof callback == 'function') {
										return callback();
									}
									window.location.reload();
								}
							});
							layer.close(index);
						}
					}
					else {
						layer.open({ content: data.msg });
					}
				});
			},
			no: function (index) {
				layer.close(index);
			}
		});
	}
	else {
		$.getJSON(uri, function (data) {
			if (data.done) {
				if (!isMobileDevice()) {
					layer.msg(data.msg, {
						end: function () {
							if (typeof callback == 'function') {
								return callback();
							}
							window.location.reload();
						}
					});
				}
				else {
					layer.open({
						content: data.msg, time: 3, end: function () {
							if (typeof callback == 'function') {
								return callback();
							}
							window.location.reload();
						}
					});
					layer.close(index);
				}
			}
			else {
				layer.open({ content: data.msg });
			}
		});
	}
}

/* 异步通用请求 */
function ajaxSubmit(method, uri, formObj, ret_url, oClick, callback) {
	if ((formObj == null) || (formObj == undefined)) formObj = $('<form></form>');
	if ((oClick == null) || (oClick == undefined)) oClick = $('<input></input>');
	if (uri) uri = replace_all(uri, '&amp;', '&');

	formObj.ajaxSubmit({
		type: method,
		url: uri,
		async: false,
		cache: false,
		dataType: "json",
		beforeSubmit: function () {
			//return formObj.valid();
		},
		success: function (data) {
			if (data.done) {

				if (typeof callback == 'function') {
					return callback();
				}

				// 重定向
				var redirect = ''; // 默认刷新当前页

				// 1)先判断是否在PHP设置重定向
				if ($.inArray(data.redirect, [undefined, '']) < 0) {
					redirect = replace_all(data.redirect, '&amp;', '&');
				}
				// 2)判断是否在HTML设置重定向
				else if ($.inArray(ret_url, [undefined, '']) < 0) {
					redirect = replace_all(ret_url, '&amp;', '&');
				}

				if (!isMobileDevice()) {
					layer.msg(data.msg, {
						time: 2000, end: function () {
							go(redirect);
						}
					});
				}
				else {
					layer.open({
						content: data.msg, time: 3, end: function (data) {
							go(redirect);
						}
					});
				}
			}
			else {
				// 重定向
				var redirect = null;

				// 只取PHP中的设置
				if ($.inArray(data.redirect, [undefined, '']) < 0) {
					redirect = replace_all(data.redirect, '&amp;', '&');
				}

				oClick.prop('disabled', false);
				if (!isMobileDevice()) {
					layer.msg(data.msg, {
						time: 2000, end: function () {
							go(redirect);
						}
					});
				}
				else {
					layer.open({
						content: data.msg, time: 3, end: function (data) {
							go(redirect);
						}
					});
				}
			}

		},
		error: function (data) {
			oClick.prop('disabled', false);

			if (isMobileDevice()) {
				layer.open({ content: lang.system_busy, time: 3 });
			} else {
				layer.msg(lang.system_busy);
			}
		}
	});
}

function drop_confirm(msg, uri) {
	layer.open({
		content: msg, btn: [lang.confirm, lang.cancel],
		yes: function (index) {
			window.location = uri;
		},
		no: function (index) {
			layer.close(index);
		}
	});
}

/* 显示Ajax表单 */
function ajax_form(id, title, uri, width, style, opacity, position) {
	var d = DialogManager.create(id);
	d.setTitle(title);
	d.setContents('ajax', uri + (uri.indexOf('?') > -1 ? '&' : '?') + 'dialog_id=' + id);

	if (width) {
		d.setWidth(width);
	}
	if (style) {
		d.setStyle(style);
	}
	if (opacity) {
		ScreenLocker.style.opacity = opacity;
	}
	if (position) {
		d.show(position);
	} else {
		d.show('center');
	}
	return d;
}
function go(redirect) {
	if (redirect == null) { /* NOT TO */ }
	else if (redirect == '') {
		window.location.reload();
	}
	else if (typeof redirect == 'string') {
		window.location = redirect;
	} else window.history.go(-1);
}

// 通过此判断是否为手机端[临时方案]
function isMobileDevice() {
	return (typeof (MOBILE_CLIENT) == 'undefined' || MOBILE_CLIENT != true) ? false : true;
}

/**
 * format URL of JS
 * usage is basically the same as the Url:toRoute () of PHP
 * @desc use as url(['deposit/index', {'id': 5, 'page': 1}, APPURL]);
 * @var params APPURL use the specified application, example: Jump from one application to another
 */
function url(arguments) {
	// 格式化后的地址
	var queryUrl = '';

	var route = arguments[0];
	var params = (typeof (arguments[1]) != 'undefined') ? arguments[1] : {};
	var appUrl = (typeof (arguments[2]) != 'undefined') ? arguments[2] : SITE_URL;

	var query = '';
	for (var i in params) {
		query += '&' + i + '=' + params[i];
	}
	query = query.substr(1);

	// 是否开启路由美化
	if (ENABLE_PRETTY) {

		// 例外处理（跟配置文件common\config\main.php <urlManager.rules> 一致
		if (arguments[0] == 'default/index') route = 'index';
		if (arguments[0] == 'user/login') route = 'login';
		if (arguments[0] == 'user/register') route = 'register';
		if (arguments[0] == 'user/logout') route = 'logout';
		if (arguments[0] == 'search/index') route = 'search/goods';
		if (arguments[0] == 'goods/index') route = 'goods';

		queryUrl = appUrl + '/' + route + '.html' + (query ? '?' + query : '');
	} else {
		queryUrl = appUrl + '/index.php?r=' + route + (query ? '&' + query : '');
	}
	return queryUrl;
}

// 将相对地址转换成完整地址
// 不管当前是什么应用，都使用前台地址（frontendUrl）
// 这个主要是针对上传图片在不同应用中能正确展示
function url_format(url, def) {
	url = (url == '' || url == undefined) ? def : url;

	if (url == '' || url == undefined) {
		return '';
	}
	if (url.indexOf("http") > -1) {
		return url;
	}
	return HOME_URL + '/' + url;
}

// 获取图片上传本地的地址路径，兼容移动端浏览器
function getTempPath(obj, callback) {
	if (window.FileReader) {
		//var ext = obj.value.substring(obj.value.lastIndexOf(".") + 1).toLowerCase();
		var file = obj.files[0];
		var reader = new FileReader();
		reader.readAsDataURL(file);
		reader.onload = function (e) {
			if (typeof callback === "function") {
				return callback(reader.result);
			}
			$(obj).parent().append("<img src='" + reader.result + "' />");
		}
	}
}

function change_captcha(jqObj) {
	$.ajax({
		url: url(['default/captcha', { refresh: 1 }]),
		dataType: 'json',
		cache: false,
		success: function (data) {
			jqObj.attr('src', data.url);
			$('body').data(/*jqObj.attr('hashKey')*/'yiiCaptcha\default\captcha', [data.hash1, data.hash2]);
		}
	});
}

/* 格式化金额 */
function price_format(price) {
	if (typeof (PRICE_FORMAT) == 'undefined') {
		PRICE_FORMAT = '&yen;%s';
	}
	price = number_format(price, 2);
	return PRICE_FORMAT.replace('%s', price);
}

function number_format(num, digit) {
	var _str = '0.00';
	if (undefined != num) {
		_str = num;
	}
	_str = new Number(_str);
	if (isNaN(_str)) {
		return '0.00';
	}
	_str = _str.toFixed(digit) + '';
	var re = /^(-?\d+)(\d{3})(\.?\d*)/;
	while (re.test(_str)) {
		_str = _str.replace(re, "$1,$2$3")
	}

	return _str;
}

/**
 * 数组去重
 * @param {array} arr 
 * @returns array/string
 */
function unique(arr) {
	return Array.from(new Set(arr));
}

/* 收藏商品 */
function collect_goods(goods_id, altMsg) {
	return move_favorite(goods_id, 'goods', altMsg);
}

/* 收藏店铺 */
function collect_store(store_id, altMsg) {
	return move_favorite(store_id, 'store', altMsg);
}
/* 加入收藏 */
function move_favorite(id, type, altMsg) {
	if ($.inArray(type, ['goods', 'store']) < 0) type = 'goods';
	if ($.inArray(altMsg, ['', undefined, null]) > -1) altMsg = true;
	$.getJSON(url(['my_favorite/add', { type: type, item_id: id }]), function (result) {
		if (result.done) {
			if (altMsg) {
				if (isMobileDevice()) {
					layer.open({ content: result.msg, time: 3 });
				} else {
					layer.msg(result.msg);
				}
			}
		}
		else {
			if (altMsg) {
				if (result.loginUrl) {
					layer.open({
						content: result.msg, btn: ['前往登录', '关闭'],
						yes: function (index) {
							window.location = result.loginUrl;
						},
						no: function (index) {
							layer.close(index);
						}
					});
				}
				else layer.open({ content: result.msg });
			}
		}
	});
}

/* 领取优惠券（方便调用）
 * 比如上传一张图片，设置 onclick="couponReceive(coupon_id, $(this));即可领取
 * 如设置：href="javascript:couponReceive(coupon_id, $(this));"  disabled控制无效
 * 比如首页挂件的广告图片，店铺页面的广告图片，都可以设置领取优惠券
 * 实现多处可领券的目的 
 */
function couponReceive(coupon_id, obj) {
	if (!obj.hasClass('disabled')) {
		obj.addClass('disabled');
		$.getJSON(url(['coupon/receive', { id: coupon_id }]), function (data) {
			obj.removeClass('disabled');

			if (isMobileDevice()) {
				layer.open({ content: data.msg, time: 3 });
			} else {
				layer.msg(data.msg);
			}
		});
	}
}

function replace_all(str, s, r) {

	if (typeof str != 'string') return str;

	//g 表示全部替换，没用正则的情况下， replace只能替换第一个
	var reg = new RegExp(s, "g");

	return str.replace(reg, r);
}

/* 类似于PHP的 sprintf */
function sprintf() {
	var num = arguments.length;
	var oStr = arguments[0] || '';
	for (var i = 1; i < num; i++) {
		var pattern = "\\[" + (i) + "\\]";
		var re = new RegExp(pattern, "g");
		oStr = oStr.replace(re, arguments[i]);
	}
	return oStr;
}

// eval string format to JSON object
function evil(fn) {
	if (typeof fn != "object") {
		var Fn = Function;
		return new Fn('return ' + fn)();
	}
	else return fn;
}

function is_phone(str) {
	if (str.match(/^[1][3456789][0-9]{9}$/)) {
		return true;
	}
	return false;
}

function is_email(str) {
	if (str.match(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/)) {
		return true;
	}
	return false;
}

/* 通用倒计时 */
function countdown(theDaysBox, theHoursBox, theMinsBox, theSecsBox) {
	// 避免重复reload
	if (theDaysBox.text() <= 0 && theHoursBox.text() <= 0 && theMinsBox.text() <= 0 && theSecsBox.text() <= 0) {
		return;
	}

	var refreshId = setInterval(function () {
		var currentSeconds = theSecsBox.text();
		var currentMins = theMinsBox.text();
		var currentHours = theHoursBox.text();
		var currentDays = theDaysBox.text();

		// hide day
		if (currentDays == 0) {
			theDaysBox.next('em').hide();
			theDaysBox.hide();
		}

		if (currentSeconds == 0 && currentMins == 0 && currentHours == 0 && currentDays == 0) {
			// if everything rusn out our timer is done!!
			// do some exciting code in here when your countdown timer finishes
			clearInterval(refreshId);
			window.location.reload();

		} else if (currentSeconds == 0 && currentMins == 0 && currentHours == 0) {
			// if the seconds and minutes and hours run out we subtract 1 day
			theDaysBox.html(currentDays - 1);
			theHoursBox.html("23");
			theMinsBox.html("59");
			theSecsBox.html("59");
		} else if (currentSeconds == 0 && currentMins == 0) {
			// if the seconds and minutes run out we need to subtract 1 hour
			theHoursBox.html(currentHours - 1);
			theMinsBox.html("59");
			theSecsBox.html("59");
		} else if (currentSeconds == 0) {
			// if the seconds run out we need to subtract 1 minute
			theMinsBox.html(currentMins - 1);
			theSecsBox.html("59");
		} else {
			theSecsBox.html(currentSeconds - 1);
		}
	}, 1000);
}

/* 通用验证码发送控制（手机/EMail）*/
function time(o, wait) {
	if (wait == 0) {
		o.attr("disabled", false);
		o.val(lang.get_captcha);
		wait = 120;
	} else {
		o.attr("disabled", true);
		o.val(lang.get_captcha_again + "(" + wait + lang.miao_hou + ")");
		wait--;
		setTimeout(function () {
			time(o, wait);
		},
			1000)
	}
}
function send_phonecode(o, params, interval) {
	$.ajax({
		type: "POST",
		url: url(['default/sendCode']),
		data: params,
		dataType: "json",
		success: function (data) {
			if (data.done) {
				time(o, interval);
			} else {
				o.attr('disabled', false);
			}
			if (isMobileDevice()) {
				layer.open({ content: data.msg, time: 3 });
			} else {
				layer.msg(data.msg);
			}
		},
		error: function () {
			if (isMobileDevice()) {
				layer.open({ content: lang.captcha_send_failure, time: 3 });
			} else {
				layer.msg(lang.captcha_send_failure);
			}
		}
	});
}

function send_emailcode(o, params, interval) {
	$.ajax({
		type: "POST",
		url: url(['default/sendEmail']),
		data: params,
		dataType: "json",
		success: function (data) {
			if (data.done) {
				time(o, interval);
			} else {
				o.attr('disabled', false);
			}
			if (isMobileDevice()) {
				layer.open({ content: data.msg, time: 3 });
			} else {
				layer.msg(data.msg);
			}
		},
		error: function () {
			if (isMobileDevice()) {
				layer.open({ content: lang.captcha_send_failure, time: 3 });
			} else {
				layer.msg(lang.captcha_send_failure);
			}
		}
	});
}

/**
 * 弹窗的成功回调
 * @param {string} dialog_id 
 */
function js_success(dialog_id) {
	DialogManager.close(dialog_id);
	var url = window.location.href;
	url = url.indexOf('#') >= 0 ? url.replace(/#/g, '') : url;
	window.location.replace(url);
}

/**
 * 弹窗的错误回调
 * @param {string} str 
 */
function js_fail(str) {
	$('#warning').html('<label class="error">' + str + '</label>');
	$('#warning').show();
}

jQuery.extend({
	getCookie: function (sName) {
		var aCookie = document.cookie.split("; ");
		for (var i = 0; i < aCookie.length; i++) {
			var aCrumb = aCookie[i].split("=");
			if (sName == aCrumb[0]) return decodeURIComponent(aCrumb[1]);
		}
		return '';
	},
	setCookie: function (sName, sValue, sExpires) {
		var sCookie = sName + "=" + encodeURIComponent(sValue);
		if (sExpires != null) sCookie += "; expires=" + sExpires;
		document.cookie = sCookie;
	},
	removeCookie: function (sName) {
		document.cookie = sName + "=; expires=Fri, 31 Dec 1999 23:59:59 GMT;";
	}
});

/*********************************  以下移动端专用  ****************************************/

$(function () {

	// 必须用body以便兼容dialog
	$('body').on('click', '.radioUiStyle', function () {
		if (!$(this).hasClass('disabled')) {
			$(this).parents('.radioUiWraper').find('.radioUiStyle').removeClass('active');
			$(this).parents('.radioUiWraper').find('input[type="radio"]').prop('checked', false);
			$(this).addClass('active');
			$(this).find('input[type="radio"]').prop('checked', true);
		} else $(this).find('input[type="radio"]').prop('checked', false);

		//  处理回调
		var selected = $(this).find('input[type="radio"]:checked');
		var opts = $.extend({}, evil($(this).parents('.pop-layer-common').attr('data-PopLayerItems')));
		if (typeof opts.callback == 'function') {
			opts.callback(selected);
		}
	});

	// 必须用body以便兼容dialog
	$('body').on('click', '.checkboxUiStyle', function () {
		if (!$(this).hasClass('disabled')) {
			$(this).toggleClass('active');
			$(this).find('input[type="checkbox"]').prop('checked', $(this).find('input[type="checkbox"]').prop('checked') == false);
		} else $(this).find('input[type="checkbox"]').prop('checked', false);

		//  处理回调
		var current = $(this).find('input[type="checkbox"]');
		var selected = $(this).find('input[type="checkbox"]:checked');
		var opts = $.extend({}, evil($(this).parents('.pop-layer-common').attr('data-PopLayerItems')));
		if (typeof opts.callback == 'function') {
			opts.callback(current, selected);
		}
	});

	// 通用popLayer弹出层触发
	$('.J_PopLayer').each(function (index, element) {
		$(this).popLayer($(this).attr('data-PopLayer'));
	});
	// 针对PopLayer 需要做初始赋值 
	$('.J_PopLayer__INIT').each(function (index, element) {
		var o = $(this);
		$(this).find('p').html($.trim($(this).next('.pop-layer-common').find('li.active:last').find('.lp span').text()));
		$(this).next('.pop-layer-common').find('li').click(function () {
			o.find('p').html($.trim($(this).find('.lp span').text()));
		});
	});
});

(function ($) {

	// 弹出多个层（不跳转）
	$.fn.ajaxSwitcher = function (options) {
		var defaults = { url: '', title: '请选择', joinStr: ' ', startId: 0, callback: function () { } };
		var opts = $.extend({}, defaults, options);
		var mlsIdInput = this.find('.mls_id');
		var mlsNamesInput = this.find('.mls_names');
		var mls_names = new Array();

		this.find('input').blur().attr('readonly', 'readonly');

		this.click(function () {
			var data = ajaxData(opts.startId);
			outputTemplate(data);

			mls_names.splice(0, mls_names.length);//清空数组
		})

		$(document).on('click', (opts.model) + ' .list li', function () {
			var mls_id = $(this).attr('data-val');
			var data = ajaxData(mls_id);
			mls_names.push($(this).find('span').text());

			if (data != '') {
				prevPid = mls_id;
				outputTemplate(data);
			} else {
				$('.ajaxSwitcher').animate({ 'right': '-110%', 'left': '110%' }, 'fast', 'linear', function () {
					$(this).remove();
				});
				mlsNamesInput.val(mls_names.join(opts.joinStr));
				mlsNamesInput.parent().find('p.mls_names').html(mls_names.join(opts.joinStr)).removeClass('gray');
			}
			mlsIdInput.val(mls_id);

			if (typeof opts.callback == 'function') {
				opts.callback();
			}
		})

		$(document).on('click', (opts.model) + ' .backToPrev', function () {
			$(this).parents('.ajaxSwitcher').animate({ 'right': '-110%', 'left': '110%' }, 'fast', 'linear', function () {
				$(this).remove();
			});
			if (mls_names != '') {
				$(this).parents('.ajaxSwitcher').prev().show();
				mls_names.splice(mls_names.length - 1, mls_names.length);
			}
		})

		function ajaxData(mls_id) {
			$.ajaxSettings.async = false;

			var result = '';
			$.getJSON(opts.url, { pid: mls_id }, function (data) {
				if (data.done) {
					result = data.retval;
				}
			});
			$.ajaxSettings.async = true;

			return result;
		}

		function outputTemplate(data) {
			var template = "<div class='ajaxSwitcher left'><div class='wraper " + (opts.model).substr(1) + "'>" +
				"<div class='hd'><div class='wrap webkit-box'><a href='javascript:;' class='float-left backToPrev'><i></i></a><span class='flex1 title'>" + opts.title + "</span></div></div>";
			template += "<div class='bd'><ul class='list'>";
			$.each(data, function (index, res) {
				template += "<li data-val='" + res.mls_id + "' class='webkit-box'><span class='block flex1 fs14'>" + res.mls_name + "</span><i class='wind-icon-font block'></i></li>";
			})
			template += "</ul></div></div></div>";

			$('body').append(template);

			if ($(opts.model).length > 1) {
				$(opts.model + ':last').siblings(opts.model).hide();
			}
		}
	};

	// 简单的弹出一个层
	$.fn.popLayer = function (options) {
		var defaults = { popLayer: '.popLayer', closeBtn: '.popClosed', resetBtn: '.popReset', masker: '.masker', direction: 'bottom', fixedBody: false, top: 0, bottom: 0, left: 0, right: 0, callback: function (e) { } };
		var opts = $.extend({}, defaults, evil(options));

		this.each(function () {
			var obj = $(this);
			obj.click(function () {
				if (!$(this).hasClass('disabled')) {
					openLayer();
					if (typeof opts.callback == 'function') {
						opts.callback(obj);
					}

					if (opts.fixedBody == true) {
						$('body').css({ 'position': 'fixed', 'left': 0, 'right': 0, 'top': 0 });
					}
				}
			})
		})
		// do not use $('body')
		$(opts.popLayer).on('click', opts.closeBtn, function () {
			if (!$(this).hasClass('disabled')) {
				closeLayer();
			}
		});
		// do not use $('body')
		$(opts.popLayer).on('click', opts.resetBtn, function () {
			closeLayer();
			setTimeout(function () {
				window.location = $(opts.resetBtn).attr('uri');
			}, 500);
		});

		// touchend for iPhone only
		$('body').on('touchend click', opts.masker, function () {
			closeLayer();
		});

		function closeLayer() {
			switch (opts.direction) {
				case 'bottom':
					$(opts.popLayer).animate({ 'bottom': '-150%', 'top': '150%' });
					break;

				case 'top':
					$(opts.popLayer).animate({ 'top': '-150%', 'bottom': '150%' });
					break;

				case 'left':
					$(opts.popLayer).animate({ 'left': '-110%', 'right': '110%' });
					break;

				case 'right':
					$(opts.popLayer).animate({ 'right': '-110%', 'left': '110%' });
					break;

				default:
					$(opts.popLayer).animate({ 'bottom': '-150%', 'top': '150%' });
					break;
			}

			//将遮盖层去掉
			$(opts.masker).fadeOut('slow', function () {
				$(opts.masker).remove();
			});

			$('body').css({ 'position': 'static' });//将固定的body释放
		}

		function openLayer() {
			switch (opts.direction) {
				case 'bottom':
					$(opts.popLayer).animate({ 'bottom': opts.bottom, 'top': opts.top });
					break;

				case 'top':
					$(opts.popLayer).animate({ 'top': opts.top, 'bottom': opts.bottom });
					break;

				case 'left':
					$(opts.popLayer).animate({ 'left': opts.left, 'right': opts.right });
					break;

				case 'right':
					$(opts.popLayer).animate({ 'right': opts.right, 'left': opts.left });
					break;

				default:
					$(opts.popLayer).animate({ 'bottom': opts.bottom, 'top': opts.top });
					break;
			}

			// 将其他遮盖层去掉
			$(opts.masker).remove();

			var maskerClass = (opts.masker).substr(1);
			$('body').append("<div style='background:rgba(0,0,0,0.3);position:fixed; left:0;bottom:0;width:100%; height:100%; display:none; z-index:991' class='" + maskerClass + "'></div>");
			$(opts.masker).fadeIn();
		}
	};
})(jQuery)