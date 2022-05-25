/* spec对象 */
function spec(spec_id, spec1, spec2, image, price, mkprice,stock, goods_id)
{
    this.spec_id = spec_id;
    this.spec1 = spec1;
    this.spec2 = spec2;
    this.price = price;
	this.mkprice = mkprice;
    this.stock = stock;
	this.goods_id = goods_id;
	this.image = image;
}

/* goodsspec对象 */
function goodsspec(specs, specQty, defSpec)
{
    this.specs = specs;
    this.specQty = specQty;
    this.defSpec = defSpec;
    this.spec1 = null;
    this.spec2 = null;
    if (this.specQty >= 1)
    {
        for(var i = 0; i < this.specs.length; i++)
        {
            if (this.specs[i].spec_id == this.defSpec)
            {
                this.spec1 = this.specs[i].spec1;
                if (this.specQty >= 2)
                {
                    this.spec2 = this.specs[i].spec2;
                }
                break;
            }
        }
    }

    // 取得某字段的不重复值，如果有spec1，以此为条件
    this.getDistinctValues = function(field, spec1, distinct = true, items = false)
    {
        var values = new Array();
        for (var i = 0; i < this.specs.length; i++)
        {
            var value ;
			if(items == true){
				value = this.specs[i];
			}else{
				value = this.specs[i][field];
			}
            if (spec1 != '' && spec1 != this.specs[i].spec1) continue;
			if (distinct == false){
				values.push(value);
				continue;
			} 
			if($.inArray(value, values) < 0){
				values.push(value);
			}
        }
        return (values);
    }
	
	 // 根据spec1取得 spec2的内容
	 this.getSpecValues = function(spec1)
	 {
		 var values = new Array();
		 if(!spec1){
			 return null;
		 }
		 for (var i = 0; i < this.specs.length; i++)
		 {
			 var value = this.specs[i];
			 if (spec1 == this.specs[i].spec1){
				 if ($.inArray(value, values) < 0)
				 {
					 values.push(value);
				 }
			 }
		 }
		 return (values);
	 }

    // 取得选中的spec
    this.getSpec = function()
    {
        for (var i = 0; i < this.specs.length; i++)
        {
            if (this.specQty >= 1 && this.specs[i].spec1 != this.spec1) continue;
            if (this.specQty >= 2 && this.specs[i].spec2 != this.spec2) continue;

            return this.specs[i];
        }
        return null;
    }

    // 初始化
    this.init = function()
    {
		var spec = this.getSpec();
        if (this.specQty >= 1)
        {
			var specImage = this.getDistinctValues('spec1', '', true, true);
            var spec1Values = this.getDistinctValues('spec1', '');
			var stock = this.getDistinctValues('stock', '' , false);
            for (var i = 0; i < spec1Values.length; i++)
            {
				var aclass ,liclass,canclick,bhidden,spec_img;
				aclass = liclass = canclick = bhidden = spec_img = "";
				if(specImage[i].image){
					spec_img = "<img src='" + url_format(specImage[i].image) + "'/>";
				}else{
					spec_img = "<span>" + spec1Values[i] + "</span>";
				}
				if(this.specQty == 1 && stock[i] == 0 ){
					aclass = "class='none'";
					bhidden = "style='display:none'"
				}else{
					canclick = "onclick='selectSpec(1, this)'";
				}
                if (spec1Values[i] == this.spec1){
					liclass = "class='solid'";
				}else{
					liclass = "class='dotted'";
				}

				$(".handle ul:eq(0)").append("<li " + liclass + canclick + "><a href='javascript:;' title='"+ spec1Values[i] +"' " + aclass + ">" + spec_img + "<b " + bhidden + "></b></a></li>");

            }
        }
        if (this.specQty >= 2)
        {
            var spec2Values = this.getDistinctValues('spec2', this.spec1);
			var stock = this.getDistinctValues('stock', this.spec1, false);
            for (var i = 0; i < spec2Values.length; i++)
            {
				var aclass ,liclass,canclick,bhidden;
				aclass = liclass = canclick = bhidden = "";
				if(stock[i] == 0){
					aclass = "class='none'";
					bhidden = "style='display:none'"
				}else{
					canclick = "onclick='selectSpec(2, this)'";
				}
                if (spec2Values[i] == this.spec2){
					liclass = "class='solid'";
				}else{
					liclass = "class='dotted'";
				}
				
				$(".handle ul:eq(1)").append("<li " + liclass + canclick + "><a href='javascript:;' title='"+ spec2Values[i] +"' " + aclass + "><span>" + spec2Values[i] + "</span><b " + bhidden + "></b></a></li>");
            }
        }
        var spec = this.getSpec();
		setGoodsProInfo(spec);
        $("[ectype='current_spec']").html(spec.spec1 + ' ' + spec.spec2);
		$("[ectype='goods_stock']").html(spec.stock);
		if(spec.image){
			$(".big_pic a img").attr('src',spec.image);
			$(".big_pic a").attr('href',spec.image);
			$(".tiny-pics ul li").attr('class','');
		}
    }
}

