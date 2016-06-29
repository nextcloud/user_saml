$(function() {

	// Show token views
	var collection = new OCA.User_SAML.AuthTokenCollection();
	var view = new OCA.User_SAML.AuthTokenView({
		collection: collection
	});
	view.reload();
});