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
		private readonly bool $trackingOnly = false,
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
		$newTrackingOnly = $this->trackingOnly || $other->trackingOnly;

		if ($this->type === $other->type || $this->type->equals($other->type)) {
			$newCertainty = $this->certainty->and($other->certainty);
			if ($newCertainty->yes() && !$newTrackingOnly) {
				return $this;
			}

			if ($this->certainty->maybe() && $this->trackingOnly === $newTrackingOnly) {
				return $this;
			}

			return new self(
				$this->expr,
				$this->type,
				$newCertainty,
				$newTrackingOnly,
			);
		}

		return new self(
			$this->expr,
			TypeCombinator::union($this->type, $other->type),
			$this->certainty->and($other->certainty),
			$newTrackingOnly,
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

	public function isTrackingOnly(): bool
	{
		return $this->trackingOnly;
	}

}
