<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData;
use PHPStan\Cache\Cache;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use function array_key_exists;
use function sprintf;

final class CachedPhpStormStubsSourceStubber implements SourceStubber
{

	/** @var array<string, mixed> */
	private array $cached;

	public function __construct(
		private PhpStormStubsSourceStubber $sourceStubber,
		private Cache $cache,
		private PhpVersion $phpVersion,
	)
	{
		[$cacheKey, $variableCacheKey] = $this->getCacheKeys();
		$this->cached = $this->cache->load($cacheKey, $variableCacheKey) ?? [];
	}

	/**
	 * @return array{non-empty-string, string}
	 */
	private function getCacheKeys(): array
	{
		$stubsVersion = ComposerHelper::getPhpStormStubsVersion();
		$cacheKey = sprintf('phpstorm-stubs-%s', $stubsVersion);
		$variableCacheKey = sprintf('v1-%s-%s', ComposerHelper::getBetterReflectionVersion(), $this->phpVersion->getVersionString());

		return [$cacheKey, $variableCacheKey];
	}

	#[\Override]
	public function generateClassStub(string $className): ?StubData
	{
		$this->cached['classes'] ??= [];
		if (!array_key_exists($className, $this->cached['classes'])) {
			$this->cached['classes'][$className] = $this->sourceStubber->generateClassStub($className);
			$this->storeCache();
		}
		return $this->cached['classes'][$className];
	}

	#[\Override]
	public function generateFunctionStub(string $functionName): ?StubData
	{
		$this->cached['functions'] ??= [];
		if (!array_key_exists($functionName, $this->cached['functions'])) {
			$this->cached['functions'][$functionName] = $this->sourceStubber->generateFunctionStub($functionName);
			$this->storeCache();
		}
		return $this->cached['functions'][$functionName];
	}

	#[\Override]
	public function generateConstantStub(string $constantName): ?StubData
	{
		$this->cached['constants'] ??= [];
		if (!array_key_exists($constantName, $this->cached['constants'])) {
			$this->cached['constants'][$constantName] = $this->sourceStubber->generateConstantStub($constantName);
			$this->storeCache();
		}
		return $this->cached['constants'][$constantName];
	}

	public function isPresentClass(string $className): ?bool
	{
		$this->cached['isPresentClass'] ??= [];
		if (!array_key_exists($className, $this->cached['isPresentClass'])) {
			$this->cached['isPresentClass'][$className] = $this->sourceStubber->isPresentClass($className);
			$this->storeCache();
		}
		return $this->cached['isPresentClass'][$className];
	}

	public function isPresentFunction(string $functionName): ?bool
	{
		$this->cached['isPresentFunction'] ??= [];
		if (!array_key_exists($functionName, $this->cached['isPresentFunction'])) {
			$this->cached['isPresentFunction'][$functionName] = $this->sourceStubber->isPresentFunction($functionName);
			$this->storeCache();
		}
		return $this->cached['isPresentFunction'][$functionName];
	}

	private function storeCache(): void
	{
		[$cacheKey, $variableCacheKey] = $this->getCacheKeys();
		$this->cache->save($cacheKey, $variableCacheKey, $this->cached);
	}

}
