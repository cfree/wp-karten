(function($) {
	$(document).ready(function() {
		// Check to make sure the input box exists
		if ($('.datepicker').length) {
			// Initilaize datepicker
			$('.datepicker').datepicker();
		}
	});
})(jQuery);