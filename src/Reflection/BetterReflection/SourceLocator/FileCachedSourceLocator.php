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
use function sprintf;
use function strtolower;

final class FileCachedSourceLocator implements SourceLocator
{

	/** @var array{classes: array<string, ?Reflection>, functions: array<string, ?Reflection>, constants: array<string, ?Reflection>}|null */
	private ?array $cachedSymbols = null;

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
		$this->cachedSymbols ??= $this->loadCache($reflector);

		if ($identifier->isClass()) {
			$className = strtolower($identifier->getName());

			if (!array_key_exists($className, $this->cachedSymbols['classes'])) {
				$this->cachedSymbols['classes'][$className] = $this->locator->locateIdentifier($reflector, $identifier);
				$this->storeCache();
			}
			return $this->cachedSymbols['classes'][$className];
		}
		if ($identifier->isFunction()) {
			$className = strtolower($identifier->getName());

			if (!array_key_exists($className, $this->cachedSymbols['functions'])) {
				$this->cachedSymbols['functions'][$className] = $this->locator->locateIdentifier($reflector, $identifier);
				$this->storeCache();
			}
			return $this->cachedSymbols['functions'][$className];
		}
		if ($identifier->isConstant()) {
			$constantName = ConstantNameHelper::normalize($identifier->getName());

			if (!array_key_exists($constantName, $this->cachedSymbols['constants'])) {
				$this->cachedSymbols['constants'][$constantName] = $this->locator->locateIdentifier($reflector, $identifier);
				$this->storeCache();
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

	/** @return non-empty-string */
	private function getVariableCacheKey(): string
	{
		return sprintf('v2-%s-%s', ComposerHelper::getBetterReflectionVersion(), $this->phpVersion->getVersionString());
	}

	/** @return array{classes: array<string, ReflectionClass|null>, functions: array<string, ReflectionFunction|null>, constants: array<string, ReflectionConstant|null>} */
	private function loadCache(Reflector $reflector): array
	{
		$variableCacheKey = $this->getVariableCacheKey();
		$cached = $this->cache->load($this->cacheKey, $variableCacheKey);

		$restored = [
			'classes' => [],
			'functions' => [],
			'constants' => [],
		];
		if ($cached === null) {
			return $restored;
		}

		foreach ($cached['classes'] ?? [] as $class => $cachedReflection) {
			if ($cachedReflection === null) {
				$restored['classes'][$class] = null;
				continue;
			}

			if (array_key_exists('backingType', $cachedReflection)) {
				$restored['classes'][$class] = ReflectionEnum::importFromCache($reflector, $cachedReflection);
				continue;
			}

			$restored['classes'][$class] = ReflectionClass::importFromCache($reflector, $cachedReflection);
		}
		foreach ($cached['functions'] ?? [] as $class => $cachedReflection) {
			if ($cachedReflection === null) {
				$restored['functions'][$class] = null;
				continue;
			}
			$restored['functions'][$class] = ReflectionFunction::importFromCache($reflector, $cachedReflection);
		}
		foreach ($cached['constants'] ?? [] as $constantName => $cachedReflection) {
			if ($cachedReflection === null) {
				$restored['constants'][$constantName] = null;
				continue;
			}

			$restored['constants'][$constantName] = ReflectionConstant::importFromCache($reflector, $cachedReflection);
		}
		return $restored;
	}

	private function storeCache(): void
	{
		$variableCacheKey = $this->getVariableCacheKey();

		$exported = [
			'classes' => [],
			'functions' => [],
			'constants' => [],
		];
		foreach ($this->cachedSymbols ?? [] as $type => $data) {
			foreach ($data as $name => $reflection) {
				if ($reflection === null) {
					$exported[$type][$name] = $reflection;
					continue;
				}

				if (
					!$reflection instanceof ReflectionClass
					&& !$reflection instanceof ReflectionFunction
					&& !$reflection instanceof ReflectionConstant
				) {
					throw new ShouldNotHappenException();
				}

				$exported[$type][$name] = $reflection->exportToCache();
			}
		}

		$this->cache->save($this->cacheKey, $variableCacheKey, $exported);
	}

}
