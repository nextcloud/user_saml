$(window).load(function() {

	$(document).on('click', 'option', function() {
		var target = $(this).val();
		window.location.href = target;
	});

});
