<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Cache\Cache;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ReflectionProvider;
use function strtolower;

final class CachedVendorReflectionProvider implements ReflectionProvider
{

	/** @var array<string, bool> */
	private array $hasClasses = [];

	/** @var array<string, ClassReflection> */
	private array $classes = [];

	/** @var array<string, string> */
	private array $classNames = [];

	private const CACHE_ROOT_KEY = 'vendor-reflections';
	private const CACHE_VARIABLE_KEY = 'v1';

	private bool $needsUpdate = false;

	public function __construct(
		private ReflectionProvider $provider,
		private Cache $cache,
	)
	{
		register_shutdown_function(function() {
			if ($this->needsUpdate) {
				$this->persist();
			}
		});

		$this->loadCache();
	}

	private function persist(): void
	{
		$this->cache->save(self::CACHE_ROOT_KEY, self::CACHE_VARIABLE_KEY, [
			$this->hasClasses,
			$this->classNames
		]);
	}

	private function loadCache(): void
	{
		$cached = $this->cache->load(self::CACHE_ROOT_KEY, self::CACHE_VARIABLE_KEY);
		if ($cached !== null) {
			[
				$this->hasClasses,
				$this->classNames
			] = $cached;
		}
	}

	public function hasClass(string $className): bool
	{
		if (isset($this->hasClasses[$className])) {
			return $this->hasClasses[$className];
		}
		$this->needsUpdate = true;

		return $this->hasClasses[$className] = $this->provider->hasClass($className);
	}

	public function getClass(string $className): ClassReflection
	{
		$lowerClassName = strtolower($className);
		if (isset($this->classes[$lowerClassName])) {
			return $this->classes[$lowerClassName];
		}

		return $this->classes[$lowerClassName] = $this->provider->getClass($className);
	}

	public function getClassName(string $className): string
	{
		$lowerClassName = strtolower($className);
		if (isset($this->classNames[$lowerClassName])) {
			return $this->classNames[$lowerClassName];
		}
		$this->needsUpdate = true;

		return $this->classNames[$lowerClassName] = $this->provider->getClassName($className);
	}

	public function getAnonymousClassReflection(Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		return $this->provider->getAnonymousClassReflection($classNode, $scope);
	}

	public function getUniversalObjectCratesClasses(): array
	{
		return $this->provider->getUniversalObjectCratesClasses();
	}

	public function hasFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->provider->hasFunction($nameNode, $namespaceAnswerer);
	}

	public function getFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): FunctionReflection
	{
		return $this->provider->getFunction($nameNode, $namespaceAnswerer);
	}

	public function resolveFunctionName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		return $this->provider->resolveFunctionName($nameNode, $namespaceAnswerer);
	}

	public function hasConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->provider->hasConstant($nameNode, $namespaceAnswerer);
	}

	public function getConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ConstantReflection
	{
		return $this->provider->getConstant($nameNode, $namespaceAnswerer);
	}

	public function resolveConstantName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		return $this->provider->resolveConstantName($nameNode, $namespaceAnswerer);
	}

}
