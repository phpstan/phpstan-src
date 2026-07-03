<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ReflectionProvider;
use function array_key_first;
use function count;
use function strtolower;

final class MemoizingReflectionProvider implements ReflectionProvider
{

	private const CLASSES_MAX = 2048;

	/** @var array<lowercase-string, true> */
	private array $knownClasses = [];

	/** @var array<string, true> */
	private array $unknownClasses = [];

	/** @var array<lowercase-string, ClassReflection> LRU; first entry = least recently used */
	private array $classes = [];

	/** @var array<lowercase-string, string> */
	private array $classNames = [];

	public function __construct(private ReflectionProvider $provider)
	{
	}

	public function hasClass(string $className): bool
	{
		$lowerClassName = strtolower($className);
		if (isset($this->knownClasses[$lowerClassName])) {
			return true;
		}

		if (isset($this->unknownClasses[$className])) {
			return false;
		}

		$result = $this->provider->hasClass($className);

		if ($result) {
			$this->knownClasses[$lowerClassName] = true;
		} else {
			$this->unknownClasses[$className] = true;
		}

		return $result;
	}

	public function getClass(string $className): ClassReflection
	{
		$lowerClassName = strtolower($className);
		if (isset($this->classes[$lowerClassName])) {
			// LRU: move to the most-recently-used position
			$classReflection = $this->classes[$lowerClassName];
			unset($this->classes[$lowerClassName]);

			return $this->classes[$lowerClassName] = $classReflection;
		}

		$classReflection = $this->provider->getClass($className);
		$this->classes[$lowerClassName] = $classReflection;
		if (count($this->classes) > self::CLASSES_MAX) {
			unset($this->classes[array_key_first($this->classes)]);
		}

		return $classReflection;
	}

	public function getClassName(string $className): string
	{
		return $this->classNames[strtolower($className)] ??= $this->provider->getClassName($className);
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
