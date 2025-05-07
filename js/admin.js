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
						OCA.User_SAML.Admin.currentConfig = OCA.User_SAML.Admin.providerIds.split(',')[0];
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

		/**
		 * Add a new provider
		 */
		addProvider: function (callback) {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', OC.generateUrl('/apps/user_saml/settings/providerSettings'));
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
			xhr.open('PUT', OC.generateUrl('/apps/user_saml/settings/providerSettings/' + this.currentConfig));
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

		testMetaData: function() {
			// Checks on each request whether the settings make sense or not
			const type = document.getElementById('user-saml').dataset.type;
			if (type === 'environment-variable') {
				return
			}

			let xhr = new XMLHttpRequest();
			xhr.open('GET', OC.generateUrl('/apps/user_saml/saml/metadata?idp=' + this.currentConfig));
			xhr.setRequestHeader('Content-Type', 'application/json');
			xhr.setRequestHeader('requesttoken', OC.requestToken);

			xhr.onload = function () {
				if (xhr.status >= 200 && xhr.status < 300) {
					document.getElementById('user-saml-settings-complete').classList.remove('hidden');
					document.getElementById('user-saml-settings-incomplete').classList.add('hidden');
				} else {
					document.getElementById('user-saml-settings-complete').classList.add('hidden');
					document.getElementById('user-saml-settings-incomplete').classList.remove('hidden');
				}
			};
			xhr.onerror = function () {
				document.getElementById('user-saml-settings-complete').classList.add('hidden');
				document.getElementById('user-saml-settings-incomplete').classList.remove('hidden');
			};

			xhr.send();
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
					OCA.User_SAML.Admin.testMetaData();
				},
				error: function() {
					OC.msg.finishedSaving('#user-saml-save-indicator', {status: 'error', data: {message: t('user_saml', 'Could not save')}});
					// reset any meta data indicator as the test would not be called now, old state might be misleading
					document.getElementById('user-saml-settings-complete').classList.add('hidden');
					document.getElementById('user-saml-settings-incomplete').classList.add('hidden');
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
			$('#user-saml-filtering').removeClass('hidden');
		} else {
			$('#user-saml-attribute-mapping').addClass('hidden');
			$('#user-saml-filtering').addClass('hidden');
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
			document.querySelectorAll('#user-saml-settings input[type="text"], #user-saml-settings textarea').forEach(function (inputNode) {
				inputNode.value = '';
			});
			document.querySelectorAll('#user-saml-settings input[type="checkbox"]').forEach(function (inputNode) {
				inputNode.checked = false;
				inputNode.setAttribute('value', '0');
			});
			document.querySelectorAll('#user-saml-settings select option').forEach(function (inputNode) {
				inputNode.selected = false;
			});

			Object.keys(data).forEach(function(category){
				var entries = data[category];
				Object.keys(entries).forEach(function (configKey) {
					var htmlElement = document.querySelector('#user-saml-settings *[data-key="' + configKey + '"]')
						|| document.querySelector('#user-saml-' + category + ' #user-saml-' + configKey)
						|| document.querySelector('#user-saml-' + category + ' [name="' + configKey + '"]');

					if (!htmlElement) {
						console.log("could not find element for " + configKey);
						return;
					}

					if ((htmlElement.tagName === 'INPUT' && htmlElement.getAttribute('type') === 'text')
						 || htmlElement.tagName === 'TEXTAREA'
					) {
						htmlElement.nodeValue = entries[configKey];
						htmlElement.value = entries[configKey];
					} else if (htmlElement.tagName === 'INPUT' && htmlElement.getAttribute('type') === 'checkbox') {
						htmlElement.checked = entries[configKey] === '1';
						htmlElement.value = entries[configKey] === '1' ? '1' : '0';
					} else if (htmlElement.tagName === 'SELECT') {
						htmlElement.querySelector('[value="' + entries[configKey] + '"]').selected = true;
					} else {
						console.error("Could not handle " + configKey + " Tag is " + htmlElement.tagName + " and type is " + htmlElement.getAttribute("type"));
					}
				});
			});

			var xmlDownloadButton = document.getElementById('get-metadata');
			var url = xmlDownloadButton.dataset.base + '?idp=' + providerId;
			xmlDownloadButton.setAttribute('href', url);
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
				$('#user-saml-filtering').toggleClass('hidden');
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

	$('#user-saml-filtering input[type="text"]').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('saml-user-filter', key, $(this).val());
		});
		if (e.keyCode === 13) {
			var key = $(this).attr('name');
			OCA.User_SAML.Admin.setSamlConfigValue('saml-user-filter', key, $(this).val());
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
			case 'user-saml-filtering':
				if (nextSibling.hasClass('hidden')) {
					text = 'Hide user filter settings ...';
				} else {
					text = 'Show user filter settings ...';
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
