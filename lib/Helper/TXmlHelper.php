<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Helper;

trait TXmlHelper {

	/**
	 * @returns mixed returns the result of the callable parameter
	 */
	public function callWithXmlEntityLoader(callable $func) {
		libxml_set_external_entity_loader(static fn ($public, $system) => $system);
		$result = $func();
		libxml_set_external_entity_loader(static fn () => null);
		return $result;
	}
}
