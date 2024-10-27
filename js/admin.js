jQuery(function() {
	'use strict';

	var $            = jQuery,
	    $bypass      = $('input[name*=bypass]'),
	    $adInjection = $('.ad-injection'),
	    $selectors   = $adInjection.find('input[name*=selector]'),
	    $tags        = $adInjection.find('textarea[name*=tag]'),
            $submit      = $('input[type=submit]');

	var updateInjections = function() {
		if ($bypass.is(':checked')) {
			$selectors.prop('disabled', false);
			$tags.prop('disabled', false);
		}
		else {
			$selectors.prop('disabled', true);
			$tags.prop('disabled', true);
		}
	};

	$bypass.click(updateInjections);
	updateInjections();
});
