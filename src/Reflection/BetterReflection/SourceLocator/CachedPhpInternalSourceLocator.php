<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\Cache;
use PHPStan\File\FileHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use function array_key_exists;
use function is_array;
use function is_file;
use function is_string;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

/**
 * Caches the reflections built from the PhpStorm stubs the way the optimized
 * locators cache userland ones. PHP built-in classes never had a cache, so
 * every process parsed the stub files and rebuilt the same reflections again;
 * with this in place they are built once, persisted like any other cache
 * entry, and shared across a run's parallel workers through the arena.
 *
 * The stubber's output depends on the stubs package, the reflection library
 * and the target PHP version (stub members are version-filtered), so all
 * three are part of the cache key. The key deliberately contains no
 * installation path — the same cache directory may be shared by installations
 * seeing the project at different absolute paths (host vs. Docker container).
 * The persisted blob must be just as portable, so the stub file name is
 * stored relative to the stubs package and resolved against the current
 * installation on import; an entry whose stub file cannot be resolved here
 * (e.g. written by a phar, read by a vendor install) counts as a cache miss.
 */
final class CachedPhpInternalSourceLocator implements SourceLocator
{

	private ?string $variableCacheKey = null;

	private ?string $stubsDirectory = null;

	public function __construct(
		private SourceLocator $inner,
		private Cache $cache,
		private PhpVersion $phpVersion,
		private FileHelper $fileHelper,
	)
	{
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		$name = $identifier->getName();
		if ($identifier->isClass() || $identifier->isFunction()) {
			$name = strtolower($name);
		}
		$cacheKey = sprintf('phpinternal-%s-%s', $identifier->getType()->getName(), $name);
		$variableCacheKey = $this->getVariableCacheKey();

		$cached = $this->cache->load($cacheKey, $variableCacheKey);
		if (is_array($cached)) {
			$cached = $this->resolveStubFilename($cached);
		}
		if (is_array($cached)) {
			if ($identifier->isConstant()) {
				return ReflectionConstant::importFromCache($reflector, $cached);
			}
			if ($identifier->isFunction()) {
				return ReflectionFunction::importFromCache($reflector, $cached);
			}
			if ($identifier->isClass()) {
				if (array_key_exists('backingType', $cached)) {
					return ReflectionEnum::importFromCache($reflector, $cached);
				}

				return ReflectionClass::importFromCache($reflector, $cached);
			}
		}

		$reflection = $this->inner->locateIdentifier($reflector, $identifier);
		if (
			$reflection instanceof ReflectionClass
			|| $reflection instanceof ReflectionFunction
			|| $reflection instanceof ReflectionConstant
		) {
			$export = $this->relativizeStubFilename($reflection->exportToCache());
			if ($export !== null) {
				$this->cache->save($cacheKey, $variableCacheKey, $export);
			}
		}

		return $reflection;
	}

	/**
	 * @param array<string, mixed> $export
	 * @return array<string, mixed>|null
	 */
	private function relativizeStubFilename(array $export): ?array
	{
		if (!is_array($export['locatedSource'] ?? null) || !is_array($export['locatedSource']['data'] ?? null)) {
			return null;
		}

		$filename = $export['locatedSource']['data']['filename'] ?? null;
		if (!is_string($filename)) {
			return null;
		}

		$stubsDirectoryPrefix = $this->getStubsDirectory() . '/';
		$normalizedFilename = $this->fileHelper->normalizePath($filename, '/');
		if (!str_starts_with($normalizedFilename, $stubsDirectoryPrefix)) {
			return null;
		}

		$export['locatedSource']['data']['filename'] = substr($normalizedFilename, strlen($stubsDirectoryPrefix));

		return $export;
	}

	/**
	 * @param array<string, mixed> $cached
	 * @return array<string, mixed>|null
	 */
	private function resolveStubFilename(array $cached): ?array
	{
		if (!is_array($cached['locatedSource'] ?? null) || !is_array($cached['locatedSource']['data'] ?? null)) {
			return null;
		}

		$relativeFilename = $cached['locatedSource']['data']['filename'] ?? null;
		if (!is_string($relativeFilename)) {
			return null;
		}

		$filename = $this->getStubsDirectory() . '/' . $relativeFilename;
		if (!is_file($filename)) {
			return null;
		}

		$cached['locatedSource']['data']['filename'] = $filename;

		return $cached;
	}

	private function getStubsDirectory(): string
	{
		return $this->stubsDirectory ??= $this->fileHelper->normalizePath(PhpStormStubsMap::DIR, '/');
	}

	/**
	 * @return list<Reflection>
	 */
	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->inner->locateIdentifiersByType($reflector, $identifierType);
	}

	private function getVariableCacheKey(): string
	{
		return $this->variableCacheKey ??= sprintf(
			'v2-%s-%s-%s',
			ComposerHelper::getBetterReflectionVersion(),
			ComposerHelper::getPhpStormStubsVersion(),
			$this->phpVersion->getVersionString(),
		);
	}

}
