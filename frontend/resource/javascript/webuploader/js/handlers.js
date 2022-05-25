/**
 * 上传状况的这几个函数（效果）
 * 目前用不到，暂时保留（先注释）
 */
function fileQueued(file, progress_id)
{
    /*
	var $list = $( '#'+progress_id);
	$list.append( '<div id="' + file.id + '" class="item webuploader-upload-item">' +
        '<h4 class="info">' + file.name + '</h4>' +
        '<p class="state">等待上传...</p>' +
    '</div>' );
    */
}

function fileError(type)
{
    // 批量文件数量操过限制
    if(type == 'Q_EXCEED_NUM_LIMIT') {
        alert(lang.not_for_upload_num_limit);
    }
    // 批量文件总大小超过限制
    else if(type == 'Q_EXCEED_SIZE_LIMIT') {
        alert(lang.not_for_upload_size_limit);
    }
}

function uploadProgress(file, percentage)
{
    /*
	var $li = $( '#'+file.id ),
        $percent = $li.find('.progress .progress-bar');

    // 避免重复创建
    if ( !$percent.length ) {
        $percent = $('<div class="progress progress-striped active">' +
          '<div class="progress-bar" role="progressbar" style="width: 0%">' +
          '</div>' +
        '</div>').appendTo( $li ).find('.progress-bar');
    }
    $li.find('p.state').text(percentage * 100 + '% 上传中');
    $percent.css( 'width', percentage * 100 + '%' );
    */
}

function uploadSuccess(file, serverData)
{
    if(!serverData.done) {
        alert(serverData.msg);
        return false;
    }
    add_uploadedfile(serverData.retval);
    
    /*
	$( '#'+file.id ).addClass('upload-state-done');
    $( '#'+file.id ).find('p.state').text('完成');
    */
}
function uploadError(file, reason)
{
   console.log(reason);
   alert(reason);

    /*
	var $li = $( '#'+file.id ),
        $error = $li.find('div.error');

    // 避免重复创建
    if ( !$error.length ) {
        $error = $('<div class="error"></div>').appendTo( $li );
    }
    $error.text('上传失败');
    */
}
function uploadComplete(file) 
{
	/* $( '#'+file.id ).find('.progress').remove(); */
}
function uploadFinished(progress_id)
{
    /*
	var interval = setInterval(function(){
		var result = removeUploadProgress(progress_id);
        if(result){
			clearInterval(interval);
		}
    }, 2000);
    */
}

function removeUploadProgress(progress_id)
{
    /*
	$('#'+progress_id).find('.webuploader-upload-item:visible:last').fadeOut();
	
	var allHide = true;
	$('#'+progress_id).find('.webuploader-upload-item').each(function(index, element) {
        if($(this).css('display') == 'block') {
			allHide = false;
		}
    });
	
    return allHide;
    */
}

/* 以下是编辑器上传图片控制用到的公共JS */
$(function(){
	// 初始化上传
    trigger_uploader();
});

function trigger_uploader()
{
    /* 悬停解释 */
    $('*[ecm_title]').hover(function(){
        $('*[ectype="explain_layer"]').remove();
        $(this).parent().parent().append('<div class="titles" ectype="explain_layer" style="display:none; z-index:999">' + $(this).attr('ecm_title') + '<div class="line"></div></div>');
        $('*[ectype="explain_layer"]').fadeIn();
    },
    function(){
        $('*[ectype="explain_layer"]').fadeOut();
    });

    /* 图片控制 */
    var handle_pic, handler, drop, cover;
    $('*[ectype="handle_pic"]').find('img:first').hover(function(){
		$('#editor_uploader').css('opacity', 0);
		$('*[ectype="handle_pic"]').css('z-index', 999);
        $('*[ectype="explain_layer"]').remove();
        handle_pic = $(this).parents('*[ectype="handle_pic"]');
        handler = handle_pic.find('*[ectype="handler"]');
        var parents = handler.parents();
        handler.show();
        handler.hover(function(){
            $(this).show();
            set_zindex(parents, "999");
        },
        function(){
            $(this).hide();
            //set_zindex(parents, "0");
        });
        set_zindex(parents, '999');

        cover = handler.find('*[ectype="set_cover"]');
        cover.unbind('click');
        cover.click(function(){
            set_cover(handle_pic.attr("file_id"));
        });

        drop = handler.find('*[ectype="drop_image"]');
        drop.unbind('click');
        drop.click(function(){
            drop_image(handle_pic.attr("file_id"));
        });
    },
    function(){
        handler.hide();
        var parents = handler.parents();
        set_zindex(parents, '0');
    });
}
function set_zindex(parents, index){
    $.each(parents,function(i,n){
        if($(n).css('position') == 'relative'){
            $(n).css('z-index',index);
        }
    });
}
/* 以下是编辑器上传图片控制用到的公共JS（END） */