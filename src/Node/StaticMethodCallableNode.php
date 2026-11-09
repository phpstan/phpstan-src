<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'staticMethodCallableNode')]
final class StaticMethodCallableNode extends Expr implements VirtualNode
{

	public function __construct(
		private Name|Expr $class,
		private Identifier|Expr $name,
		private Expr\StaticCall $originalNode,
	)
	{
		// the original's printed expression key must not become this node's
		$attributes = $originalNode->getAttributes();
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		parent::__construct($attributes);
	}

	public function getClass(): Expr|Name
	{
		return $this->class;
	}

	public function getName(): Identifier|Expr
	{
		return $this->name;
	}

	public function getOriginalNode(): Expr\StaticCall
	{
		return $this->originalNode;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_StaticMethodCallableNode';
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
