$(function(){
	
	$('.J_Cart').submit(function(){
		if($('.J_SelectGoods:checked').length == 0) {
			layer.msg(lang.select_empty_by_cart);
		} else {
			$(this).submit();
		}
		return false;
	});
	
	// 页面加载完就执行（只在购物车页面需要）
	if($('#page-cart').length > 0) {
		reBuildCheckboxBySelected();
		showFullPerferPlusBySelected();
	}
	
	// 全选
	$('.J_SelectAll').click(function() { 
		selectedAll($(this).prop('checked'));

		// 设置新的总金额
		showCartAmountBySelected();
	});
	
	// 店铺全选
	$('.J_SelectStoreAll').click(function() {
		storeSelectedAll($(this).val(), $(this).prop('checked'));

		// 设置新的总金额
		showCartAmountBySelected();
	});
	
	// 点击某个商品，改变全选和店铺全选的选中状态
	$('.J_SelectGoods').click(function() {
		$(this).prop("checked", $(this).prop("checked"));
		
		// 店铺全选判断
		$('.J_Store-' + $(this).attr('store_id')).find('.J_SelectStoreAll').prop('checked', $('.J_Store-' + $(this).attr('store_id')).find('.J_SelectGoods').not('input:checked').length == 0);
		
		// 全选判断
		$('.J_SelectAll').prop("checked", $('.J_SelectGoods').not("input:checked").length == 0);
		
		// 设置新的总金额
		showCartAmountBySelected();
	});
	
	$(".J_Batch a").click(function(){
		var name = this.name;
		var checked = 0;
		$('.J_SelectGoods').each(function(){
			if($(this).prop("checked")==true){
				srg = $(this).val().split(":");
				if(name == "batch_del"){
					drop_cart_item(srg[0], srg[1]);
				} else {
					collect_goods(srg[2], checked == 0);
				}
				checked++;
			}
		});
		if(!checked) {
			layer.msg(lang.select_empty);
		}

		// 设置新的总金额
		showCartAmountBySelected();
	});
});

/**
 * 购物车商品全选/反选
 * @param {bool} checked 
 */
function selectedAll(checked)
{
	$('.J_SelectGoods').prop('checked', checked);
	$('.J_SelectStoreAll').prop("checked", checked);
	$('.J_SelectAll').prop("checked", checked);
}

/**
 * 店铺全选/反选
 * @param {int} store_id 
 * @param {bool} checked 
 */
function storeSelectedAll(store_id, checked)
{
	$('.J_Store-' + store_id).find('.J_SelectGoods').prop('checked', checked);

	// 全选/反选
	if($('.J_SelectGoods:checked').length == (checked ? $('.J_SelectGoods').length : 0)) {
		$('.J_SelectAll').prop("checked", checked);
	} else {
		$('.J_SelectAll').prop("checked", !checked);
	}
}

// 显示满折满减的差额
function showFullPerferPlusBySelected()
{
	var allDecrease = 0;
	var cartAllAmount = parseFloat($('.J_CartAllAmount').html().replace(/[^\d\.-]/g, ""));

	$('.J_SelectStoreAll').each(function(index, element) {
		var decrease = 0;
		var o = $(this).parents('.J_Store-'+$(this).attr('id'));
		if(o.find('.J_FullPerferAmount').length > 0) {
			var fullPerferAmount = parseFloat(o.find('.J_FullPerferAmount').attr('data-value'));
			var selectStoreAmount = 0;
			
			o.find('.J_GoodsEach').each(function(index, element) {
				if($(this).find('.J_SelectGoods').prop('checked') == true) {
					selectStoreAmount += parseFloat($(this).find('.J_GetSubtotal').attr('price'));
				}
			});
			if(selectStoreAmount < fullPerferAmount) {
				var plus = number_format((fullPerferAmount - selectStoreAmount).toFixed(2), 2);
				o.find('.J_FullPerferPlus').html(',还差'+plus+'元');
			} else {
				o.find('.J_FullPerferPlus').html('');
				
				// 在订单总价后显示节省的满优惠金额
				var fullPerferDetail = {type: o.find('.J_FullPerferAmount').attr('perfer-type'), value: o.find('.J_FullPerferAmount').attr('perfer-value')};
				if(fullPerferDetail.type == 'discount' && (fullPerferDetail.value >= 0 && fullPerferDetail.vaue <=10)) {
					decrease = (selectStoreAmount * (10 - fullPerferDetail.value) * 0.1).toFixed(2);
				}
				else if(fullPerferDetail.type == 'decrease' && (fullPerferDetail.value <= selectStoreAmount)) {
					decrease = fullPerferDetail.value;
				}
				allDecrease += parseFloat(decrease);
				
				$('.J_CartAllAmount').html(price_format((cartAllAmount-allDecrease).toFixed(2)) + "<s class='fs12'>（已优惠"+price_format(allDecrease, 2)+"）</s>");
			}
		}
	});
}

