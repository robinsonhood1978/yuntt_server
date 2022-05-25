$(function(){
	
	/* 预存款提现 */
	$('.deposit-withdraw .bank-each').click(function(){
		$(this).parent().find('.bank-each').removeClass('selected');
		$(this).parent().find('.bank-list input').prop('checked', false);
		$(this).find('input').prop('checked', true);
		$(this).addClass('selected');
	});
	$('.pop-tips').hover(function(){
		$(this).children('.pop-bd').show();
	},function(){
		$(this).children('.pop-bd').hide();
	});
	
	/* 预存款充值 */
	$('*[ectype="recharge-method"] input[name="method"]').click(function(){
		$(this).parent().find('input[name="method"]').prop('checked',false);
		$(this).next().removeClass('selected');
		$(this).prop('checked', true);
		$(this).addClass('selected');
		$('*[ectype="online"]').hide();
		$('*[ectype="offline"]').hide();
		$('*[ectype="'+$(this).val()+'"]').show();
	})
	
	/* 左栏菜单折叠 */
	$('#left .menu b').click(function(){
		$(this).toggleClass('fold');
		$(this).parent().parent().find('dd').each(function(){
			$(this).slideToggle();
		});
	});
	
    /* 全选 */
    $('.checkall').click(function(){
        var checked = this.checked;
        $('.checkitem').not(':disabled').prop('checked', checked);
        $('.checkall').each(function(){
            $(this).prop('checked', checked);
        })
    });

    /* 批量操作按钮 */
    $('*[ectype="batchbutton"]').click(function(){
        var items = getCheckItemIds();
		if(items)
		{
            var uri = $(this).attr('uri');
            uri += (uri.indexOf('?') > -1 ? '&' : '?') + $(this).attr('name') + '=' + items;
            location.href = uri;
		}
        return false;
    });

    /* 缩小大图片 */
    $('.makesmall').each(function(){
        if(this.complete){
            makesmall(this, $(this).attr('max_width'), $(this).attr('max_height'));
        }else{
            $(this).load(function(){
                makesmall(this, $(this).attr('max_width'), $(this).attr('max_height'));
            });
        }
    });

    $('.su_btn').click(function(){
        if($(this).hasClass('close')){
            $(this).parent().next('.su_block').css('display', '');
            $(this).removeClass('close');
        }
        else{
            $(this).addClass('close');
            $(this).parent().next('.su_block').css('display', 'none');
        }
    });
	
    $('body').on("click", '*[ectype="gselector"]', function() {
        var id = $(this).attr('gs_id');
        var name = $(this).attr('gs_name');
        var callback = $(this).attr('gs_callback');
        var type = $(this).attr('gs_type');
        var store_id = $(this).attr('gs_store_id');
        var title = $(this).attr('gs_title') ? $(this).attr('gs_title') : '';
        var width = $(this).attr('gs_width');
		var style = $(this).attr('gs_class');
		var opacity = $(this).attr('gs_opacity') ? $(this).attr('gs_opacity') : 0.45;
		var uri = $(this).attr('gs_uri').indexOf('?') > -1 ? $(this).attr('gs_uri') + '&' : $(this).attr('gs_uri') + '?';
		var position = $(this).attr('gs_position') ? $(this).attr('gs_position') : 'center';
		
        ajax_form(id, title, uri+'title='+title+'&store_id='+store_id+'&id='+id+'&name='+name+'&callback='+callback, width, style, opacity, position);
        return false;
    });
	
	$('.J_ApplyTab li').click(function(){
		$(this).addClass('on').siblings('li').removeClass('on');
  		var index = $(this).index();
  		$(this).parent().siblings('.tab-content').find('>div:eq(' + index + ')').show().siblings().hide();
	});

});

function check_number(v)
{

    if(isNaN(v))
    {
        layer.msg(lang.only_number);
        return false;
    }
    if(v.indexOf('-') > -1)
    {
        layer.msg(lang.only_number);
        return false;
    }
    return true;
}
function check_required(v)
{
    if(v == '')
    {
        layer.msg(lang.not_empty);
        return false;
    }
    return true;
}

function check_pint(v)
{
    var regu = /^[0-9]{1,}$/;
    if(!regu.test(v))
    {
        layer.msg(lang.only_int);
        return false;
    }
    return true;
}

function check_max(v)
{
    var regu = /^[0-9]{1,}$/;
    if(!regu.test(v))
    {
        layer.msg(lang.only_int);
        return false;
    }
    var max = 255;
    if(parseInt(v) > parseInt(max))
    {
        layer.msg(lang.small+max);
        return false;
    }
    return true;
}



function getCheckItemIds()
{
	/* 是否有选择 */
	if($('.checkitem:checked').length == 0){
		return false;
	}
	 /* 运行presubmit */
	if($(this).attr('presubmit')){
		if(!eval($(this).attr('presubmit'))){
			return false;
		}
	}
	var items = '';
	$('.checkitem:checked').each(function(){
		items += this.value + ',';
	});
	items = items.substr(0, (items.length - 1));
		
	return items;
}