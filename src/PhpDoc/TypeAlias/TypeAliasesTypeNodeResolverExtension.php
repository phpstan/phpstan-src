<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\TypeAlias;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use function array_key_exists;

class TypeAliasesTypeNodeResolverExtension implements TypeNodeResolverExtension
{

	private TypeStringResolver $typeStringResolver;

	private ReflectionProvider $reflectionProvider;

	/** @var array<string, string> */
	private array $aliases;

	/** @var array<string, Type> */
	private array $resolvedTypes = [];

	/** @var array<string, true> */
	private array $inProcess = [];

	/**
	 * @param TypeStringResolver $typeStringResolver
	 * @param ReflectionProvider $reflectionProvider
	 * @param array<string, string> $aliases
	 */
	public function __construct(
		TypeStringResolver $typeStringResolver,
		ReflectionProvider $reflectionProvider,
		array $aliases
	)
	{
		$this->typeStringResolver = $typeStringResolver;
		$this->reflectionProvider = $reflectionProvider;
		$this->aliases = $aliases;
	}

	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
	{
		if ($typeNode instanceof IdentifierTypeNode) {
			$aliasName = $typeNode->name;
			if (array_key_exists($aliasName, $this->resolvedTypes)) {
				return $this->resolvedTypes[$aliasName];
			}
			if (!array_key_exists($aliasName, $this->aliases)) {
				return null;
			}

			if ($this->reflectionProvider->hasClass($aliasName)) {
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
		return null;
	}

}
