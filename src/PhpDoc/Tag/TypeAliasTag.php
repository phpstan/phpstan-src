<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\TypeAlias;

/**
 * @api
 */
final class TypeAliasTag
{

	/**
	 * @param TemplateTagValueNode[] $templateTagValueNodes
	 */
	public function __construct(
		private string $aliasName,
		private TypeNode $typeNode,
		private NameScope $nameScope,
		private array $templateTagValueNodes = [],
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
			$this->templateTagValueNodes,
			$this->aliasName,
		);
	}

}
