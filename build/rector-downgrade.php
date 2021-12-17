<?php declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\DowngradePhp73\Rector\FuncCall\DowngradeTrailingCommasInFunctionCallsRector;
use Rector\DowngradePhp74\Rector\Property\DowngradeTypedPropertyRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
	$parameters = $containerConfigurator->parameters();

	$parameters->set(Option::SKIP, [
		'tests/*/data/*',
		'tests/PHPStan/Analyser/traits/*',
		'tests/PHPStan/Generics/functions.php',
	]);

	$services = $containerConfigurator->services();
	$services->set(DowngradeTypedPropertyRector::class);
	$services->set(DowngradeTrailingCommasInFunctionCallsRector::class);
};
