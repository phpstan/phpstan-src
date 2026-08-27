<?php declare(strict_types = 1);

// typo3/class-alias-loader replaces Composer's class loader with its own wrapper and
// resolves these names through class_alias() at runtime.
return [
	'AliasLoaderE2e\\LegacyName' => 'AliasLoaderE2e\\ModernName',
];
