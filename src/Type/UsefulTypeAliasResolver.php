<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
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

	/** @var array<string, true> */
	private array $inProcess = [];

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public function __construct(
		#[AutowiredParameter(ref: '%typeAliases%')]
		private array $globalTypeAliases,
		private TypeStringResolver $typeStringResolver,
		private ReflectionProvider $reflectionProvider,
	)
	{
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

		return $nameScope->getTypeAlias($aliasName);
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
