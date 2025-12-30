<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use PHPStan\Internal\CombinationsHelper;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner;
use PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles;
use function class_alias;
use function extension_loaded;

final class TurboExtensionEnabler
{

	public static function isLoaded(): bool
	{
		return extension_loaded('phpstanturbo');
	}

	public static function enableIfLoaded(): void
	{
		if (!self::isLoaded()) {
			return;
		}

		class_alias('PHPStanTurbo\CombinationsHelper', CombinationsHelper::class);
		class_alias('PHPStanTurbo\PhpFileCleaner', PhpFileCleaner::class);
		class_alias('PHPStanTurbo\SymbolFinderInFiles', SymbolFinderInFiles::class);
	}

}
