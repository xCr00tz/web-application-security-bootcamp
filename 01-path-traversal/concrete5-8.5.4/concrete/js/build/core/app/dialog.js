/* jshint unused:vars, undef:true, browser:true, jquery:true */
/* global NProgress, ccmi18n, ConcreteMenuManager, ConcreteAjaxRequest, ConcreteAlert */

;(function(global, $) {
    'use strict';

    /* concrete5 wrapper for jQuery UI */
    $.widget("concrete.dialog", $.ui.dialog, {
        _allowInteraction: function(event) {
            return !!$(event.target).closest('.ccm-interaction-dialog').length ||
                !!$(event.target).closest(".cke_dialog").length ||
                this._super(event);
        }
    });
    
    function fixDialogButtons($dialog) {
        var $ccmButtons = $dialog.find('.dialog-buttons').eq(0);
        if ($ccmButtons.length === 0) {
            return;
        }
        if ($.trim($ccmButtons.html()).length === 0) {
            return;
        }
        var $dialogParent = $dialog.parent();
        if ($dialogParent.find('.ui-dialog-buttonset').length !== 0) {
        	return;
        }
        $dialog.jqdialog('option', 'buttons', [{}]);
        $dialogParent.find('.ui-dialog-buttonset').remove();
        $ccmButtons
            .removeClass()
            .addClass('ccm-ui')
            .appendTo($dialogParent.find('.ui-dialog-buttonpane').empty())
        ;
    }

    $.widget.bridge( "jqdialog", $.concrete.dialog );
    // wrap our old dialog function in the new dialog() function.
    $.fn.dialog = function() {
        // Pass this over to jQuery UI Dialog in a few circumstances
        switch (arguments.length) {
            case 0:
                if ($(this).is('div')) {
                    $(this).jqdialog();
                    return;
                }
                break;
            case 1:
            	var arg = arguments[0];
            	if ($.isPlainObject(arg)) {
            		var originalOpen = arg.open || null;
            		arg.open = function(e, ui) {
            			fixDialogButtons($(this));
            			if (originalOpen) {
            				originalOpen.call(this, e, ui);
            			}
            		};
            	}
            	$.fn.jqdialog.call($(this), arg);
            	return;
            default:
            	$.fn.jqdialog.apply($(this), arguments);
            	return;
    	}
        // LEGACY SUPPORT
        return $(this).each(function() {
            $(this).unbind('click.make-dialog').bind('click.make-dialog', function(e) {
                e.preventDefault();
                if ($(this).hasClass('ccm-dialog-launching')) {
                    return;
                }
    
                $(this).addClass('ccm-dialog-launching');
    
                var href = $(this).attr('href');
                var width = $(this).attr('dialog-width');
                var height =$(this).attr('dialog-height');
                var title = $(this).attr('dialog-title');
                var onOpen = $(this).attr('dialog-on-open');
                var dialogClass = $(this).attr('dialog-class');
                var onDestroy = $(this).attr('dialog-on-destroy');
                /*
                 * no longer necessary. we auto detect
                    var appendButtons = $(this).attr('dialog-append-buttons');
                */
                var onClose = $(this).attr('dialog-on-close');
                var onDirectClose = $(this).attr('dialog-on-direct-close');
                var obj = {
                    modal: true,
                    href: href,
                    width: width,
                    height: height,
                    title: title,
                    onOpen: onOpen,
                    onDestroy: onDestroy,
                    dialogClass: dialogClass,
                    onClose: onClose,
                    onDirectClose: onDirectClose,
                    launcher: $(this)
                };
                $.fn.dialog.open(obj);
            });
        });
    };
    
    $.fn.dialog.close = function(num) {
        num++;
        $("#ccm-dialog-content" + num).jqdialog('close');
    };
    
    $.fn.dialog.open = function(options) {
        if (typeof(ConcreteMenu) != 'undefined') {
            var activeMenu = ConcreteMenuManager.getActiveMenu();
            if (activeMenu) {
                activeMenu.hide();
            }
        }
    
        var w;
        if (typeof(options.width) == 'string') {
            if (options.width == 'auto') {
                w = 'auto';
            } else {
                if (options.width.indexOf('%', 0) > 0) {
                    w = options.width.replace('%', '');
                    w = $(window).width() * (w / 100);
                    w = w + 50;
                } else {
                    w = parseInt(options.width) + 50;
                }
            }
        } else if (options.width) {
            w = parseInt(options.width) + 50;
        } else {
            w = 550;
        }
    
        var h;
        if (typeof(options.height) == 'string') {
            if (options.height == 'auto') {
                h = 'auto';
            } else {
                if (options.height.indexOf('%', 0) > 0) {
                    h = options.height.replace('%', '');
                    h = $(window).height() * (h / 100);
                    h = h + 100;
                } else {
                    h = parseInt(options.height) + 100;
                }
            }
        } else if (options.height) {
            h = parseInt(options.height) + 100;
        } else {
            h = 400;
        }
        if (h !== 'auto' && h > $(window).height()) {
            h = $(window).height();
        }
    
        options.width = w;
        options.height = h;
    
        var defaults = {
            'modal': true,
            'escapeClose': true,
            'width': w,
            'height': h,
            'dialogClass': 'ccm-ui',
            'resizable': true,
    
            'create': function() {
                $(this).parent().addClass('animated fadeIn');
            },
    
            'open': function() {
                // jshint -W061
                var $dialog = $(this);
                var nd = $(".ui-dialog").length;
                if (nd == 1) {
                    $("body").attr('data-last-overflow', $('body').css('overflow'));
                    $("body").css("overflow", "hidden");
                }
                var overlays = $('.ui-widget-overlay').length;
                $('.ui-widget-overlay').each(function(i, obj) {
                    if ((i + 1) < overlays) {
                        $(this).removeClass('animated fadeIn').css('opacity', 0);
                    }
                });
                if (overlays == 1) {
                    $('.ui-widget-overlay').addClass('animated fadeIn');
                }
    
                $.fn.dialog.activateDialogContents($dialog);
    
                // on some brother (eg: Chrome) the resizable get hidden because the button pane
                // in on top of it, here is a fix for this:
                if ( $dialog.jqdialog('option', 'resizable') )
                {
                    var $wrapper = $($dialog.parent());
                    var z = parseInt($wrapper.find('.ui-dialog-buttonpane').css('z-index'));
                    $wrapper.find('.ui-resizable-handle').css('z-index', z + 1000);
                }
    
                if (typeof options.onOpen != "undefined") {
                    if ((typeof options.onOpen) == 'function') {
                        options.onOpen($dialog);
                    } else {
                        eval(options.onOpen);
                    }
                }
    
                if (options.launcher) {
                    options.launcher.removeClass('ccm-dialog-launching');
                }
    
            },
            'beforeClose': function() {
                var nd = $(".ui-dialog:visible").length;
                if (nd == 1) {
                    $("body").css("overflow", $('body').attr('data-last-overflow'));
                }
            },
            'close': function(ev, u) {
                // jshint -W061
                if (!options.element) {
                    $(this).jqdialog('destroy').remove();
                }
                if (typeof options.onClose != "undefined") {
                    if ((typeof options.onClose) == 'function') {
                        options.onClose($(this));
                    } else {
                        eval(options.onClose);
                    }
                }
                if (typeof options.onDirectClose != "undefined" && ev.handleObj && (ev.handleObj.type == 'keydown' || ev.handleObj.type == 'click')) {
                    if ((typeof options.onDirectClose) == 'function') {
                        options.onDirectClose();
                    } else {
                        eval(options.onDirectClose);
                    }
                }
                if (typeof options.onDestroy != "undefined") {
                    if ((typeof options.onDestroy) == 'function') {
                        options.onDestroy();
                    } else {
                        eval(options.onDestroy);
                    }
                }
                var overlays = $('.ui-widget-overlay').length;
                $('.ui-widget-overlay').each(function(i, obj) {
                    if ((i + 1) < overlays) {
                        $(this).css('opacity', 0);
                    } else {
                        $(this).css('opacity', 1);
                    }
                });
            }
        };
    
        var finalSettings = {'autoOpen': false, 'data': {} };
        $.extend(finalSettings, defaults, options);
    
        if (finalSettings.element) {
            $(finalSettings.element).jqdialog(finalSettings).jqdialog();
            $(finalSettings.element).jqdialog('open');
        } else {
            $.fn.dialog.showLoader();
            $.ajax({
                type: 'GET',
                url: finalSettings.href,
                data: finalSettings.data,
                success: function(r) {
                    $.fn.dialog.hideLoader();
                    // note the order here is very important in order to actually run javascript in
                    // the pages we load while having access to the jqdialog object.
                    // Ensure that the dialog is open prior to evaluating javascript.
                    $('<div />').jqdialog(finalSettings).html(r).jqdialog('open');
                },
                error: function(xhr, status, error) {
                	$.fn.dialog.hideLoader();
                	ConcreteAlert.dialog(ccmi18n.error, ConcreteAjaxRequest.renderErrorResponse(xhr, true));
                }
            });
        }
    
    };
    
    $.fn.dialog.activateDialogContents = function($dialog) {
        // handle buttons
    
        $dialog.find('button[data-dialog-action=cancel]').on('click', function() {
            $.fn.dialog.closeTop();
        });
        $dialog.find('[data-dialog-form]').each(function() {
            var $form = $(this),
                options = {};
            if ($form.attr("data-dialog-form-processing") == 'progressive') {
                options.progressiveOperation = true;
                options.progressiveOperationElement = 'div[data-dialog-form-element=progress-bar]';
            }
            $form.concreteAjaxForm(options);
        });
    
    
        $dialog.find('button[data-dialog-action=submit]').on('click', function() {
            $dialog.find('[data-dialog-form]').submit();
        });
        
        fixDialogButtons($dialog);
    
        // make dialogs
        $dialog.find('.dialog-launch').dialog();
    
        // automated close handling
        $dialog.find('.ccm-dialog-close').on('click', function() {
            $dialog.dialog('close');
        });
    
        $dialog.find('.launch-tooltip').tooltip({'container': '#ccm-tooltip-holder'});
    
        // help handling
        if ($dialog.find('.dialog-help').length > 0) {
            $dialog.find('.dialog-help').hide();
            var helpContent = $dialog.find('.dialog-help').html(),
                helpText;
            if (ccmi18n.helpPopup) {
                helpText = ccmi18n.helpPopup;
            } else {
                helpText = 'Help';
            }
            var button = $('<button class="ui-dialog-titlebar-help ccm-menu-help-trigger"><i class="fa fa-info-circle"></i></button>'),
                container = $('#ccm-tooltip-holder');
            $dialog.parent().find('.ui-dialog-titlebar').append(button);
    
            button.popover({
                content: function() {
                    return helpContent;
                },
                placement: 'bottom',
                html: true,
                container: container,
                trigger: 'click'
            });
            button.on('shown.bs.popover', function() {
                var binding = function() {
                    button.popover('hide', button);
                    binding = $.noop;
                };
    
                button.on('hide.bs.popover', function(event) {
                    button.unbind(event);
                    binding = $.noop;
                });
    
                $('body').mousedown(function(e) {
                    if ($(e.target).closest(container).length || $(e.target).closest(button).length) {
                        return;
                    }
                    $(this).unbind(e);
                    binding();
                });
            });
        }
    };
    
    $.fn.dialog.getTop = function() {
        var nd = $(".ui-dialog:visible").length;
        return $($('.ui-dialog:visible')[nd-1]).find('.ui-dialog-content');
    };
    
    $.fn.dialog.replaceTop = function(html) {
        var $dialog = $.fn.dialog.getTop();
        $dialog.html(html);
        $.fn.dialog.activateDialogContents($dialog);
    };
    
    $.fn.dialog.showLoader = function(text) {
        NProgress.start();
    };
    
    $.fn.dialog.hideLoader = function() {
        NProgress.done();
    };
    
    $.fn.dialog.closeTop = function() {
        var $dialog = $.fn.dialog.getTop();
        $dialog.jqdialog('close');
    };
    
    $.fn.dialog.closeAll = function() {
        $($(".ui-dialog-content").get().reverse()).jqdialog('close');
    };
    
    $.ui.dialog.prototype._focusTabbable = $.noop;
    
})(window, jQuery);
