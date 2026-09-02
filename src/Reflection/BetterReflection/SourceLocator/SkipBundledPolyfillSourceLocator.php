<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\File\FileHelper;
use function str_contains;
use function str_starts_with;

/**
 * PHPStan bundles symfony/polyfill-* packages for its own runtime, so functions and classes like
 * array_first() or NoDiscard are declared in the PHPStan process even when the analysed project
 * knows nothing about them. Locators asking function_exists()/class_exists() would report them
 * as existing symbols of the analysed project.
 *
 * Polyfills installed in the analysed project are unaffected - they're located by the Composer
 * source locators which run before the runtime-based ones.
 */
final class SkipBundledPolyfillSourceLocator implements SourceLocator
{

	private const POLYFILL_PATH_PART = '/symfony/polyfill-';

	/** @var string[] */
	private array $phpstanDirectories;

	/** @param string[] $phpstanDirectories */
	public function __construct(
		private SourceLocator $sourceLocator,
		private FileHelper $fileHelper,
		array $phpstanDirectories,
	)
	{
		$normalized = [];
		foreach ($phpstanDirectories as $phpstanDirectory) {
			$normalized[] = $fileHelper->normalizePath($phpstanDirectory, '/') . '/';
		}

		$this->phpstanDirectories = $normalized;
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		$reflection = $this->sourceLocator->locateIdentifier($reflector, $identifier);
		if ($reflection === null) {
			return null;
		}

		if ($reflection instanceof ReflectionClass || $reflection instanceof ReflectionFunction || $reflection instanceof ReflectionConstant) {
			$fileName = $reflection->getFileName();
			if ($fileName !== null && $this->isBundledPolyfillFile($fileName)) {
				return null;
			}
		}

		return $reflection;
	}

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->sourceLocator->locateIdentifiersByType($reflector, $identifierType);
	}

	private function isBundledPolyfillFile(string $fileName): bool
	{
		$normalized = $this->fileHelper->normalizePath($fileName, '/');
		if (!str_contains($normalized, self::POLYFILL_PATH_PART)) {
			return false;
		}

		foreach ($this->phpstanDirectories as $phpstanDirectory) {
			if (str_starts_with($normalized, $phpstanDirectory)) {
				return true;
			}
		}

		return false;
	}

}
