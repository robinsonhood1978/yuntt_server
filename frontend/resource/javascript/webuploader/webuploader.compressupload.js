(function($){
	$.fn.compressUpload = function(options){
		var defaults = {auto: true, swf: 'Uploader.swf', server: '', fileVal: 'file', pick: '#picker', multiple: false, compress: true, compressWidth:400, compressHeight:400, crop: true, compressSize: 0, formData: {ajax: 1}, callback : function(){}};
		var opts = $.extend({}, defaults, options);
		
		// 当页面有多个上传按钮的时候，通过此来确定是点击了哪个按钮
		var target = null;
		
		this.click(function(){
			if($(this).attr('id') != '' && $(this).attr('id') != undefined) {
				target = $('#'+$(this).attr('id'));
			} else target = $(opts.pick);
		});
		
		// 初始化Web Uploader
		var uploader = WebUploader.create({
		
			// 选完文件后，是否自动上传。
			auto: opts.auto,
		
			// swf文件路径
			swf: opts.swf,
		
			// 文件接收服务端。
			server: opts.server,
		
			// 选择文件的按钮。可选。
			// 内部根据当前运行是创建，可能是input元素，也可能是flash.
			pick: {
				id: opts.pick,
				multiple: opts.multiple
			},
			
			fileVal: opts.fileVal,
			
			formData: $.extend({fileVal:opts.fileVal}, opts.formData),
		
			// 只允许选择图片文件。
			accept: {
				title: 'Images',
				extensions: 'gif,jpg,jpeg,bmp,png',
				//mimeTypes: 'image/*' // Google BUG
				mimeTypes: 'image/jpg,image/jpeg,image/png'
			},
			runtimeOrder: 'html5,flash',
			
			// 上传前压缩图片（貌似PC无效）
			compress: getCompress(),//不启用压缩
			
			// true为压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
   			resize: false,
    		
			//可重复上传
			duplicate: true   
		});
		/* 没上传之前生成缩微图，浏览器兼容性不是太好
		uploader.on( 'fileQueued', function( file ) {
			uploader.makeThumb( file, function( error, src ) {
				if ( !error ) {
					$(opts.pick).parent().find('img').attr( 'src', src );
				}
			}, 60, 60);
		
		});*/
		
		// 上传后接收服务端返回数据
		uploader.on( 'uploadSuccess', function( file, response ) {
			opts.callback(file, response, opts.pick, target);
		});
		
		function getCompress()
		{
			if(opts.compress != false) 
			{
				return  {
					
					width: opts.compressWidth,
					height: opts.compressHeight,
					
					// 图片质量，只有type为`image/jpeg`的时候才有效。
					quality: 90,
					
					// 是否允许放大，如果想要生成小图的时候不失真，此选项应该设置为false.
					allowMagnify: false,
					
					// 是否允许裁剪。
					crop: opts.crop,
					
					// 是否保留头部meta信息。
					preserveHeaders: true,
					
					// 如果发现压缩后文件大小比原来还大，则使用原来图片
					// 此属性可能会影响图片自动纠正功能
					noCompressIfLarger: false,
					
					// 单位字节，如果图片大小小于此值，不会采用压缩。
					compressSize: opts.compressSize
				}
			}
			
			return false;
		}
  };
	
})(jQuery)