<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ClosureType;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'inArrowFunctionNode')]
final class InArrowFunctionNode extends NodeAbstract implements VirtualNode
{

	private Node\Expr\ArrowFunction $originalNode;

	public function __construct(private ClosureType $closureType, ArrowFunction $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
		$this->originalNode = $originalNode;
	}

	public function getClosureType(): ClosureType
	{
		return $this->closureType;
	}

	public function getOriginalNode(): Node\Expr\ArrowFunction
	{
		return $this->originalNode;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_InArrowFunctionNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
