<?php declare(strict_types=1);

use PHPStan\Build\RectorCache;
use Rector\Config\RectorConfig;
use Rector\DowngradePhp73\Rector\FuncCall\DowngradeTrailingCommasInFunctionCallsRector;
use Rector\DowngradePhp74\Rector\Coalesce\DowngradeNullCoalescingOperatorRector;
use Rector\DowngradePhp74\Rector\ArrowFunction\ArrowFunctionToAnonymousFunctionRector;
use Rector\DowngradePhp74\Rector\Property\DowngradeTypedPropertyRector;
use Rector\DowngradePhp80\Rector\Catch_\DowngradeNonCapturingCatchesRector;
use Rector\DowngradePhp80\Rector\Class_\DowngradePropertyPromotionRector;
use Rector\DowngradePhp80\Rector\ClassMethod\DowngradeTrailingCommasInParamUseRector;
use Rector\DowngradePhp80\Rector\FunctionLike\DowngradeMixedTypeDeclarationRector;
use Rector\DowngradePhp80\Rector\FunctionLike\DowngradeUnionTypeDeclarationRector;
use Rector\DowngradePhp80\Rector\Property\DowngradeUnionTypeTypedPropertyRector;
use Rector\DowngradePhp81\Rector\FunctionLike\DowngradePureIntersectionTypeRector;
use Rector\DowngradePhp81\Rector\Property\DowngradeReadonlyPropertyRector;

return static function (RectorConfig $config): void {
	$parsePhpVersion = static function (string $version, int $defaultPatch = 0): int {
		$parts = array_map('intval', explode('.', $version));

		return $parts[0] * 10000 + $parts[1] * 100 + ($parts[2] ?? $defaultPatch);
	};
	$targetPhpVersion = getenv('TARGET_PHP_VERSION');
	$targetPhpVersionId = $parsePhpVersion($targetPhpVersion);

	$cache = new RectorCache();
	$config->paths($cache->restore());
	$config->phpVersion($targetPhpVersionId);
	$config->skip(RectorCache::SKIP_PATHS);
	$config->disableParallel();

	if ($targetPhpVersionId < 80100) {
		$config->rule(DowngradeReadonlyPropertyRector::class);
		$config->rule(DowngradePureIntersectionTypeRector::class);
	}

	if ($targetPhpVersionId < 80000) {
		$config->rule(DowngradeTrailingCommasInParamUseRector::class);
		$config->rule(DowngradeNonCapturingCatchesRector::class);
		$config->rule(DowngradeUnionTypeTypedPropertyRector::class);
		$config->rule(DowngradePropertyPromotionRector::class);
		$config->rule(DowngradeUnionTypeDeclarationRector::class);
		$config->rule(DowngradeMixedTypeDeclarationRector::class);
	}

	if ($targetPhpVersionId < 70400) {
		$config->rule(DowngradeTypedPropertyRector::class);
		$config->rule(DowngradeNullCoalescingOperatorRector::class);
		$config->rule(ArrowFunctionToAnonymousFunctionRector::class);
	}

	if ($targetPhpVersionId < 70300) {
		$config->rule(DowngradeTrailingCommasInFunctionCallsRector::class);
	}
};
