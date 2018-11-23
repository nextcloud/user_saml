$(window).load(function() {

	$(".login-chose-saml-idp").change(function() {
		var target = $(this).val();
		if (target !== '') {
			window.location.href = target;
		}
	});

});
