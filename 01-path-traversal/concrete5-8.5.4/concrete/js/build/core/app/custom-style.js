/* jshint unused:vars, undef:true, browser:true, jquery:true */
/* global ccmi18n */

/* Block Styles Customization Popup */
;(function(global, $) {
    'use strict';

    var ccmCustomStyle = {
    	tabs:function(aLink,tab){
    		$('.ccm-styleEditPane').hide();
    		$('#ccm-styleEditPane-'+tab).show();
    		$(aLink.parentNode.parentNode).find('li').removeClass('ccm-nav-active');
    		$(aLink.parentNode).addClass('ccm-nav-active');
    		return false;
    	},
    	resetAll:function(){
    		if (!window.confirm( ccmi18n.confirmCssReset)) {  
    			return false;
    		}
    		$.fn.dialog.showLoader();
    
    		$('#ccm-reset-style').val(1);
    		$('#ccmCustomCssForm').get(0).submit();
    		return true;
    	},
    	showPresetDeleteIcon: function() {
    		if ($('select[name=cspID]').val() > 0) {
    			$("#ccm-style-delete-preset").show();		
    		} else {
    			$("#ccm-style-delete-preset").hide();
    		}	
    	},
    	deletePreset: function() {
    		var cspID = $('select[name=cspID]').val();
    		if (cspID > 0) {
    			
    			if( !window.confirm(ccmi18n.confirmCssPresetDelete) ) return false;
    			
    			var action = $('#ccm-custom-style-refresh-action').val() + '&deleteCspID=' + cspID + '&subtask=delete_custom_style_preset';
    			$.fn.dialog.showLoader();
    			
    			$.get(action, function(r) {
    				$("#ccm-custom-style-wrapper").html(r);
    				$.fn.dialog.hideLoader();
    			});
    		}
    	},
    	initForm: function() {
    		if ($("#cspFooterPreset").length > 0) {
    			$("#ccmCustomCssFormTabs input, #ccmCustomCssFormTabs select, #ccmCustomCssFormTabs textarea").bind('change click', function() {
    				$("#cspFooterPreset").show();
    				$("#cspFooterNoPreset").remove();
    				$("#ccmCustomCssFormTabs input, #ccmCustomCssFormTabs select").unbind('change click');
    			});		
    		}
    		$('input[name=cspPresetAction]').click(function() {
    			if ($(this).val() == 'create_new_preset' && $(this).prop('checked')) {
    				$('input[name=cspName]').attr('disabled', false).focus();
    			} else { 
    				$('input[name=cspName]').val('').attr('disabled', true); 
    			}
    		});
    		ccmCustomStyle.showPresetDeleteIcon();
    		
    		ccmCustomStyle.lastPresetID=parseInt($('select[name=cspID]').val());
    		
    		$('select[name=cspID]').change(function(){ 
    			var cspID = parseInt($(this).val());
    			var selectedCsrID = parseInt($('input[name=selectedCsrID]').val());
    			
    			if(ccmCustomStyle.lastPresetID==cspID) return false;
    			ccmCustomStyle.lastPresetID=cspID;
    			
    			$.fn.dialog.showLoader();
    			var action;
    			if (cspID > 0) {
    				action = $('#ccm-custom-style-refresh-action').val() + '&cspID=' + cspID;
    			} else {
    				action = $('#ccm-custom-style-refresh-action').val() + '&csrID=' + selectedCsrID;
    			}
    			
    			
    			$.get(action, function(r) {
    				$("#ccm-custom-style-wrapper").html(r);
    				$.fn.dialog.hideLoader();
    			});
    			
    		});
    		
    		$('#ccmCustomCssForm').submit(function() {
    			if ($('input[name=cspCreateNew]').prop('checked') == true) {
    				if ($('input[name=cspName]').val() == '') { 
    					$('input[name=cspName]').focus();
    					window.alert(ccmi18n.errorCustomStylePresetNoName);
    					return false;
    				}
    			}
    
    			$.fn.dialog.showLoader();		
    			return true;
    		});
    		
    		//IE bug fix 0 can't focus on txt fields if new block just added 
    		if(!parseInt(ccmCustomStyle.lastPresetID)) {
    			setTimeout(
    			    function() {
    			        $("#ccmCustomCssFormTabs input").attr("disabled", false).get(0).focus();
    			    },
    			    500
    			);
    		}
    	},
    	validIdCheck:function(el,prevID){
    		var selEl = $('#'+el.value); 
    		if( selEl && selEl.get(0) && selEl.get(0).id!=prevID ){		
    			$('#ccm-styles-invalid-id').css('display','block');
    		}else{
    			$('#ccm-styles-invalid-id').css('display','none');
    		}
    	}
    };

    global.ccmCustomStyle = ccmCustomStyle;

})(window, jQuery);
