<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Turbo\ReferencedByTurboExtension;
use function sprintf;
use function strtolower;

/**
 * Remembers the answers about classes, and records every lookup with ValueDependencyCollector -
 * a remembered answer too, once in each of its contexts: the values of $knownClasses,
 * $unknownClasses and $nameLookups are the context it was recorded in last.
 */
#[ReferencedByTurboExtension(key: 'memoizingReflectionProvider')]
final class MemoizingReflectionProvider implements ReflectionProvider
{

	/** @var array<lowercase-string, int> */
	private array $knownClasses = [];

	/** @var array<string, int> */
	private array $unknownClasses = [];

	/** @var array<lowercase-string, ClassReflection> */
	private array $classes = [];

	/** @var array<lowercase-string, string> */
	private array $classNames = [];

	/** @var array<string, int> kind, namespace and name as written => context */
	private array $nameLookups = [];

	public function __construct(
		private ReflectionProvider $provider,
		private ValueDependencyCollector $valueDependencyCollector,
	)
	{
	}

	public function hasClass(string $className): bool
	{
		$lowerClassName = strtolower($className);
		$context = ValueDependencyCollector::getContext();
		if (isset($this->knownClasses[$lowerClassName])) {
			if ($this->knownClasses[$lowerClassName] !== $context) {
				$this->knownClasses[$lowerClassName] = $context;
				$this->valueDependencyCollector->recordFoundSymbol(ValueDependencyCollector::KIND_CLASS, $className);
			}
			return true;
		}

		if (isset($this->unknownClasses[$className])) {
			if ($this->unknownClasses[$className] !== $context) {
				$this->unknownClasses[$className] = $context;
				$this->valueDependencyCollector->recordMissingSymbol(ValueDependencyCollector::KIND_CLASS, $className);
			}
			return false;
		}

		$result = $this->provider->hasClass($className);

		if ($result) {
			$this->knownClasses[$lowerClassName] = $context;
			$this->valueDependencyCollector->recordFoundSymbol(ValueDependencyCollector::KIND_CLASS, $className);
		} else {
			$this->unknownClasses[$className] = $context;
			$this->valueDependencyCollector->recordMissingSymbol(ValueDependencyCollector::KIND_CLASS, $className);
		}

		return $result;
	}

	public function getClass(string $className): ClassReflection
	{
		$lowerClassName = strtolower($className);
		$classReflection = $this->classes[$lowerClassName] ??= $this->provider->getClass($className);
		$this->recordFoundClass($className, $lowerClassName);

		return $classReflection;
	}

	public function getClassName(string $className): string
	{
		$lowerClassName = strtolower($className);
		$name = $this->classNames[$lowerClassName] ??= $this->provider->getClassName($className);
		$this->recordFoundClass($className, $lowerClassName);

		return $name;
	}

	/**
	 * @param lowercase-string $lowerClassName
	 */
	private function recordFoundClass(string $className, string $lowerClassName): void
	{
		// read after the class was found: building its reflection resolves declarations in other contexts
		$context = ValueDependencyCollector::getContext();
		if (($this->knownClasses[$lowerClassName] ?? null) === $context) {
			return;
		}

		$this->knownClasses[$lowerClassName] = $context;
		$this->valueDependencyCollector->recordFoundSymbol(ValueDependencyCollector::KIND_CLASS, $className);
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
		return $this->resolveFunctionName($nameNode, $namespaceAnswerer) !== null;
	}

	public function getFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): FunctionReflection
	{
		$function = $this->provider->getFunction($nameNode, $namespaceAnswerer);
		$this->recordNameLookup(ValueDependencyCollector::KIND_FUNCTION, $nameNode, $namespaceAnswerer, fn (): ?string => $this->provider->resolveFunctionName($nameNode, $namespaceAnswerer));

		return $function;
	}

	public function resolveFunctionName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		$resolved = $this->provider->resolveFunctionName($nameNode, $namespaceAnswerer);
		$this->recordNameLookup(ValueDependencyCollector::KIND_FUNCTION, $nameNode, $namespaceAnswerer, static fn (): ?string => $resolved);

		return $resolved;
	}

	public function hasConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->resolveConstantName($nameNode, $namespaceAnswerer) !== null;
	}

	public function getConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ConstantReflection
	{
		$constant = $this->provider->getConstant($nameNode, $namespaceAnswerer);
		$this->recordNameLookup(ValueDependencyCollector::KIND_CONSTANT, $nameNode, $namespaceAnswerer, fn (): ?string => $this->provider->resolveConstantName($nameNode, $namespaceAnswerer));

		return $constant;
	}

	public function resolveConstantName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		$resolved = $this->provider->resolveConstantName($nameNode, $namespaceAnswerer);
		$this->recordNameLookup(ValueDependencyCollector::KIND_CONSTANT, $nameNode, $namespaceAnswerer, static fn (): ?string => $resolved);

		return $resolved;
	}

	/**
	 * An unqualified name in a namespace is looked for in the namespace first, then globally:
	 * strlen() in namespace App calls App\strlen() once it is declared. Both are recorded - the
	 * one in the namespace not found, unless it was.
	 *
	 * @param ValueDependencyCollector::KIND_FUNCTION|ValueDependencyCollector::KIND_CONSTANT $kind
	 * @param callable(): ?string $resolve the name it resolved to
	 */
	private function recordNameLookup(string $kind, Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer, callable $resolve): void
	{
		$namespace = $namespaceAnswerer?->getNamespace();
		$name = (string) $nameNode;
		$fallback = $namespace !== null && !$nameNode->isFullyQualified();
		$lookupKey = $kind . ':' . ($fallback ? $namespace . '\\' : '\\') . $name;
		$context = ValueDependencyCollector::getContext();
		if (($this->nameLookups[$lookupKey] ?? null) === $context) {
			return;
		}

		$this->nameLookups[$lookupKey] = $context;
		$resolved = $resolve();
		if ($fallback) {
			$namespacedName = sprintf('%s\\%s', $namespace, $name);
			if ($resolved === $namespacedName) {
				$this->valueDependencyCollector->recordFoundSymbol($kind, $resolved);
				return;
			}

			$this->valueDependencyCollector->recordMissingSymbol($kind, $namespacedName);
		}

		if ($resolved === null) {
			$this->valueDependencyCollector->recordMissingSymbol($kind, $name);
			return;
		}

		$this->valueDependencyCollector->recordFoundSymbol($kind, $resolved);
	}

}
