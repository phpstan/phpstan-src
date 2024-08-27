<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

final class TypeAlias
{

	private ?Type $resolvedType = null;

	public function __construct(
		private TypeNode $typeNode,
		private NameScope $nameScope,
	)
	{
	}

	public static function invalid(): self
	{
		$self = new self(new IdentifierTypeNode('*ERROR*'), new NameScope(null, []));
		$self->resolvedType = new CircularTypeAliasErrorType();
		return $self;
	}

	public function resolve(TypeNodeResolver $typeNodeResolver): Type
	{
		if ($this->resolvedType === null) {
			$this->resolvedType = $typeNodeResolver->resolve(
				$this->typeNode,
				$this->nameScope,
			);
		}

		return $this->resolvedType;
	}

}
