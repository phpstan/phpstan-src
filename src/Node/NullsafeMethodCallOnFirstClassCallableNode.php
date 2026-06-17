<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;

/**
 * Represents `$foo?->bar(...)` - combining the nullsafe operator with the
 * first-class callable syntax. This is a fatal error in PHP ("Cannot combine
 * nullsafe operator with Closure creation"), reported by NullsafeFirstClassCallableRule.
 *
 * @api
 */
final class NullsafeMethodCallOnFirstClassCallableNode extends Expr implements VirtualNode
{

	public function __construct(
		private Expr $var,
		private Identifier|Expr $name,
		private Expr\NullsafeMethodCall $originalNode,
	)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getVar(): Expr
	{
		return $this->var;
	}

	/**
	 * @return Expr|Identifier
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getOriginalNode(): Expr\NullsafeMethodCall
	{
		return $this->originalNode;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_NullsafeFirstClassCallableNode';
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
