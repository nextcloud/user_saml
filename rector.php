<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Nextcloud\Rector\Set\NextcloudSets;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/appinfo',
		__DIR__ . '/lib',
		__DIR__ . '/tests',
	])
	->withSkip([
		__DIR__ . '/tests/integration/vendor',
	])
	// uncomment to reach your current PHP version
	->withPhpSets(php81: true)
	->withSets([
		NextcloudSets::NEXTCLOUD_30,
	])
	->withTypeCoverageLevel(0);
