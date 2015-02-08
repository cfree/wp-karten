(function($) {
	$(document).ready(function() {
		// Check to make sure the input box exists
		if ($('.ktn-datepicker').length > 0) {
			// Initilaize datepicker
			$('.ktn-datepicker').datepicker();
		}
	});
})(jQuery);