/* 选中某规格 num=1,2 */
function selectSpec(num, liObj)
{
    goodsspec['spec' + num] = $(liObj).find('a').attr('title');
	
    $(liObj).attr("class", "solid");
    $(liObj).siblings(".solid").attr("class", "dotted");
	if(num == 1)
	{
		if($(liObj).find('img').length > 0)
		{
			$(".big_pic a img").attr('src',$(liObj).find('img').attr('src'));
			$(".tiny-pics ul li").attr('class','');
		}
		else
		{
			$(".big_pic a img").attr('src',$(".tiny-pics ul li:first").find('img').attr('src'));
			$(".tiny-pics ul li:first").attr('class','pic_hover');
		}
	}
	
    // 当有2种规格并且选中了第一个规格时，刷新第二个规格
    if (num == 1 && goodsspec.specQty == 2)
    {
        goodsspec.spec2 = null;
        $(".aggregate").html("");
        $(".handle ul:eq(1) li[class='handle_title']").siblings().remove();

        var spec2Values = goodsspec.getDistinctValues('spec2', goodsspec.spec1);
		var stock = goodsspec.getDistinctValues('stock', goodsspec.spec1, false);
        for (var i = 0; i < spec2Values.length; i++)
        {
			var aclass ,liclass,canclick;
			aclass = canclick = "";
			if(!stock[i] || stock[i] == 0 ){
				aclass = "class='none'";
			}else{
				canclick = "onclick='selectSpec(2, this)'";
			}

			$(".handle ul:eq(1)").append("<li class='dotted' " + canclick + "><a href='javascript:;' title='"+ spec2Values[i] +"' " + aclass + "><span>" + spec2Values[i] + "</span><b></b></a></li>");
			
        }
    }
    else
    {
        var spec = goodsspec.getSpec();
        if (spec != null)
        {
			setGoodsProInfo(spec);
			$("[ectype='current_spec']").html(spec.spec1 + ' ' + spec.spec2);
            $("[ectype='goods_stock']").html(spec.stock);
        }
    }
}
$(function(){

    goodsspec.init();

    //点击后移动的距离
    var left_num = -61;

    //整个ul超出显示区域的尺寸
    var li_length = ($('.ware_box li').width() + 6) * $('.ware_box li').length - 305;

    $('.right_btn').click(function(){
        var posleft_num = $('.ware_box ul').position().left;
        if($('.ware_box ul').position().left > -li_length){
            $('.ware_box ul').css({'left': posleft_num + left_num});
        }
    });

    $('.left_btn').click(function(){
        var posleft_num = $('.ware_box ul').position().left;
        if($('.ware_box ul').position().left < 0){
            $('.ware_box ul').css({'left': posleft_num - left_num});
        }
    });

    // 加入购物车弹出层
    $('.close_btn').click(function(){
        $('.ware_cen').slideUp('slow');
    });
	
	// 鼠标移到商品小图
	$('.tiny-pics .list li').mouseover(function(){
        $('.tiny-pics .list li').removeClass();
        $(this).addClass('pic_hover');
		$('.big_pic img').attr('src', $(this).find('img').attr('src'));
    });
    
	/* 选中城市后，查询运费 */
	$('.J_City').on('click', 'a', function(){
		$(this).parent().children().removeClass('selected');
		$(this).addClass('selected');
				
		var template_id = $(this).attr('dtid');
		var store_id    = $(this).attr('sid');
		var city_id 	= $(this).attr('id');
		
		// 加载指定城市的运费
		// 传递 store_id,是为了在delivery_templaet_id 为0 的情况下，获取店铺的默认运费模板
		load_city_logistic(template_id,store_id,city_id);
	});
	
	/* 修改数量 */
	$('.buy-quantity a').click(function(){
		var type = $(this).attr('change');
		var _v = Number($('#quantity').val());
		var stock = Number($('*[ectype="goods_stock"]').text());
		if(type == 'reduce')
		{
			if(_v > 1)
			{
				$('#quantity').val(_v-1);
			}
		}
		else if(_v < stock) {
			$('#quantity').val(_v+1);
		}else{
			layer.msg(lang.no_enough_goods);
		}
	});
	$('.buy-quantity #quantity').keyup(function(){
		var _v = Number($('#quantity').val());
		var stock = Number($('*[ectype="goods_stock"]').text());
		if(_v > stock){ 
			layer.msg(lang.no_enough_goods);
			$(this).val(stock);
		}
		if(_v < 1 || isNaN(_v)) {
			layer.msg(lang.invalid_quantity);
			$(this).val(1);
		}
	});
	
	/* 促销倒计时 */
	$.each($('.countdown'),function(){
		var theDaysBox  = $(this).find('.NumDays');
		var theHoursBox = $(this).find('.NumHours');
		var theMinsBox  = $(this).find('.NumMins');
		var theSecsBox  = $(this).find('.NumSeconds');
			
		countdown(theDaysBox, theHoursBox, theMinsBox, theSecsBox)
	});
	
});

