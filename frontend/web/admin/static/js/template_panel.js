/* 初始化编辑状态 */
$(function () {
    /* 使页面上的链接都无效 */
    //disableLink($(document.body));

    /* 编辑状态下样式重写 */
    $('body').addClass('editTmplateMode');
    $('body').find('#template_page .ewraper').show();

    /* 加载面板 */
    $.get(url(['template/panel', null, BACK_URL]), { client: __CLIENT__, page: __PAGE__ }, function (data) {

        /* 在最开头插入面板 */
        $(document.body).prepend(data);

        /* 由于挂件的初始化依赖挂件信息，因此，该初始化必须要放在此处 */
        $("[widget_type='widget']").each(function () { init_widget(this); });
    });

    /* 初始化区域 */
    set_widget_area();

    // 这个是控制拖放的
    $("[widget_type='area']").sortable({
        items: "[widget_type='widget']",
        connectWith: "[widget_type='area']",
        opacity: 0.6,
        forcePlaceholderSize: true,
        // placeholder: 'placeholder',
        // revert: true, 
        update: set_widget_area
    }).disableSelection();

    /* 初始化配置iframe */
    $(document.body).append($('<iframe src="about:blank" style="display:none;height:0px;width:0px;" id="_config_post_iframe_" name="_config_post_iframe_"></iframe>'));
});

function set_widget_area() {
    $("[widget_type='area']").addClass('widgets');
    $("[widget_type='area']").each(function () { init_widget_area(this); });

    // for delete
    $('#template_config').html('');
}
/**
 *    初始挂件区域
 *    @param    none
 *    @return    void
 */
function init_widget_area(area) {
    /* 为了保证样式优先级，此类样式都通过JS来控制 */
    var _has_widget = $(area).find("[widget_type='widget']").length;
    var _has_empty_placeholder = $(area).find('.empty_widget_area').length;
    if (!_has_widget && !_has_empty_placeholder) {
        /* 若没有挂件且没有空占位符，则加上 */
        $(area).prepend('<div class="empty_widget_area">' + lang.empty_area_notice + '</div>');
    }
    if (_has_widget && _has_empty_placeholder) {
        /* 若有挂件且有空占位符，则去掉 */
        $(area).find('.empty_widget_area').remove();
    }
}

/**
 *    初始化挂件
 *
 *    @param    none
 *    @return    void
 */
function init_widget(widget) {
    if ($(widget).css('position') != 'absolute') {
        $(widget).css('position', 'relative');
    }
    /* 可拖动 */
    $(widget).css('cursor', 'move');

    /* 操作栏 */
    var operater = $('<div class="widget_icons"></div>');
    operater.append($('<button title="上移" action="prev" type="button" class="layui-btn layui-btn-sm ml10 mb5"><i class="layui-icon layui-icon-up"></i></button>').click(function () {
        move_widget(widget, this);
    }));
    operater.append($('<button widget_name=' + $(widget).attr('name') + ' title="复制" type="button" class="layui-btn layui-btn-sm ml10 mb5"><i class="layui-icon layui-icon-addition"></i></button>').click(add_widget));
    operater.append($('<button title="删除" type="button" class="layui-btn layui-btn-sm ml10 mb5"><i class="layui-icon layui-icon-close"></i></button>').click(function () {
        var d = DialogManager.create('confirm_delete');
        d.setWidth(400);
        d.setTitle(lang.please_confirm);
        d.setContents('message', {
            type: 'confirm',
            text: lang.delete_widget_confirm,
            onClickYes: function () {
                $(widget).fadeOut('slow', function () {
                    $(widget).remove();
                    set_widget_area();
                });
            }
        });
        d.show('center');
    }));
    operater.append($('<button title="下移" action="next" type="button" class="layui-btn layui-btn-sm ml10"><i class="layui-icon layui-icon-down"></i></button>').click(function () {
        move_widget(widget, this);
    }));
    $(widget).prepend(operater);

    /* 若可配置，则显示配置按钮 */
    if (!(typeof (__widgets[$(widget).attr('name')]) == "undefined")) {
        if (__widgets[$(widget).attr('name')]['configurable']) {
            $(widget).append($('<div class="widget_mask"></div>').click(function () { mask_widget(widget); config_widget(widget); }));
        }
    }
}

