<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\LruCache;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function sprintf;

#[AutowiredService(as: TypeAliasResolver::class)]
final class UsefulTypeAliasResolver implements TypeAliasResolver
{

	/** @var array<string, Type> */
	private array $resolvedGlobalTypeAliases = [];

	/** @var LruCache<Type> */
	private LruCache $resolvedLocalTypeAliases;

	/** @var array<string, true> */
	private array $resolvingClassTypeAliases = [];

	/** @var array<string, true> */
	private array $inProcess = [];

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public function __construct(
		#[AutowiredParameter(ref: '%typeAliases%')]
		private array $globalTypeAliases,
		private TypeStringResolver $typeStringResolver,
		private TypeNodeResolver $typeNodeResolver,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter(ref: '%cache.resolvedLocalTypeAliasesCountMax%')]
		private int $resolvedLocalTypeAliasesCountMax,
	)
	{
		$this->resolvedLocalTypeAliases = new LruCache($this->resolvedLocalTypeAliasesCountMax);
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
		if (array_key_exists($aliasName, $this->globalTypeAliases)) {
			return null;
		}

		if (!$nameScope->hasTypeAlias($aliasName)) {
			return null;
		}

		$className = $nameScope->getClassNameForTypeAlias();
		if ($className === null) {
			return null;
		}

		$aliasNameInClassScope = $className . '::' . $aliasName;

		$resolvedAliasType = $this->resolvedLocalTypeAliases->get($aliasNameInClassScope);
		if ($resolvedAliasType !== null) {
			return $resolvedAliasType;
		}

		// prevent infinite recursion
		if (array_key_exists($className, $this->resolvingClassTypeAliases)) {
			return null;
		}

		$this->resolvingClassTypeAliases[$className] = true;

		if (!$this->reflectionProvider->hasClass($className)) {
			unset($this->resolvingClassTypeAliases[$className]);
			return null;
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		$localTypeAliases = $classReflection->getTypeAliases();

		unset($this->resolvingClassTypeAliases[$className]);

		if (!array_key_exists($aliasName, $localTypeAliases)) {
			return null;
		}

		if (array_key_exists($aliasNameInClassScope, $this->inProcess)) {
			// resolve circular reference as ErrorType to make it easier to detect
			throw new CircularTypeAliasDefinitionException();
		}

		$this->inProcess[$aliasNameInClassScope] = true;

		try {
			$unresolvedAlias = $localTypeAliases[$aliasName];
			$resolvedAliasType = $unresolvedAlias->resolve($this->typeNodeResolver);
		} catch (CircularTypeAliasDefinitionException) {
			$resolvedAliasType = new CircularTypeAliasErrorType();
		}

		// resolved alias types transitively pin ClassReflections and their whole
		// reflection trees, so the cache is bounded and evicts the least recently used
		$this->resolvedLocalTypeAliases->set($aliasNameInClassScope, $resolvedAliasType, 0);
		unset($this->inProcess[$aliasNameInClassScope]);

		return $resolvedAliasType;
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
			throw new ShouldNotHappenException(sprintf('Type alias %s already exists as a class.', $aliasName));
		}

		if (array_key_exists($aliasName, $this->inProcess)) {
			throw new ShouldNotHappenException(sprintf('Circular definition for type alias %s.', $aliasName));
		}

		$this->inProcess[$aliasName] = true;

		$aliasTypeString = $this->globalTypeAliases[$aliasName];
		$aliasType = $this->typeStringResolver->resolve($aliasTypeString);
		$this->resolvedGlobalTypeAliases[$aliasName] = $aliasType;

		unset($this->inProcess[$aliasName]);

		return $aliasType;
	}

}
