(function(OCA) {
	OCA.User_SAML = OCA.User_SAML || {};

	/**
	 * @namespace OCA.User_SAML.Admin
	 */
	OCA.User_SAML.Admin = {
		currentConfig: '1',
		providerIds: '1',

		init: function(callback) {
			var xhr = new XMLHttpRequest();
			xhr.open('GET', OC.generateUrl('/apps/user_saml/settings/providers'));
			xhr.setRequestHeader('Content-Type', 'application/json');
			xhr.setRequestHeader('requesttoken', OC.requestToken);

			xhr.onload = function () {
				var response = JSON.parse(xhr.response);
				if (xhr.status >= 200 && xhr.status < 300) {
					if (response.providerIds !== "") {
						OCA.User_SAML.Admin.providerIds += ',' + response.providerIds;
						OCA.User_SAML.Admin.currentConfig = OCA.User_SAML.Admin.providerIds.split(',').sort()[0];
					}
					callback();
				} else {
					console.error("Could not fetch new provider ID");
				}
			};
			xhr.onerror = function () {
				console.error("Could not fetch new provider ID");
			}


			xhr.send();
		},
		chooseEnv: function() {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.chooseEnv, this));
				return;
			}

			OCP.AppConfig.setValue('user_saml', 'type', 'environment-variable', {success: function() {location.reload();}});
		},

		chooseSaml: function() {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.chooseSaml, this));
				return;
			}

			OCP.AppConfig.setValue('user_saml', 'type', 'saml', {success: function() {location.reload();}});
		},

		resetSettings: function() {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.resetSettings, this));
				return;
			}

			OCP.AppConfig.setValue('user_saml', 'type', '', {success: function() {location.reload();}});
		},


		getConfigIdentifier: function() {
			if (this.currentConfig === '1') {
				return '';
			}
			return this.currentConfig + '-';
		},

		/**
		 * Add a new provider
		 */
		addProvider: function (callback) {
			var xhr = new XMLHttpRequest();
			xhr.open('PUT', OC.generateUrl('/apps/user_saml/settings/providerSettings'));
			xhr.setRequestHeader('Content-Type', 'application/json')
			xhr.setRequestHeader('requesttoken', OC.requestToken)

			xhr.onload = function () {
				var response = JSON.parse(xhr.response)
				if (xhr.status >= 200 && xhr.status < 300) {
					OCA.User_SAML.Admin.providerIds += ',' + response.id;
					callback(response.id)
				} else {
					console.error("Could not fetch new provider ID")
				}
			};
			xhr.onerror = function () {
				console.error("Could not fetch new provider ID");
			};

			xhr.send();
		},

		updateProvider: function (configKey, configValue, successCb, errorCb) {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', OC.generateUrl('/apps/user_saml/settings/providerSettings/' + this.currentConfig));
			xhr.setRequestHeader('Content-Type', 'application/json');
			xhr.setRequestHeader('requesttoken', OC.requestToken);

			xhr.onload = function () {
				if (xhr.status >= 200 && xhr.status < 300) {
					successCb();
				} else {
					console.error("Could not update config");
					errorCb();
				}
			};
			xhr.onerror = function () {
				console.error("Could not update config");
				errorCb();
			};

			xhr.send(JSON.stringify({configKey: configKey, configValue: configValue.trim()}));
		},

		removeProvider: function(callback) {
			var providerIds = OCA.User_SAML.Admin.providerIds.split(',');
			if (providerIds.length > 1) {
				var index = providerIds.indexOf(this.currentConfig);
				if (index > -1) {
					providerIds.splice(index, 1);
				}
				$.ajax({ url: OC.generateUrl('/apps/user_saml/settings/providerSettings/' + this.currentConfig), type: 'DELETE'})
					.done(callback(this.currentConfig));
			}
		},

		setSamlConfigValue: function(category, setting, value, global) {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.setSamlConfigValue, this, category, setting, value));
				return;
			}
			OC.msg.startSaving('#user-saml-save-indicator');

			var callbacks = {
				success: function () {
					OC.msg.finishedSaving('#user-saml-save-indicator', {status: 'success', data: {message: t('user_saml', 'Saved')}});
				},
				error: function() {
					OC.msg.finishedSaving('#user-saml-save-indicator', {status: 'error', data: {message: t('user_saml', 'Could not save')}});
				}
			};

			if (global) {
				OCP.AppConfig.setValue('user_saml', category + '-' + setting, value, callbacks);
				return;
			}
			this.updateProvider(category + '-' + setting, value, callbacks.success, callbacks.error);
		}
	}
})(OCA);

