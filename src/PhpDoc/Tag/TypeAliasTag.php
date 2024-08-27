<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\TypeAlias;

/**
 * @api
 * @final
 */
class TypeAliasTag
{

	public function __construct(
		private string $aliasName,
		private TypeNode $typeNode,
		private NameScope $nameScope,
	)
	{
	}

	public function getAliasName(): string
	{
		return $this->aliasName;
	}

	public function getTypeAlias(): TypeAlias
	{
		return new TypeAlias(
			$this->typeNode,
			$this->nameScope,
		);
	}

}
