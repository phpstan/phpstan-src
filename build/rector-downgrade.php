<?php declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\DowngradePhp72\Rector\FunctionLike\DowngradeObjectTypeDeclarationRector;
use Rector\DowngradePhp73\Rector\FuncCall\DowngradeTrailingCommasInFunctionCallsRector;
use Rector\DowngradePhp74\Rector\Coalesce\DowngradeNullCoalescingOperatorRector;
use Rector\DowngradePhp74\Rector\ArrowFunction\ArrowFunctionToAnonymousFunctionRector;
use Rector\DowngradePhp74\Rector\Property\DowngradeTypedPropertyRector;
use Rector\DowngradePhp80\Rector\Catch_\DowngradeNonCapturingCatchesRector;
use Rector\DowngradePhp80\Rector\ClassMethod\DowngradeTrailingCommasInParamUseRector;
use Rector\DowngradePhp80\Rector\Property\DowngradeUnionTypeTypedPropertyRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;



return static function (ContainerConfigurator $containerConfigurator): void {
	$parsePhpVersion = static function (string $version, int $defaultPatch = 0): int {
		$parts = array_map('intval', explode('.', $version));

		return $parts[0] * 10000 + $parts[1] * 100 + ($parts[2] ?? $defaultPatch);
	};
	$targetPhpVersion = getenv('TARGET_PHP_VERSION');
	$targetPhpVersionId = $parsePhpVersion($targetPhpVersion);

	$parameters = $containerConfigurator->parameters();

	$parameters->set(Option::PHP_VERSION_FEATURES, $targetPhpVersionId);
	$parameters->set(Option::SKIP, [
		'tests/*/data/*',
		'tests/*/Fixture/*',
		'tests/PHPStan/Analyser/traits/*',
		'tests/PHPStan/Generics/functions.php',
		'tests/e2e/resultCache_1.php',
		'tests/e2e/resultCache_2.php',
		'tests/e2e/resultCache_3.php',
	]);

	$services = $containerConfigurator->services();

	if ($targetPhpVersionId < 80000) {
		$services->set(DowngradeTrailingCommasInParamUseRector::class);
		$services->set(DowngradeNonCapturingCatchesRector::class);
		$services->set(DowngradeUnionTypeTypedPropertyRector::class);
	}

	if ($targetPhpVersionId < 70400) {
		$services->set(DowngradeTypedPropertyRector::class);
		$services->set(DowngradeNullCoalescingOperatorRector::class);
		$services->set(ArrowFunctionToAnonymousFunctionRector::class);
	}

	if ($targetPhpVersionId < 70300) {
		$services->set(DowngradeTrailingCommasInFunctionCallsRector::class);
	}

	if ($targetPhpVersionId < 70200) {
		$services->set(DowngradeObjectTypeDeclarationRector::class);
	}
};
