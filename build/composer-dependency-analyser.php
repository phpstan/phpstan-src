<?php declare(strict_types = 1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

$polyfills = [
	'symfony/polyfill-intl-grapheme',
	'symfony/polyfill-intl-normalizer',
	'symfony/polyfill-mbstring',
	'symfony/polyfill-php73',
	'symfony/polyfill-php74',
	'symfony/polyfill-php80',
	'symfony/polyfill-php81',
];

$pinnedToSupportPhp72 = [
	'symfony/process',
	'symfony/service-contracts',
	'symfony/string',
];

return $config
	->addPathToScan(__DIR__ . '/../bin', true)
	->ignoreErrorsOnPackages(
		[
			'hoa/regex', // used only via stream wrapper hoa://
			...$pinnedToSupportPhp72, // those are unused, but we need to pin them to support PHP 7.2
			...$polyfills, // not detected by composer-dependency-analyser
		],
		[ErrorType::UNUSED_DEPENDENCY],
	)
	->ignoreErrorsOnPackage('phpunit/phpunit', [ErrorType::DEV_DEPENDENCY_IN_PROD]) // prepared test tooling
	->ignoreErrorsOnPackage('jetbrains/phpstorm-stubs', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]) // there is no direct usage, but we need newer version then required by ondrejmirtes/BetterReflection
	->ignoreErrorsOnPath(__DIR__ . '/../tests', [ErrorType::UNKNOWN_CLASS, ErrorType::UNKNOWN_FUNCTION]) // to be able to test invalid symbols
	->ignoreUnknownClasses([
		'JetBrains\PhpStorm\Pure', // not present on composer's classmap
		'PHPStan\ExtensionInstaller\GeneratedConfig', // generated
	]);
