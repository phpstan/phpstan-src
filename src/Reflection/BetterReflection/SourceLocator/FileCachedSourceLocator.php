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
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function hash;
use function sprintf;
use function strtolower;

final class FileCachedSourceLocator implements SourceLocator
{

	private const NOT_FOUND = null;
	private const NULL_CACHED = true;

	/** @var array{classes: array<string, ?Reflection>, functions: array<string, ?Reflection>, constants: array<string, ?Reflection>} */
	private array $cachedSymbols = ['classes' => [], 'functions' => [], 'constants' => []];

	/**
	 * @param non-empty-string $cacheKey
	 */
	public function __construct(
		private SourceLocator $locator,
		private Cache $cache,
		private PhpVersion $phpVersion,
		private string $cacheKey,
	)
	{
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$className = strtolower($identifier->getName());

			if (!array_key_exists($className, $this->cachedSymbols['classes'])) {
				$result = $this->loadCache($identifier, $reflector);
				if ($result === self::NOT_FOUND) {
					$result = $this->locator->locateIdentifier($reflector, $identifier);
					$this->storeCache($identifier, $result);
				} elseif ($result === self::NULL_CACHED) {
					$result = null;
				}
				$this->cachedSymbols['classes'][$className] = $result;
			}
			return $this->cachedSymbols['classes'][$className];
		}

		if ($identifier->isFunction()) {
			$className = strtolower($identifier->getName());

			if (!array_key_exists($className, $this->cachedSymbols['functions'])) {
				$result = $this->loadCache($identifier, $reflector);
				if ($result === self::NOT_FOUND) {
					$result = $this->locator->locateIdentifier($reflector, $identifier);
					$this->storeCache($identifier, $result);
				} elseif ($result === self::NULL_CACHED) {
					$result = null;
				}
				$this->cachedSymbols['functions'][$className] = $result;
			}
			return $this->cachedSymbols['functions'][$className];
		}

		if ($identifier->isConstant()) {
			$constantName = ConstantNameHelper::normalize($identifier->getName());

			if (!array_key_exists($constantName, $this->cachedSymbols['constants'])) {
				$result = $this->loadCache($identifier, $reflector);
				if ($result === self::NOT_FOUND) {
					$result = $this->locator->locateIdentifier($reflector, $identifier);
					$this->storeCache($identifier, $result);
				} elseif ($result === self::NULL_CACHED) {
					$result = null;
				}
				$this->cachedSymbols['constants'][$constantName] = $result;
			}
			return $this->cachedSymbols['constants'][$constantName];
		}

		return null;
	}

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->locator->locateIdentifiersByType($reflector, $identifierType);
	}

	private function loadCache(
		Identifier $identifier,
		Reflector $reflector,
	): ReflectionClass|ReflectionFunction|ReflectionConstant|true|null
	{
		[$cacheKey, $variableCacheKey] = $this->getCacheKeys($identifier);
		$cachedReflection = $this->cache->load(
			$cacheKey,
			$variableCacheKey,
		);

		if ($cachedReflection === self::NOT_FOUND) {
			return self::NOT_FOUND;
		}
		if ($cachedReflection === self::NULL_CACHED) {
			return self::NULL_CACHED;
		}

		if ($identifier->isClass()) {
			if (array_key_exists('backingType', $cachedReflection)) {
				return ReflectionEnum::importFromCache($reflector, $cachedReflection);
			}

			return ReflectionClass::importFromCache($reflector, $cachedReflection);
		}

		if ($identifier->isFunction()) {
			return ReflectionFunction::importFromCache($reflector, $cachedReflection);
		}

		return ReflectionConstant::importFromCache($reflector, $cachedReflection);
	}

	private function storeCache(
		Identifier $identifier,
		Reflection|null $reflection,
	): void
	{
		[$cacheKey, $variableCacheKey] = $this->getCacheKeys($identifier);

		$exported = self::NULL_CACHED;
		if ($reflection !== null) {
			if (
				!$reflection instanceof ReflectionClass
				&& !$reflection instanceof ReflectionFunction
				&& !$reflection instanceof ReflectionConstant
			) {
				throw new ShouldNotHappenException();
			}

			$exported = $reflection->exportToCache();
		}

		$this->cache->save(
			$cacheKey,
			$variableCacheKey,
			$exported,
		);
	}

	/** @return array{non-empty-string, non-empty-string} */
	private function getCacheKeys(Identifier $identifier): array
	{
		$suffix = $this->getCacheKeySuffix($identifier);

		return [
			sprintf('%s-%s', $this->cacheKey, $suffix),
			sprintf('v3-%s-%s-%s', ComposerHelper::getBetterReflectionVersion(), $this->phpVersion->getVersionString(), $suffix),
		];
	}

	/** @return non-empty-string */
	private function getCacheKeySuffix(Identifier $identifier): string
	{
		$identifierHash = hash('sha256', $identifier->getName());
		return sprintf('%s-%s', $identifier->getType()->getName(), $identifierHash);
	}

}
