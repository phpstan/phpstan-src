<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'booleanAndNode')]
final class BooleanAndNode extends Expr implements VirtualNode
{

	public function __construct(private BooleanAnd|LogicalAnd $originalNode, private Scope $rightScope)
	{
		// the original's printed expression key must not become this node's
		$attributes = $originalNode->getAttributes();
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		parent::__construct($attributes);
	}

	public function getOriginalNode(): BooleanAnd|LogicalAnd
	{
		return $this->originalNode;
	}

	public function getRightScope(): Scope
	{
		return $this->rightScope;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_BooleanAndNode';
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