function buy(toPay)
{
    if (goodsspec.getSpec() == null)
    {
        layer.msg(lang.select_specs);
        return;
    }
    var spec_id = goodsspec.getSpec().spec_id;

    var quantity = $("#quantity").val();
    if (quantity == '')
    {
        layer.msg(lang.input_quantity);
        return;
    }
    if (parseInt(quantity) < 1 || isNaN(quantity))
    {
        layer.msg(lang.invalid_quantity);
        return;
    }
    add_to_cart(spec_id, quantity, toPay, true);
}

// 加载城市的运费(指定城市id或者根据ip自动判断城市id)
function load_city_logistic(template_id,store_id,city_id)
{
	var html = '';
	$.getJSON(url(['logistic/index', {template_id: template_id, store_id: store_id, city_id: city_id}]), function(data){
		if (data.done){
			var logistic = data.retval;
			$.each(logistic.logistic_fee,function(n,v) {
				html += v.name+':'+v.start_fees+'元 ';
			});
			$('.J_Region').html('至 '+logistic.city_name+'<b></b>');
			$('.postage-info').html(html);
		}
		else
		{
			$('.J_Region').html('至 全国<b></b>');
			$('.postage-info').html(data.msg);	
		}
		$('.postage-area').hide();
	});
}

/* 获取促销商品，会员价格等的优惠信息 */
function setGoodsProInfo(spec)
{
	$.getJSON(url(['goods/promoinfo', {goods_id: spec.goods_id, spec_id: spec.spec_id}]),function(data){
		if (data.done){
			pro_price = data.retval.price;
			pro_type  = data.retval.type;

			$("[ectype='goods_mkprice']").html(spec.mkprice ? price_format(spec.mkprice) : price_format(spec.price));
			$("[ectype='goods_price']").html('<del>'+price_format(spec.price)+'</del>');
			$("[ectype='goods_pro_price']").html(price_format(pro_price)).parents('dl').show();
		} else {
			
			$("[ectype='goods_mkprice']").html(spec.mkprice ? price_format(spec.mkprice) : price_format(spec.price));
			$("[ectype='goods_price']").html(price_format(spec.price));
			$("[ectype='goods_pro_price']").hide();
		}
	});
}

/* 初始化配送地区 */
function setGoodsRegionInfo(obj, parent_id)
{
	var template = '<a href="javascript:;" dtid="[3]" sid="[4]" id="[1]">[2]</a>';
				
	$.getJSON(url(['goods/regioninfo', {parent_id: parent_id}]), function(data) {
		if (data.done){
			var html = '';
			$.each(data.retval, function(i, item) {
				html += sprintf(template, item.region_id, item.region_name, $('.J_Region').attr('dtid'), $('.J_Region').attr('sid'));
			})
			obj.html(html);
		}
	});	
}
