<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\TypeAlias;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;
use PHPStan\Type\TypeAliasResolver;

class TypeAliasesTypeNodeResolverExtension implements TypeNodeResolverExtension
{

	private TypeAliasResolver $typeAliasResolver;

	public function __construct(
		TypeAliasResolver $typeAliasResolver
	)
	{
		$this->typeAliasResolver = $typeAliasResolver;
	}

	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
	{
		if ($typeNode instanceof IdentifierTypeNode) {
			$aliasName = $typeNode->name;
			return $this->typeAliasResolver->resolveTypeAlias($aliasName, $nameScope);
		}
		return null;
	}

}
