<?php declare(strict_types = 1);

use AliasLoaderE2e\LegacyName;

function (LegacyName $legacy): int {
	return $legacy->doFoo();
};
