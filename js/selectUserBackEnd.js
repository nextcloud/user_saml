// !CDSP: Entirely overridden script.
// The CDSP_SelectedLang cookie is used by the CDSP app to apply the language choice
// selected here once the user lands in the main application.
//
// Cookie functions are defined in CDSP app JS.

document.addEventListener('DOMContentLoaded', function() {
	function swapPageLang(isEnglish) {
		$('#englishdisclaimer, #title-en, #footer-en, #langbutton-fr').toggle(isEnglish);
		$('#frenchdisclaimer, #title-fr, #footer-fr, #langbutton-en').toggle(!isEnglish);
	}

	swapPageLang(readCookie('CDSP_SelectedLang') != 'fr');

	$('.agree').on('click', function () {
		var target = null;
		if (window.cdspStateData.isFromExternalSide) {
			target = $('.sso-login').last();
		}
		else {
			target = $('.sso-login').first();
		}

		window.location.href = target.attr('href');
	});

	$('#langbutton-fr').on('click', function () {
		createCookie('CDSP_SelectedLang', 'fr');
		swapPageLang(false);
	});

	$('#langbutton-en').on('click', function () {
		createCookie('CDSP_SelectedLang', 'en');
		swapPageLang(true);
	});
});
