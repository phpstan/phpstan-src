<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ReflectionProvider;
use function array_key_exists;

class TypeAliasResolver
{

	/** @var array<string, string> */
	private array $globalTypeAliases;

	private TypeStringResolver $typeStringResolver;

	private ReflectionProvider $reflectionProvider;

	/** @var array<string, Type> */
	private array $resolvedGlobalTypeAliases = [];

	/** @var array<string, Type> */
	private array $resolvedLocalTypeAliases = [];

	/** @var array<string, true> */
	private array $resolvingClassTypeAliases = [];

	/** @var array<string, true> */
	private array $inProcess = [];

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public function __construct(
		array $globalTypeAliases,
		TypeStringResolver $typeStringResolver,
		ReflectionProvider $reflectionProvider
	)
	{
		$this->globalTypeAliases = $globalTypeAliases;
		$this->typeStringResolver = $typeStringResolver;
		$this->reflectionProvider = $reflectionProvider;
	}

	public function hasTypeAlias(string $aliasName, ?string $classNameScope): bool
	{
		$hasGlobalTypeAlias = array_key_exists($aliasName, $this->globalTypeAliases);
		if ($hasGlobalTypeAlias) {
			return true;
		}

		if ($classNameScope === null || !$this->reflectionProvider->hasClass($classNameScope)) {
			return false;
		}

		$classReflection = $this->reflectionProvider->getClass($classNameScope);
		$localTypeAliases = $classReflection->getTypeAliases();
		return array_key_exists($aliasName, $localTypeAliases);
	}

	public function resolveTypeAlias(string $aliasName, NameScope $nameScope): ?Type
	{
		return $this->resolveLocalTypeAlias($aliasName, $nameScope)
			?? $this->resolveGlobalTypeAlias($aliasName, $nameScope);
	}

	private function resolveLocalTypeAlias(string $aliasName, NameScope $nameScope): ?Type
	{
		$className = $nameScope->getClassName();
		if ($className === null) {
			return null;
		}

		// prevent infinite recursion
		if (array_key_exists($className, $this->resolvingClassTypeAliases)) {
			return null;
		}

		$this->resolvingClassTypeAliases[$className] = true;

		if (!$this->reflectionProvider->hasClass($className)) {
			return null;
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		$localTypeAliases = $classReflection->getTypeAliases();

		unset($this->resolvingClassTypeAliases[$className]);

		if (!array_key_exists($aliasName, $localTypeAliases)) {
			return null;
		}

		$aliasNameInClassScope = $className . '::' . $aliasName;

		if (array_key_exists($aliasNameInClassScope, $this->resolvedLocalTypeAliases)) {
			return $this->resolvedLocalTypeAliases[$aliasNameInClassScope];
		}

		if ($this->reflectionProvider->hasClass($nameScope->resolveStringName($aliasName))) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Type alias %s already exists as a class in scope of %s.', $aliasName, $className));
		}

		if (array_key_exists($aliasName, $this->globalTypeAliases)) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Type alias %s used in scope of %s already exists as a global type alias.', $aliasName, $className));
		}

		if (array_key_exists($aliasNameInClassScope, $this->inProcess)) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Circular definition for type alias %s in scope of %s.', $aliasName, $className));
		}

		$this->inProcess[$aliasNameInClassScope] = true;

		$aliasTypeString = $localTypeAliases[$aliasName];
		$aliasType = $this->typeStringResolver->resolve($aliasTypeString, $nameScope);
		$this->resolvedLocalTypeAliases[$aliasNameInClassScope] = $aliasType;

		unset($this->inProcess[$aliasNameInClassScope]);

		return $aliasType;
	}

	private function resolveGlobalTypeAlias(string $aliasName, NameScope $nameScope): ?Type
	{
		if (!array_key_exists($aliasName, $this->globalTypeAliases)) {
			return null;
		}

		if (array_key_exists($aliasName, $this->resolvedGlobalTypeAliases)) {
			return $this->resolvedGlobalTypeAliases[$aliasName];
		}

		if ($this->reflectionProvider->hasClass($nameScope->resolveStringName($aliasName))) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Type alias %s already exists as a class.', $aliasName));
		}

		if (array_key_exists($aliasName, $this->inProcess)) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Circular definition for type alias %s.', $aliasName));
		}

		$this->inProcess[$aliasName] = true;

		$aliasTypeString = $this->globalTypeAliases[$aliasName];
		$aliasType = $this->typeStringResolver->resolve($aliasTypeString);
		$this->resolvedGlobalTypeAliases[$aliasName] = $aliasType;

		unset($this->inProcess[$aliasName]);

		return $aliasType;
	}

}
