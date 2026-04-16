<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class ExpressionTypeHolder
{

	public function __construct(
		private readonly Expr $expr,
		private readonly Type $type,
		private readonly TrinaryLogic $certainty,
		private readonly ?Expr $assignedFromExpr = null,
	)
	{
	}

	public static function createYes(Expr $expr, Type $type): self
	{
		return new self($expr, $type, TrinaryLogic::createYes());
	}

	public static function createMaybe(Expr $expr, Type $type): self
	{
		return new self($expr, $type, TrinaryLogic::createMaybe());
	}

	public function equalTypes(self $other): bool
	{
		if ($this === $other) {
			return true;
		}

		return $this->type === $other->type || $this->type->equals($other->type);
	}

	public function equals(self $other): bool
	{
		if ($this === $other) {
			return true;
		}

		if (!$this->certainty->equals($other->certainty)) {
			return false;
		}

		return $this->type === $other->type || $this->type->equals($other->type);
	}

	public function and(self $other): self
	{
		$assignedFromExpr = $this->assignedFromExpr === $other->assignedFromExpr ? $this->assignedFromExpr : null;

		if ($this->type === $other->type || $this->type->equals($other->type)) {
			if ($this->certainty->and($other->certainty)->yes()) {
				if ($assignedFromExpr === $this->assignedFromExpr) {
					return $this;
				}
				return $this->withAssignedFromExpr($assignedFromExpr);
			}

			if ($this->certainty->maybe()) {
				if ($assignedFromExpr === $this->assignedFromExpr) {
					return $this;
				}
				return $this->withAssignedFromExpr($assignedFromExpr);
			}

			if ($assignedFromExpr === $other->assignedFromExpr) {
				return $other;
			}
			return $other->withAssignedFromExpr($assignedFromExpr);
		}

		return new self(
			$this->expr,
			TypeCombinator::union($this->type, $other->type),
			$this->certainty->and($other->certainty),
			$assignedFromExpr,
		);
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function getCertainty(): TrinaryLogic
	{
		return $this->certainty;
	}

	public function getAssignedFromExpr(): ?Expr
	{
		return $this->assignedFromExpr;
	}

	public function withAssignedFromExpr(?Expr $assignedFromExpr): self
	{
		return new self($this->expr, $this->type, $this->certainty, $assignedFromExpr);
	}

}
