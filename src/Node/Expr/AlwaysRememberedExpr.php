<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Type\Type;

/**
 * Wraps an expression so its type is remembered in the scope even when
 * `rememberPossiblyImpureFunctionValues` is false.
 *
 * TypeSpecifier::createForExpr() returns empty SpecifiedTypes for impure
 * function calls when that setting is off. Wrapping the call in this node
 * bypasses that check (since AlwaysRememberedExpr is not a FuncCall) while
 * MutatingScope::specifyExpressionType() propagates the type to the inner
 * expression as well.
 *
 * Used for function calls whose result should always participate in type
 * narrowing regardless of purity — e.g. class_exists() guards that gate
 * "class not found" errors.
 */
final class AlwaysRememberedExpr extends Expr implements VirtualNode
{

	public function __construct(public Expr $expr, private Type $type, private Type $nativeType)
	{
		parent::__construct([]);
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getExprType(): Type
	{
		return $this->type;
	}

	public function getNativeExprType(): Type
	{
		return $this->nativeType;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_AlwaysRememberedExpr';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return ['expr'];
	}

}
