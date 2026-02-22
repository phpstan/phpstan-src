<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Type\Type;

final class PossiblyImpureCallExpr extends Expr implements VirtualNode
{

	public function __construct(
		public Expr $callExpr,
		public Expr $impactedExpr,
		private string $callDescription,
		private Type $declaredReturnType,
	)
	{
		parent::__construct([]);
	}

	public function getCallDescription(): string
	{
		return $this->callDescription;
	}

	public function getDeclaredReturnType(): Type
	{
		return $this->declaredReturnType;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_PossiblyImpureCallExpr';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return ['callExpr', 'impactedExpr'];
	}

}
