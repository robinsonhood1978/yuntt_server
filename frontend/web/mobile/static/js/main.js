$(function(){

	disableLink($(document.body));

	if($('body').find('img.lazyload').length > 0) {
		$("img.lazyload").lazyLoad();
	}
});

function pageBack()
{
	window.history.back();
}

/* 使页面上的链接都无效 */
function disableLink(doc) {
    doc.find("a").attr('href', 'javascript:void(0);').attr('target', '').css('cursor', '');
}