/**
 * 显示购物车商品金额
 * 同步选中到服务器的作用：保持前端和后台的购物车商品的选中状态是一致的，以便处理阶梯价格问题（根据不同的购买数量执行不同的单价）
 */
function showCartAmountBySelected()
{
	// 同步选中项到购物车数据库
	var product_ids = [];
	$('.J_SelectGoods:checked').each(function(index, item) {
		srg = $(item).val().split(":");
		product_ids[index] = srg[1];
	});

	var cartAllAmount = 0;
	$.getJSON(url(['cart/chose', {product_ids: JSON.stringify(product_ids), selected: 1}]), function(data){
		if(data.done){
			$.each(data.retval.items, function(k, v) {
				$('.J_ItemPrice-' + k).html(price_format(v.price));		
				$('.J_ItemQuantity-' + k).html(v.quantity);
				$('.J_ItemSubtotal-' + k).html(price_format(v.subtotal));
				$('.J_ItemSubtotal-' + k).attr('price', parseFloat(v.subtotal).toFixed(2));
				$('.J_CartItem-' + k).find('.J_SelectGoods').prop(v.selected ? true : false);

				if(v.selected) {
					cartAllAmount += parseFloat(v.subtotal);
				}
			});
		
			$('.J_CartAllAmount').html(price_format(cartAllAmount.toFixed(2)));

			reBuildCheckboxBySelected();
			showFullPerferPlusBySelected();
		}
	});
}

function reBuildCheckboxBySelected() {
	$('.J_SelectAll').prop("checked", $('.J_SelectGoods').not('input:checked').length == 0);
	$('.J_SelectStoreAll').each(function(index, element) {
		var o = $(this).parents('.J_Store-' + $(this).attr('id'));
		$(this).prop("checked", o.find('.J_SelectGoods').not("input:checked").length == 0);
	});
}

function drop_cart_item(store_id, product_id)
{
	var tr = $('.J_CartItem-' + product_id);
	//layer.open({content:lang.drop_confirm, btn:[lang.confirm,lang.cancel],
		//yes:function(index){
			$.getJSON(url(['cart/delete', {product_id: product_id}]), function(data){
				if(data.done){
					
					//删除成功
					if(data.retval.kinds == 0){
						window.location.reload();    //刷新
					}
					else
					{
						$('.J_C_T_GoodsKinds').html(data.retval.kinds);
						if(tr.parents('.J_Store-'+store_id).find('.J_GoodsEach').length == 1) {
							tr.parents('.J_Store-'+store_id).remove();
						}
						tr.remove();
					}
					
					// 设置新的总金额
					showCartAmountBySelected();
				}
			});
			//layer.close(index);
		//},
		//no: function(index) {
			//layer.close(index);
		//}
	//});
}

function change_quantity(store_id, product_id, spec_id){
    // 暂存为局部变量，否则如果用户输入过快有可能造成前后值不一致的问题
	var obj = $('#input_item_' + product_id);
	var _v = obj.val();
	if(_v < 1 || isNaN(_v)) {
		layer.msg(lang.invalid_quantity);
		obj.val(obj.attr('orig'));
		return false;
	}
	$.getJSON(url(['cart/update', {spec_id: spec_id, quantity: _v}]), function(data){
        if(data.done){
			
			//更新成功
			obj.attr('changed', _v);
				
			// 设置新的总金额
			showCartAmountBySelected();
        }
        else {
            //更新失败
            layer.msg(data.msg);
            obj.val(obj.attr('changed'));
        }
    });
}

