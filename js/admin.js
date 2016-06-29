function setSAMLConfigValue(category, setting, value) {
	OC.msg.startSaving('#user-saml-save-indicator');
	OC.AppConfig.setValue('user_saml', category+'-'+setting, value);
	OC.msg.finishedSaving('#user-saml-save-indicator', {status: 'success', data: {message: t('user_saml', 'Saved')}});
}

$(function() {
	// Enable tabs
	$('#user-saml-settings').tabs();
	$('input:checkbox[value="1"]').attr('checked', true);

	$('#user-saml-sp input[type="text"], #user-saml-sp textarea').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			setSAMLConfigValue('sp', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			setSAMLConfigValue('sp', key, $(this).val());
		}
	});

	$('#user-saml-idp input[type="text"], #user-saml-idp textarea').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			setSAMLConfigValue('idp', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			setSAMLConfigValue('idp', key, $(this).val());
		}
	});

	$('#user-saml-general input[type="text"], #user-saml-general textarea').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			setSAMLConfigValue('general', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			setSAMLConfigValue('general', key, $(this).val());
		}
	});

	$('#user-saml-security input[type="checkbox"]').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			if($(this).val() === "0") {
				$(this).val("1");
			} else {
				$(this).val("0");
			}
			setSAMLConfigValue('security', key, $(this).val());
		});
	});
});
