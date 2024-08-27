document.addEventListener("DOMContentLoaded", (event) => {
	// !CDSP: Cookie to help pass Language Choice to core. ----
	function createCookie(name, value, days) {
		var expires;

		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = "; expires=" + date.toGMTString();
		} else {
			expires = "";
		}
		document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
	}

	function readCookie(name) {
		var nameEQ = encodeURIComponent(name) + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) === ' ')
				c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) === 0)
				return decodeURIComponent(c.substring(nameEQ.length, c.length));
		}
		return null;
	}

	if (window.location.hostname.substr(0, 5) === "ll-lv") {
		createCookie("Side", "External");
	} else {
		createCookie("Side", "Internal");
	}

	if (readCookie("LanguageChosen") === "fr") {
		$("#englishdisclaimer").hide();
		$("#title-en").hide();
		$("#footer-en").hide();
		$("#langbutton-fr").hide();

		$("#frenchdisclaimer").show();
		$("#title-fr").show();
		$("#footer-fr").show();
		$("#langbutton-en").show();
	}

	$(".agree").on('click', function () {
		if (readCookie("Side") === "Internal") {
			var target = $('.sso-login').first().attr('href');
		} else {
			var target = $('.sso-login').last().attr('href');
		}

		window.location.href = target;
	});

	$("#langbutton-fr").on('click', function () {
		createCookie("LanguageChosen", "fr");
		$("#englishdisclaimer").hide();
		$("#title-en").hide();
		$("#footer-en").hide();
		$("#langbutton-fr").hide();


		$("#frenchdisclaimer").show();
		$("#title-fr").show();
		$("#footer-fr").show();
		$("#langbutton-en").show();
	});

	$("#langbutton-en").on('click', function () {
		createCookie("LanguageChosen", "en");
		$("#frenchdisclaimer").hide();
		$("#title-fr").hide();
		$("#footer-fr").hide();
		$("#langbutton-en").hide();

		$("#englishdisclaimer").show();
		$("#title-en").show();
		$("#footer-en").show();
		$("#langbutton-fr").show();
	});
	// ----
});
