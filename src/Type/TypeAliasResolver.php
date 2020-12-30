<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ReflectionProvider;
use function array_key_exists;

class TypeAliasResolver
{

	/** @var array<string, string> */
	private array $aliases;

	private TypeStringResolver $typeStringResolver;

	private ReflectionProvider $reflectionProvider;

	/** @var array<string, Type> */
	private array $resolvedTypes = [];

	/** @var array<string, true> */
	private array $inProcess = [];

	/**
	 * @param array<string, string> $aliases
	 */
	public function __construct(
		array $aliases,
		TypeStringResolver $typeStringResolver,
		ReflectionProvider $reflectionProvider
	)
	{
		$this->aliases = $aliases;
		$this->typeStringResolver = $typeStringResolver;
		$this->reflectionProvider = $reflectionProvider;
	}

	public function hasTypeAlias(string $aliasName): bool
	{
		return array_key_exists($aliasName, $this->aliases);
	}

	public function resolveTypeAlias(string $aliasName, NameScope $nameScope): ?Type
	{
		if (!array_key_exists($aliasName, $this->aliases)) {
			return null;
		}

		if (array_key_exists($aliasName, $this->resolvedTypes)) {
			return $this->resolvedTypes[$aliasName];
		}

		if ($this->reflectionProvider->hasClass($nameScope->resolveStringName($aliasName))) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Type alias %s already exists as a class.', $aliasName));
		}

		if (array_key_exists($aliasName, $this->inProcess)) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Circular definition for type alias %s.', $aliasName));
		}

		$this->inProcess[$aliasName] = true;

		$aliasTypeString = $this->aliases[$aliasName];
		$aliasType = $this->typeStringResolver->resolve($aliasTypeString);
		$this->resolvedTypes[$aliasName] = $aliasType;

		unset($this->inProcess[$aliasName]);

		return $aliasType;
	}

}
