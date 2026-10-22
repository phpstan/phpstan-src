<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ImplementedInTurboExtension;
use PHPStan\Turbo\ReferencedByTurboExtension;

#[ReferencedByTurboExtension(key: 'noopExpressionNode')]
#[ImplementedInTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/NoopExpressionNode.cpp')]
final class NoopExpressionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Expr $originalExpr, private bool $hasAssign)
	{
		parent::__construct($this->originalExpr->getAttributes());
	}

	public function getOriginalExpr(): Expr
	{
		return $this->originalExpr;
	}

	public function hasAssign(): bool
	{
		return $this->hasAssign;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_NoopExpressionNode';
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
