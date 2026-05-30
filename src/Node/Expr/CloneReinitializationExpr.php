<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

/**
 * Tracks that a readonly property has been re-assigned within the current __clone() body.
 *
 * Distinct from PropertyInitializationExpr because PHP 8.3+ allows readonly properties
 * to be re-initialized once inside __clone — but the property is already initialized at
 * __clone's entry (carried over from the post-construction class scope), so the standard
 * initialization tracker can't distinguish "first write inside __clone" from "no write yet
 * inside __clone". This expression is only set when an assignment actually happens inside
 * __clone, and is excluded from rememberConstructorExpressions() so it never leaks into
 * __clone's entry scope.
 */
final class CloneReinitializationExpr extends Expr implements VirtualNode
{

	public function __construct(private string $propertyName)
	{
		parent::__construct([]);
	}

	public function getPropertyName(): string
	{
		return $this->propertyName;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_CloneReinitializationExpr';
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
