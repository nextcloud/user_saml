$(window).load(function() {

	$(document).on('click', 'option', function() {
		var target = $(this).val();
		if (target !== '') {
			window.location.href = target;
		}
	});

});
