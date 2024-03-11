document.addEventListener("DOMContentLoaded", (event) => {
	document.getElementsByClassName("login-chose-saml-idp")[0].addEventListener('change', function (event) {
		var target = this.value;
		if (target !== '') {
			window.location.href = target;
		}
	});
});