$(function() {

	var type = $('#user-saml').data('type');

	OCA.User_SAML.Admin.init(function() {
		$('.account-list li[data-id="' + OCA.User_SAML.Admin.currentConfig + '"]').addClass('active');
		if (OCA.User_SAML.Admin.providerIds.split(',').length <= 1) {
			$('[data-js="remove-idp"]').addClass('hidden');
		}
		// Hide depending on the setup state
		if(type !== 'environment-variable' && type !== 'saml') {
			$('#user-saml-choose-type').removeClass('hidden');
		} else {
			$('#user-saml-global').removeClass('hidden');
			$('#user-saml-warning-admin-user').removeClass('hidden');
			$('#user-saml-settings').removeClass('hidden');
		}
		if(type === 'environment-variable') {
			// we need the settings div to be visible for require_providioned_account
			$('#user-saml-settings div').addClass('hidden');
			$('#user-saml-settings .button').addClass('hidden');
			$('#user-saml-general').removeClass('hidden');
		}
		if (type === 'saml') {
			$('#user-saml .account-list').removeClass('hidden');
			$('#user-saml-general').removeClass('hidden');
		}

		if($('#user-saml-general-require_provisioned_account').val() === '0' && type !== '') {
			$('#user-saml-attribute-mapping').removeClass('hidden');
		} else {
			$('#user-saml-attribute-mapping').addClass('hidden');
		}
	});

	$('#user-saml-choose-saml').click(function(e) {
		e.preventDefault();
		if(type === '') {
			OCA.User_SAML.Admin.chooseSaml();
		}
	});
	$('#user-saml-choose-env').click(function(e) {
		e.preventDefault();
		if(type === '') {
			OCA.User_SAML.Admin.chooseEnv();
		}
	});

	$('#user-saml-reset-settings').click(function(e) {
		e.preventDefault();
		OCA.User_SAML.Admin.resetSettings();
	});

	var switchProvider = function(providerId) {
		$('.account-list li').removeClass('active');
		$('.account-list li[data-id="' + providerId + '"]').addClass('active');
		OCA.User_SAML.Admin.currentConfig = '' + providerId;
		$.get(OC.generateUrl('/apps/user_saml/settings/providerSettings/' + providerId)).done(function(data) {
			Object.keys(data).forEach(function(category, index){
				var entries = data[category];
				Object.keys(entries).forEach(function (configKey) {
					var element = $('#user-saml-settings *[data-key="' + configKey + '"]');
					if ($('#user-saml-settings #user-saml-' + category + ' #user-saml-' + configKey).length) {
						element = $('#user-saml-' + category + ' #user-saml-' + configKey);
					}
					if ($('#user-saml-settings #user-saml-' + category + ' [name="' + configKey + '"]').length) {
						element = $('#user-saml-' + category + ' [name="' + configKey + '"]');
					}
					if(element.is('input') && element.prop('type') === 'text') {
						element.val(entries[configKey])
					}
					else if(element.is('textarea')) {
						element.val(entries[configKey]);
					}
					else if(element.prop('type') === 'checkbox') {
						var value = entries[configKey] === '1' ? '1' : '0';
						element.val(value);
					} else {
						console.log('unable to find element for ' + configKey);
					}
				});
			});
			$('input:checkbox[value="1"]').attr('checked', true);
			$('input:checkbox[value="0"]').removeAttr('checked', false);
			var xmlDownloadButton = $('#get-metadata');
			var url = xmlDownloadButton.data('base') + '?idp=' + providerId;
			xmlDownloadButton.attr('href', url);
		});
	};

	$('.account-list').on('click', 'li:not(.add-provider):not(.remove-provider)', function() {
		var providerId = '' + $(this).data('id');
		switchProvider(providerId);
	});

	$('.account-list .add-provider').on('click', function() {
		OCA.User_SAML.Admin.addProvider(function (nextId) {
			$('<li data-id="' + nextId + '"><a>' + t('user_saml', 'Provider') + ' ' + nextId + '</a></li>').insertBefore('.account-list .remove-provider');
			switchProvider(nextId);
			$('[data-js="remove-idp"]').removeClass('hidden');
		});
	});

	$('[data-js="remove-idp"]').on('click', function() {
		OCA.User_SAML.Admin.removeProvider(function(currentConfig) {
			$('.account-list li[data-id="' + currentConfig + '"]').remove();
			switchProvider(OCA.User_SAML.Admin.providerIds.split(',')[0]);
			if (OCA.User_SAML.Admin.providerIds.split(',').length <= 1) {
				$('[data-js="remove-idp"]').addClass('hidden');
			}
		});
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
	$('#user-saml-sp select').change(function(e) {
		var key = $(this).attr('name');
		OCA.User_SAML.Admin.setSamlConfigValue('sp', key, $(this).val());
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
		if(el.data('key') === 'idp0_display_name') {
			if ($(this).val() !== '') {
				$('.account-list li[data-id=' + OCA.User_SAML.Admin.currentConfig + '] a').text($(this).val())
			} else {
				$('.account-list li[data-id=' + OCA.User_SAML.Admin.currentConfig + '] a').text(t('user_saml', 'Provider') + ' ' + OCA.User_SAML.Admin.currentConfig);
			}
		}
	});

	$('#user-saml-global input[type="checkbox"]').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			if($(this).val() === "0") {
				$(this).val("1");
			} else {
				$(this).val("0");
			}
			if(key === 'require_provisioned_account') {
				$('#user-saml-attribute-mapping').toggleClass('hidden');
			}
			OCA.User_SAML.Admin.setSamlConfigValue('general', key, $(this).val(), true);
		});
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

	$('#user-saml-security input[type="text"], #user-saml-security textarea').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('security', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('security', key, $(this).val());
		}
	});

	$('#user-saml-attribute-mapping input[type="text"], #user-saml-attribute-mapping textarea').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('saml-attribute-mapping', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('saml-attribute-mapping', key, $(this).val());
		}
	});

	$('#user-saml').change(function() {
		if(type === 'saml') {
			// Checks on each request whether the settings make sense or not
			$.ajax({
				url: OC.generateUrl('/apps/user_saml/saml/metadata'),
				data: { idp: OCA.User_SAML.Admin.getConfigIdentifier() },
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
			case 'user-saml-attribute-mapping':
				if (nextSibling.hasClass('hidden')) {
					text = 'Hide attribute mapping settings ...';
				} else {
					text = 'Show attribute mapping settings ...';
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
