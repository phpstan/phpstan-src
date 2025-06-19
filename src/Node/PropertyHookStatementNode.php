<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\PropertyHook;
use PhpParser\Node\Stmt;

/**
 * This class exists because PhpParser\Node\PropertyHook
 * is not a Stmt, but we need to pass it to
 * a few places that expect a Stmt.
 *
 * This is because PhpParser\Node\PropertyHook
 * is likely the one of two PhpParser nodes which contains Stmt[]
 * but itself is not a Stmt.
 *
 * The other one is Expr\Closure, but that one can
 * at least be wrapped in Stmt\Expression.
 */
final class PropertyHookStatementNode extends Stmt implements VirtualNode
{

	public function __construct(PropertyHook $propertyHook)
	{
		parent::__construct($propertyHook->getAttributes());
	}

	/**
	 * @return null
	 */
	public function getReturnType()
	{
		return null;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_PropertyHookStatementNode';
	}

	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