function decrease_quantity(product_id){
    var obj = $('#input_item_' + product_id);
    var orig = Number(obj.val());
    if(orig > 1){
        obj.val(orig - 1);
        obj.keyup();
    }
}
function add_quantity(product_id){
    var obj = $('#input_item_' + product_id);
    var orig = Number(obj.val());
    obj.val(orig + 1);
    obj.keyup();
}

/**
 * 将商品加入到购物车（或立即购买）可在任何处调用此方法实现加入购物车功能 
 * @var animate 开启动画（目前只适用商品详情页）
 */
function add_to_cart(spec_id, quantity, toPay, animate, obj)
{
	if(toPay) {
		$.getJSON(url(['cart/add']), {spec_id:spec_id, quantity:quantity, selected: 1}, function(data) {
			if (data.done) {
				location.href = url(['order/normal']);
			} else {
				layer.msg(data.msg);
			}
		});
	}
	else
	{
		$.getJSON(url(['cart/add']), {spec_id:spec_id, quantity:quantity}, function(data) {
			if (data.done)
			{
				var interval = 0;
				
				if(animate) {
					
					interval = 3000;

					if(obj) {
						var startBox = obj.find('i');
						var startImg = obj.parents('li.goods').find('.main_img');
					} else {
						var startBox = $('.add-to-cart').find('i');
						var startImg = $(".main_img");
					}

					var cartItem = $(".J_F_CardItem"); // 飞到右边栏用该行
					if(cartItem.length <= 0) {
						cartItem = $(".header_cart"); // 飞到头部用该行
					}
					
					var newImg = startImg.clone().addClass('img-clone').css({top: startBox.offset().top, left: startBox.offset().left}).show();
					newImg.appendTo("body").animate({top:cartItem.offset().top, left: cartItem.offset().left, width: 46, height:46}, {duration: 1000,  complete: setInterval(function(){newImg.remove();},2000)});
						
				}
				setTimeout(function(){
					//$('.J_NoGoods').hide();
					$('.J_C_T_GoodsKinds').html(data.retval.kinds);
					
					// var html = '';
					// var template = get_cart_item();
					
					// $.each(data.retval.items,function(k, v) {
					// 	html += sprintf(template.find('.goods-list').html(),
					// 		k, 
					// 		url(['goods/index', {id: v.goods_id}]),
					// 		url_format(v.goods_image),
					// 		v.goods_name,
					// 		url(['goods/index', {id: v.goods_id}]),
					// 		v.goods_name,
					// 		price_format(v.price),
					// 		v.quantity
					// 	);
					// });
					// template.find('.goods-list').html(html);

					// replace_all解决的问题：页面会console凡是有src="[number]"的记录，所以先使用其他参数名再替换回来
					//$('.J_HasGoods').html(sprintf(replace_all(template.html(), 'initial-url', 'src'), data.retval.kinds, price_format(data.retval.amount), url(['cart/index'])));
					
				}, interval);
				
				if(!animate) {
					layer.msg(data.msg);
				}
			}
			else {
				layer.msg(data.msg);
			}
		});
	}
}
// function get_cart_item() {
// 	return $('<div class="cart-template"><h4>最新加入的商品</h4><div class="goods-list"><div class="clearfix list J_CartItem-[1]"><div class="goods-img mt5"><a href="[2]" target="_blank"><img initial-url="[3]" width="40" height="40"></a></div><div class="goods-title"><a title="[4]" href="[5]" target="_blank">[6]</a></div><div class="goods-admin clearfix"><div class="mini-cart-count"><strong class="mini-cart-price">[7]</strong> x[8]</div></div></div></div><div class="total"> <a href="[3]">去购物车查看</a></div></div>').clone(true);
// }
