let statusThread = false;

$(document).mouseup(function (e) {
    var container = $(".show_6a2");
    if (container.has(e.target).length === 0){
        container.removeClass('show_6a2');
    }
});

$('#jvlabelWrap').on('click', function () {
    $(this).css({
        'animation-name':'Label_OPEN_WIDGET_b3b',
        'display':'none'
    });
    $('#jcont').css({
        'animation-name':'WidgetContainer_OPEN_WIDGET_e34',
        'display': 'block'
    });
    scrollDown();
});

$('#jivo_close_button').on('click', function () {
    $('#jvlabelWrap').css({
        'display':'block',
        'animation-name':'Label_CLOSE_WIDGET_51e',
    });
    $('#jcont').css({
        'animation-name':'WidgetContainer_CLOSE_WIDGET_4df',
        'display': 'none'
    });
});

$('.iconEmoji_8a0').on('click', function () {
    $('.popup_337').addClass('show_6a2');
});

$('.emojiIcon_de7').on('click', function () {
    $('.popup_337').removeClass('show_6a2');
    $('.inputField_bc5').val($.trim($('.inputField_bc5').val() + $(this).children('.icon_2de').attr('alt')));
});

$('.sendButton_05b').on('click', function () {
    var text = $('.inputField_bc5').val();
    if (text.length >= 1) {
        $('.inputField_bc5').val("");
        sendMessage(text);
    }
});

function checkUpdates(ts) {
    statusThread = true
    $.ajax({
        url: 'https://chat.drp-script.ru/message.php',
        method: 'get',
        dataType: 'json',
        data: {'ts':ts},
        async: true,
        timeout: 61000,
        success: function(data){
            if(data.error === "not_found") {
                location.reload();
            }
            $.each(data.updates, function (index, value) {
                if(value.type === "user") {
                    $('.container_16f').append('<jdiv class="main_2aa __green_772">\n' +
        '                                            <jdiv class="content_e97">\n' +
        '                                                <jdiv class="main_458 __client_524">\n' +
        '                                                    <jdiv class="message_f7e _green_c0c __client_524" title="02.11.20 1:10:44">\n' +
        '                                                        <jdiv class="text_298">'+ value.text +'</jdiv>\n' +
        '                                                    </jdiv>\n' +
        '                                                </jdiv>\n' +
        '                                            </jdiv>\n' +
        '                                        </jdiv>');
                }
            });
            scrollDown();
            checkUpdates(data.ts);
        },
        error: function (data) {
            checkUpdates(ts);
        }
    });
}

function sendMessage(text) {
    $.ajax({
        url: 'https://chat.drp-script.ru/message.php',
        method: 'post',
        dataType: 'json',
        data: {'type':'new_message', 'text':text},
        success: function(data){
            if(data.status !== "error") {
                if(!statusThread) {
                    checkUpdates(0);
                }
            }
        }
    });
}

function scrollDown() {
    var div = $("#container-chat");
    div.scrollTop(div.prop('scrollHeight'));
}


/*!
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2006, 2014 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD (Register as an anonymous module)
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {

    var pluses = /\+/g;

    function encode(s) {
        return config.raw ? s : encodeURIComponent(s);
    }

    function decode(s) {
        return config.raw ? s : decodeURIComponent(s);
    }

    function stringifyCookieValue(value) {
        return encode(config.json ? JSON.stringify(value) : String(value));
    }

    function parseCookieValue(s) {
        if (s.indexOf('"') === 0) {
            // This is a quoted cookie as according to RFC2068, unescape...
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }

        try {
            // Replace server-side written pluses with spaces.
            // If we can't decode the cookie, ignore it, it's unusable.
            // If we can't parse the cookie, ignore it, it's unusable.
            s = decodeURIComponent(s.replace(pluses, ' '));
            return config.json ? JSON.parse(s) : s;
        } catch(e) {}
    }

    function read(s, converter) {
        var value = config.raw ? s : parseCookieValue(s);
        return $.isFunction(converter) ? converter(value) : value;
    }

    var config = $.cookie = function (key, value, options) {

        // Write

        if (arguments.length > 1 && !$.isFunction(value)) {
            options = $.extend({}, config.defaults, options);

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setMilliseconds(t.getMilliseconds() + days * 864e+5);
            }

            return (document.cookie = [
                encode(key), '=', stringifyCookieValue(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // Read

        var result = key ? undefined : {},
            // To prevent the for loop in the first place assign an empty array
            // in case there are no cookies at all. Also prevents odd result when
            // calling $.cookie().
            cookies = document.cookie ? document.cookie.split('; ') : [],
            i = 0,
            l = cookies.length;

        for (; i < l; i++) {
            var parts = cookies[i].split('='),
                name = decode(parts.shift()),
                cookie = parts.join('=');

            if (key === name) {
                // If second argument (value) is a function it's a converter...
                result = read(cookie, value);
                break;
            }

            // Prevent storing a cookie that we couldn't decode.
            if (!key && (cookie = read(cookie)) !== undefined) {
                result[name] = cookie;
            }
        }

        return result;
    };

    config.defaults = {};

    $.removeCookie = function (key, options) {
        // Must not alter options, thus extending a fresh object...
        $.cookie(key, '', $.extend({}, options, { expires: -1 }));
        return !$.cookie(key);
    };

}));

if ($.cookie('chsp_id') !== null ) {
    $.ajax({
        url: 'https://chat.drp-script.ru/message.php?check',
        method: 'get',
        dataType: 'json',
        success: function(data){
            if(data.status) {
                checkUpdates(0);
            }
        }
    });
}