function setSAMLConfigValue(category, setting, value) {
	OC.msg.startSaving('#user-saml-save-indicator');
	OC.AppConfig.setValue('user_saml', category+'-'+setting, value);
	OC.msg.finishedSaving('#user-saml-save-indicator', {status: 'success', data: {message: t('user_saml', 'Saved')}});
}

$(function() {
	// Enable tabs
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

	$('#user-saml-general input[type="checkbox"]').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			if($(this).val() === "0") {
				$(this).val("1");
			} else {
				$(this).val("0");
			}
			setSAMLConfigValue('general', key, $(this).val());
		});
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

	$('#user-saml').change(function() {
		// Checks on each request whether the settings make sense or not
		$.ajax({
			url: OC.generateUrl('/apps/user_saml/saml/metadata'),
			type: 'GET'
		}).fail(function (e) {
			if(e.status === 500) {
				$('#user-saml-settings-complete').addClass('hidden');
				$('#user-saml-settings-incomplete').removeClass('hidden');
			}
		}).success(function (e) {
			$('#user-saml-settings-complete').removeClass('hidden');
			$('#user-saml-settings-incomplete').addClass('hidden');
		})
	});

	$('#user-saml-settings .toggle').on('click', function() {
		var el = $(this),
			nextSibling = el.parent().next(),
			parentSettingId = el.closest('div').attr('id'),
			text = '';
		switch(parentSettingId) {
			case 'user-saml-security':
				if (nextSibling.hasClass('hidden')) {
					text = 'Hide security settings ...';
				} else {
					text = 'Show security settings ...';
				}
				break;
			case 'user-saml-idp':
				if (nextSibling.hasClass('hidden')) {
					text = 'Hide optional Identity Provider settings ...';
				} else {
					text = 'Show optional Identity Provider settings ...';
				}
				break;
			case 'user-saml-sp':
				if (nextSibling.hasClass('hidden')) {
					text = 'Hide Service Provider settings ...';
				} else {
					text = 'Show Service Provider settings ...';
				}
				break;
		}
		el.html(t('user_saml', text));

		if (nextSibling.is(":visible")) {
			nextSibling.slideUp();
		} else {
			nextSibling.slideDown();
		}
	});
});