/**
 * 保存挂件
 * @param boolean refresh 保存后是否需要刷新配置表单
 */
function save_widget(refresh) {
    var form = $('#_config_widget_form_');
    var widget = '#' + form.attr('widget_id');

    form.ajaxSubmit({
        type: 'post',
        url: form.attr('action'),
        async: false,
        cache: false,
        dataType: "json",
        success: function (rzt) {
            if (rzt.done) {
                //disableLink($(document.body));
                $(widget).html($(rzt.retval).html());

                init_widget(widget);
                mask_widget(widget);
                if (refresh !== false) {
                    config_widget(widget);
                }
            } else {
                layer.open({ shadeClose: true, content: rzt.msg });
            }
        }
    });

    // 点击的是右上角的保存按钮
    if ($(this).attr('id') == 'widget_save_button') {
        layer.msg(lang.save_successed);
    }
}

/**
 * 移动挂件
 * @param {obj} widget 
 * @param {obj} self 
 */
function move_widget(widget, self) {
    var index = $(widget).index();
    var length = $(widget).parent().find('[widget_type="widget"]').length;

    if ($(self).attr('action') == 'prev' && index > 0) {
        $(widget).parent().find('[widget_type="widget"]:eq(' + (index - 1) + ')').before($(widget));
    }
    if ($(self).attr('action') == 'next' && (index + 1 < length)) {
        $(widget).parent().find('[widget_type="widget"]:eq(' + (index + 1) + ')').after($(widget));
    }

    $('[widget_type="area"]').find('.widget_icons button').removeClass('disabled');
    $('[widget_type="area"]').find('[widget_type="widget"]:first .widget_icons button[action="prev"]').addClass('disabled');
    $('[widget_type="area"]').find('[widget_type="widget"]:last .widget_icons button[action="next"]').addClass('disabled');
}

/**
 * 保存页面
 */
function save_page() {

    var d = DialogManager.create('save_page');
    d.setWidth(400);
    d.setTitle(lang.publish);
    d.setContents('<div class="center mt20 mb20 gray padding10">' + lang.saving + '</div>');
    d.show('center');

    /* 创建提交表单 */
    create_save_form();

    /* 信息POST到处理脚本并显示结果 */
    $.post(url(['template/save', { client: __CLIENT__, page: __PAGE__ }, BACK_URL]), $('#_edit_page_form_').serialize(), function (rzt) {
        d.setContents('<div class="center mt20 mb20 gray padding10">' + rzt.msg + '</div>');
    }, 'json');
}

/**
 * 颜色选择器
 * @param {obj} dom
 * @param {fun} callback 
 */
function colorRender(dom, callback) {
    var input = $(dom).parent('.item').find('input[type="hidden"]');
    layui.use('colorpicker', function () {
        var colorpicker = layui.colorpicker;
        colorpicker.render({
            elem: dom,
            size: 'xs',
            color: input.val(),
            //change: function (color) {},
            done: function (color) {
                input.val(color);
                callback(dom, color);
            }
        });
    });
}

/**
 * 滑块选择器
 * @param {obj} dom 
 * @param {fun} callback
 * @param {obj} params 
 */
function slideRender(dom, callback, params) {
    var input = $(dom).parent('.item').find('input[type="hidden"]');
    layui.use('slider', function () {
        var slider = layui.slider;

        slider.render(Object.assign({
            elem: dom,
            theme: '#0d6fb8',
            value: input.val(),
            input: true,
            change: function (value) {
                input.val(value);
                //save_widget(false);
                callback(dom, value);
            }
        }, params));
    });
}

