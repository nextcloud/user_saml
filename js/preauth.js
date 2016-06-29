if(typeof OC.Login !== "undefined") {
	// Redirect to login page
	window.location = OC.generateUrl('/apps/user_saml/saml/login')+'?requesttoken='+encodeURIComponent(OC.requestToken);
}
