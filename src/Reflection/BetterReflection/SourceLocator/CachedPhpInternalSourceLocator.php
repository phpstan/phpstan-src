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
use function array_key_exists;
use function is_array;
use function sprintf;
use function strtolower;

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
 */
final class CachedPhpInternalSourceLocator implements SourceLocator
{

	private ?string $variableCacheKey = null;

	public function __construct(
		private SourceLocator $inner,
		private Cache $cache,
		private PhpVersion $phpVersion,
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
			$this->cache->save($cacheKey, $variableCacheKey, $reflection->exportToCache());
		}

		return $reflection;
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
			'v1-%s-%s-%s',
			ComposerHelper::getBetterReflectionVersion(),
			ComposerHelper::getPhpStormStubsVersion(),
			$this->phpVersion->getVersionString(),
		);
	}

}
