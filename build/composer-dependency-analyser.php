<?php declare(strict_types = 1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
	->addPathToScan(__DIR__ . '/../bin', true)
	->ignoreErrors([ErrorType::UNUSED_DEPENDENCY])
	->ignoreErrorsOnPackage('phpunit/phpunit', [ErrorType::DEV_DEPENDENCY_IN_PROD]) // prepared test tooling
	->ignoreErrorsOnPackage('jetbrains/phpstorm-stubs', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]) // there is no direct usage, but we need newer version then required by ondrejmirtes/BetterReflection
	->ignoreErrorsOnPath(__DIR__ . '/../tests', [ErrorType::UNKNOWN_CLASS]) // to be able to test invalid symbols
	->ignoreUnknownClasses([
		'JetBrains\PhpStorm\Pure', // not present on composer's classmap
		'PHPStan\ExtensionInstaller\GeneratedConfig', // generated
	]);
