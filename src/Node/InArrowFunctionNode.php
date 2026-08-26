<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\NodeAbstract;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;

/**
 * @api
 */
final class InArrowFunctionNode extends NodeAbstract implements VirtualNode
{

	private Node\Expr\ArrowFunction $originalNode;

	public function __construct(
		private ClosureType $closureType,
		ArrowFunction $originalNode,
		private ?Type $overriddenType = null,
	)
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

	public function getOverriddenType(): ?Type
	{
		return $this->overriddenType;
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
