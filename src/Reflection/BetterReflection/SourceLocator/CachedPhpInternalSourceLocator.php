<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

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
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;
use function array_key_exists;
use function is_array;
use function is_file;
use function is_string;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strpos;
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
 * three are part of the cache key.
 *
 * The exported blob contains the absolute path of the stub file, but the
 * cache key deliberately contains no paths - the same entries are shared by
 * every PHPStan installation with the same package versions, and survive the
 * installation being moved. The path is therefore stored relative to the
 * phpstorm-stubs package root and resolved against the current installation
 * on import (https://github.com/phpstan/phpstan/issues/15023).
 */
final class CachedPhpInternalSourceLocator implements SourceLocator
{

	private const STUBS_DIR_MARKER = '/jetbrains/phpstorm-stubs/';

	private const RELATIVE_FILENAME_PREFIX = 'phpstorm-stubs:';

	private ?string $variableCacheKey = null;

	private ?string $stubsRootDir = null;

	public function __construct(
		private SourceLocator $inner,
		private Cache $cache,
		private PhpVersion $phpVersion,
		private ExtensionVersionProvider $extensionVersionProvider,
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
			$exported = $this->relativizeStubFilename($reflection->exportToCache());
			if ($exported !== null) {
				$this->cache->save($cacheKey, $variableCacheKey, $exported);
			}
		}

		return $reflection;
	}

	/**
	 * @param array<string, mixed> $exported
	 * @return array<string, mixed>|null
	 */
	private function relativizeStubFilename(array $exported): ?array
	{
		$filename = $exported['locatedSource']['data']['filename'] ?? null;
		if (!is_string($filename)) {
			return null;
		}

		$markerPosition = strpos($filename, self::STUBS_DIR_MARKER);
		if ($markerPosition === false) {
			return null;
		}

		$exported['locatedSource']['data']['filename'] = self::RELATIVE_FILENAME_PREFIX . substr($filename, $markerPosition + strlen(self::STUBS_DIR_MARKER));

		return $exported;
	}

	/**
	 * @param array<string, mixed> $cached
	 * @return array<string, mixed>|null
	 */
	private function resolveStubFilename(array $cached): ?array
	{
		$filename = $cached['locatedSource']['data']['filename'] ?? null;
		if (!is_string($filename) || !str_starts_with($filename, self::RELATIVE_FILENAME_PREFIX)) {
			return null;
		}

		$resolved = ($this->stubsRootDir ??= ComposerHelper::getPhpStormStubsDir()) . '/' . substr($filename, strlen(self::RELATIVE_FILENAME_PREFIX));
		if (!is_file($resolved)) {
			return null;
		}

		$cached['locatedSource']['data']['filename'] = $resolved;

		return $cached;
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
			'v3-%s-%s-%s-%s',
			ComposerHelper::getBetterReflectionVersion(),
			ComposerHelper::getPhpStormStubsVersion(),
			$this->phpVersion->getVersionString(),
			$this->extensionVersionProvider->getCacheKey(),
		);
	}

}