function create_save_form() {
    /* 清空 */
    $('#_edit_page_form_').empty();

    /* 重新生成 */
    var widgets = get_widgets_on_page();
    var config = get_widget_config_on_page();
    for (var widget_id in widgets) {
        $('#_edit_page_form_').append('<input type="checkbox" checked="true" name="widgets[' + widget_id + ']" value="' + widgets[widget_id] + '" />');
    }
    for (var area in config) {
        for (var nk in config[area]) {
            $('#_edit_page_form_').append('<input type="checkbox" checked="true" name="config[' + area + '][]" value="' + config[area][nk] + '" />');
        }
    }
}

/**
 *    获取页面中的所有挂件集合
 *
 *    @return    array
 */
function get_widgets_on_page() {
    var rzt = {};
    $("[widget_type='widget']").each(function (k) {
        rzt[$(this).attr('id')] = $(this).attr('name');
    });

    return rzt;
}

/**
 *    获取页面中所有挂件区域与挂件ID之间的关系
 *
 *    @param    none
 *    @return    void
 */
function get_widget_config_on_page() {
    var rzt = {};
    $("[widget_type='area']").each(function (k) {
        var area = $(this).attr('area');
        var area_widgets = [];
        $(this).find("[widget_type='widget']").each(function (wk) {
            area_widgets.push($(this).attr('id'));
        });
        rzt[area] = area_widgets;
    });

    return rzt;
}

function mask_widget(widget) {
    $('.widget_mask').each(function () {
        $(this).removeClass('show');
    });
    $('.widget_icons').each(function () {
        $(this).css('display', 'none');
    });
    $(widget).find('.widget_mask').addClass('show');
    $(widget).find('.widget_icons').css('display', 'block');
}

/* 配置挂件 */
function config_widget(widget) {
    var _id = $(widget).attr('id');
    var _name = $(widget).attr('name');

    $.get(url(['template/config', null, BACK_URL]), { id: _id, name: _name, client: __CLIENT__, page: __PAGE__ }, function (rzt) {
        var _form = '<div class="wraper"><div class="widget_config_form"><form widget_id=' + _id + ' widget_name=' + _name + ' enctype="multipart/form-data" method="POST" action="' + url(['template/config', { id: _id, name: _name, client: __CLIENT__, page: __PAGE__ }, BACK_URL]) + '" target="_config_post_iframe_" id="_config_widget_form_"><div class="widget_config_form_body">' + rzt + '</div></form></div></div>';
        $('#template_config').html($(_form)).css('display', 'block');
    });
}

function add_widget() {

    var d = DialogManager.create('add_widget');
    d.setWidth(270);
    d.setTitle(lang.loading);
    d.setContents('loading', { text: 'loading...' });
    d.show('center');
    $.getJSON(url(['template/addwidget', null, BACK_URL]), { name: $(this).attr('widget_name'), client: __CLIENT__, page: __PAGE__ }, function (rzt) {
        if (rzt.done) {
            //var widget_id = rzt.retval.widget_id;
            var widget = '#' + rzt.retval.widget_id;
            // if ($('#' + widget_id).length) {
            //     $(_self).click();
            // }
            var _c = $(rzt.retval.contents);
            //disableLink(_c);

            var target = $("[widget_type='area']").find('.widget_mask.show');
            if (target.length > 0) {
                target.parent().after(_c);
            } else {
                $("[widget_type='area']:last").append(_c);
            }

            init_widget(widget);
            mask_widget(widget);
            config_widget(widget);
            set_widget_area();
            DialogManager.close('add_widget');

            if ($(widget).position().top > 300) {
                $('#template_page').scrollTop($(widget).position().top - 300);
            }
        }
        else {
            var _msg = rzt;
            if (rzt.msg) {
                _msg = rzt.msg;
            }
            d.setTitle(lang.error);
            d.setContents('message', {
                type: 'warning',
                text: rzt.msg
            });
        }
    });
}

function disableLink(doc) {
    /* 将所有不是锚点的a过滤掉 */
    doc.find("a").attr('href', 'javascript:void(0);').attr('target', '').css('cursor', 'move');
    doc.find("img.lazyload").each(function () {
        $(this).attr('src', $(this).attr('initial-url'));
    });
}
