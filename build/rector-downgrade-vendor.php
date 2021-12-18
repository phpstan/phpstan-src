<?php declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\DowngradePhp72\Rector\FuncCall\DowngradePregUnmatchedAsNullConstantRector;
use Rector\DowngradePhp72\Rector\FuncCall\DowngradeStreamIsattyRector;
use Rector\DowngradePhp72\Rector\FunctionLike\DowngradeObjectTypeDeclarationRector;
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

	$services = $containerConfigurator->services();

	if ($targetPhpVersionId < 70200) {
		$services->set(DowngradeObjectTypeDeclarationRector::class);
		$services->set(DowngradePregUnmatchedAsNullConstantRector::class);
		$services->set(DowngradeStreamIsattyRector::class);
	}
};
