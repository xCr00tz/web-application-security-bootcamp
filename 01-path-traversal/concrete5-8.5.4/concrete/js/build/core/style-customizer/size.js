/* jshint unused:vars, undef:true, browser:true, jquery:true */

/* global ConcreteEvent, ConcreteStyleCustomizerPalette */

;(function(global, $) {
    'use strict';

    function ConcreteSizeSelector($element, options) {
        var my = this,
            step = 1;
        options = $.extend({
            'inputName': false,
            'unit': 'px',
            'value': 0,
            'appendTo': document.body
        }, options);

        ConcreteStyleCustomizerPalette.call(my, $element, options);
        my.$slider = my.$widget.find('div.ccm-style-customizer-slider');
        if (my.options.unit == 'em') {
            step = 0.1;
        }
        my.$slider.slider({
            min: 0,
            max: 1000,
            step: step,
            value: my.options.value,
            create: function (e, ui) {
                $(this).parent().find('span').html(my.options.value + my.options.unit);
            },
            slide: function (e, ui) {
                $(this).parent().find('span').html(ui.value + my.options.unit);
            }
        });
        $element.addClass('ccm-style-customizer-importexport').data('ccm-style-customizer-importexport', this);
    }

    ConcreteSizeSelector.prototype = Object.create(ConcreteStyleCustomizerPalette.prototype);

    ConcreteSizeSelector.prototype.chooseTemplate = '<span data-launch="style-customizer-palette">' +
        '<input type="hidden" name="<%=options.inputName%>[size]" value="<%=options.value%>" data-style-customizer-input="size" />' +
        '<input type="hidden" name="<%=options.inputName%>[unit]" value="<%=options.unit%>" />' +
        '<span><%=options.value + options.unit%></span></span>';

    ConcreteSizeSelector.prototype.selectorWidgetTemplate = '<div class="ccm-ui ccm-style-customizer-palette ccm-style-customizer-palette-large">' +
        '<div><label><%=i18n.size%></label><div data-style-customizer-field="size"><div class="ccm-style-customizer-slider"></div><span class="ccm-style-customizer-slider-value"><%=options.value%><%=options.unit%></span></div></div>' +
        '<div class="ccm-style-customizer-palette-actions"><button class="btn btn-primary"><%=i18n.save%></button></div>' +
        '</div>';

    ConcreteSizeSelector.prototype.updateSwatch = function() {
        var my = this,
            $swatch = my.$element.find('span[data-launch=style-customizer-palette] span');

        $swatch.html(my.getValue('size') + my.options.unit);
    };

    ConcreteSizeSelector.prototype.save = function (e) {
        var my = this;
        my.setValue('size', my.$widget.find('div[data-style-customizer-field=size] div.ccm-style-customizer-slider').slider('value'));
        my.updateSwatch();
        ConcreteEvent.publish('StyleCustomizerControlUpdate');
        my.closeSelector(e);
    };

    ConcreteSizeSelector.prototype.exportStyle = function (data, cb) {
        var my = this;
        if (!my.options.inputName) {
            cb();
            return;
        }
        var $i = my.$element.find('input[data-style-customizer-input="size"]');
        if ($i.length !== 1) {
            cb();
            return;
        }
        var v = parseFloat($i.val());
        if (!(my.options.inputName in data)) {
            data[my.options.inputName] = {};
        }
        data[my.options.inputName].size = isNaN(v) ? null : v;
        data[my.options.inputName].unit = my.options.unit;
        cb();
    };

    ConcreteSizeSelector.prototype.importStyle = function (data, cb) {
        var my = this;
        if (!my.options.inputName || !data[my.options.inputName] || data[my.options.inputName].unit !== my.options.unit) {
            cb();
            return;
        }
        if (data[my.options.inputName].size === null) {
            my.setValue('size', '');
        } else if (typeof data[my.options.inputName].size === 'number') {
            my.setValue('size', data[my.options.inputName].size.toString());
        }
        cb();
    };

    // jQuery Plugin
    $.fn.concreteSizeSelector = function(options) {
        return $.each($(this), function(i, obj) {
            new ConcreteSizeSelector($(this), options);
        });
    };

    global.ConcreteSizeSelector = ConcreteSizeSelector;

})(this, jQuery);
