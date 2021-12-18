<?php declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\DowngradePhp72\Rector\FunctionLike\DowngradeObjectTypeDeclarationRector;
use Rector\DowngradePhp73\Rector\FuncCall\DowngradeTrailingCommasInFunctionCallsRector;
use Rector\DowngradePhp74\Rector\Coalesce\DowngradeNullCoalescingOperatorRector;
use Rector\DowngradePhp74\Rector\ArrowFunction\ArrowFunctionToAnonymousFunctionRector;
use Rector\DowngradePhp74\Rector\Property\DowngradeTypedPropertyRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
	$parameters = $containerConfigurator->parameters();

	$parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_71);
	$parameters->set(Option::SKIP, [
		'tests/*/data/*',
		'tests/PHPStan/Analyser/traits/*',
		'tests/PHPStan/Generics/functions.php',
		'tests/e2e/resultCache_1.php',
		'tests/e2e/resultCache_2.php',
		'tests/e2e/resultCache_3.php',
	]);

	$services = $containerConfigurator->services();
	$services->set(DowngradeTypedPropertyRector::class);
	$services->set(DowngradeTrailingCommasInFunctionCallsRector::class);
	$services->set(DowngradeObjectTypeDeclarationRector::class);
	$services->set(DowngradeNullCoalescingOperatorRector::class);
	$services->set(ArrowFunctionToAnonymousFunctionRector::class);
};
