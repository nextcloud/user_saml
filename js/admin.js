(function(OCA) {
	OCA.User_SAML = OCA.User_SAML || {};

	/**
	 * @namespace OCA.User_SAML.Admin
	 */
	OCA.User_SAML.Admin = {
		chooseEnv: function() {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.chooseEnv, this));
				return;
			}

			OC.AppConfig.setValue('user_saml', 'type', 'environment-variable');
			location.reload();
		},

		chooseSaml: function() {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.chooseSaml, this));
				return;
			}

			OC.AppConfig.setValue('user_saml', 'type', 'saml');
			location.reload();
		},

		setSamlConfigValue: function(category, setting, value) {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.setSamlConfigValue, this, category, setting, value));
				return;
			}

			OC.msg.startSaving('#user-saml-save-indicator');
			OC.AppConfig.setValue('user_saml', category+'-'+setting, value);
			OC.msg.finishedSaving('#user-saml-save-indicator', {status: 'success', data: {message: t('user_saml', 'Saved')}});
		}
	}
})(OCA);

$(function() {
	// Hide depending on the setup state
	var type = $('#user-saml').data('type');
	if(type !== '') {
		$('#user-saml-choose-type').addClass('hidden');
		$('#user-saml-warning-admin-user').removeClass('hidden');
	} else {
		$('#user-saml div:gt(2)').addClass('hidden');
		$('#user-saml-settings .button').addClass('hidden');
	}
	if(type === 'environment-variable') {
		$('#user-saml div:gt(4)').addClass('hidden');
		$('#user-saml-settings .button').addClass('hidden');
	}

	$('#user-saml-choose-saml').click(function(e) {
		e.preventDefault();
		OCA.User_SAML.Admin.chooseSaml();
	});
	$('#user-saml-choose-env').click(function(e) {
		e.preventDefault();
		OCA.User_SAML.Admin.chooseEnv();
	});

	// Enable tabs
	$('input:checkbox[value="1"]').attr('checked', true);

	$('#user-saml-sp input[type="text"], #user-saml-sp textarea').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('sp', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('sp', key, $(this).val());
		}
	});

	$('#user-saml-idp input[type="text"], #user-saml-idp textarea').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('idp', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('idp', key, $(this).val());
		}
	});

	$('#user-saml-general input[type="text"], #user-saml-general textarea').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('general', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('general', key, $(this).val());
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
			OCA.User_SAML.Admin.setSamlConfigValue('general', key, $(this).val());
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
			OCA.User_SAML.Admin.setSamlConfigValue('security', key, $(this).val());
		});
	});

	$('#user-saml').change(function() {
		if(type === 'saml') {
			// Checks on each request whether the settings make sense or not
			$.ajax({
				url: OC.generateUrl('/apps/user_saml/saml/metadata'),
				type: 'GET'
			}).fail(function (e) {
				if (e.status === 500) {
					$('#user-saml-settings-complete').addClass('hidden');
					$('#user-saml-settings-incomplete').removeClass('hidden');
				}
			}).success(function (e) {
				$('#user-saml-settings-complete').removeClass('hidden');
				$('#user-saml-settings-incomplete').addClass('hidden');
			})
		}
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
