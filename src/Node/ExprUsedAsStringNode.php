<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;

/**
 * @api
 */
final class ExprUsedAsStringNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Expr $expression, private Expr|Node\Stmt $originalNode)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getExpression(): Expr
	{
		return $this->expression;
	}

	public function getOriginalNode(): Expr|Node\Stmt
	{
		return $this->originalNode;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ExprUsedAsStringNode';
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
