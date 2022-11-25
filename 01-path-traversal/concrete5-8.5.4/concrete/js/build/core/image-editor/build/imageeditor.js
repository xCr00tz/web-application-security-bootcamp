// Patch the browser drawImage function
(function() {
  var contextPrototype, originalFunction;
  try {
    contextPrototype = Object.getPrototypeOf(document.createElement('canvas').getContext('2d'));
    originalFunction = contextPrototype.drawImage;
  } catch (e) {
    originalFunction = null; 
  }
  if (originalFunction) {
    contextPrototype.drawImage = function() {
      if (arguments.length >= 4) {
        this.imageSmoothingEnabled = true;
        if (typeof this.imageSmoothingQuality === 'string') {
          this.imageSmoothingQuality = 'high';
        }
      }
      return originalFunction.apply(this, arguments);
    };
  }
})();

var ImageEditor = function (settings) {
    if (settings === undefined) return this;
    settings.pixelRatio = 1;
    var im = this, round = function (float) {
        return Math.round(float);
    };
    im.saveData = settings.saveData || {};
    im.saveUrl = settings.saveUrl;
    im.token = settings.token;
    im.width = settings.width;
    im.height = settings.height;
    im.strictSize = typeof settings.strictSize !== 'undefined' ? !!settings.strictSize : settings.saveWidth > 0;
    im.saveWidth = settings.saveWidth || (im.strictSize ? 0 : round(im.width / 2));
    im.saveHeight = settings.saveHeight || (im.strictSize ? 0 : round(im.height / 2));
    im.stage = new Kinetic.Stage(settings);
    im.namespaces = {};
    im.controlSets = {};
    im.components = {};
    im.settings = settings;
    im.filters = {};
    im.fileId = im.settings.fID;
    im.scale = 1;
    im.crosshair = new Image();
    im.uniqid = im.stage.getContainer().id;
    im.editorContext = $(im.stage.getContainer()).parent();
    im.domContext = im.editorContext.parent();
    im.controlContext = im.domContext.children('div.controls');
    im.controlSetNamespaces = [];

    im.showLoader = $.fn.dialog.showLoader;
    im.hideLoader = $.fn.dialog.hideLoader;
    im.stage.im = im;
    im.stage.elementType = 'stage';
    im.crosshair.src = CCM_IMAGE_PATH + '/image_editor/crosshair.png';

    im.center = {
        x: Math.round(im.width / 2),
        y: Math.round(im.height / 2)
    };

    im.centerOffset = {
        x: im.center.x,
        y: im.center.y
    };

    var getElem = function (selector) {
            return $(selector, im.domContext);
        },
        log = function () {
            if (settings.debug === true && typeof window.console !== 'undefined') {
                var args = arguments;
                if (args.length == 1) args = args[0];
                window.console.log(args);
            }
        },
        warn = function () {
            if (settings.debug === true && typeof window.console !== 'undefined') {
                var args = arguments;
                if (args.length == 1) args = args[0];
                window.console.warn(args);
            }
        },
        error = function () {
            if (typeof window.console !== 'undefined') {
                var args = arguments;
                if (args.length == 1) args = args[0];
                window.console.error("Image Editor Error: " + args);
            }
        };

    im.stage._setDraggable = im.stage.setDraggable;
    im.stage.setDraggable = function (v) {
        warn('setting draggable to ' + v);
        return im.stage._setDraggable(v);
    };
