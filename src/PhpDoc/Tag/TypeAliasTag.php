<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/** @api */
class TypeAliasTag
{

	private string $aliasName;

	private TypeNode $typeNode;

	private NameScope $nameScope;

	public function __construct(
		string $aliasName,
		TypeNode $typeNode,
		NameScope $nameScope
	)
	{
		$this->aliasName = $aliasName;
		$this->typeNode = $typeNode;
		$this->nameScope = $nameScope;
	}

	public function getAliasName(): string
	{
		return $this->aliasName;
	}

	public function getTypeAlias(): \PHPStan\Type\TypeAlias
	{
		return new \PHPStan\Type\TypeAlias(
			$this->typeNode,
			$this->nameScope
		);
	}

}
