<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\ExpressionTypeHolder', implementation: __DIR__ . '/../../turbo-ext/src/ExpressionTypeHolder.cpp')]
#[ReferencedByTurboExtension(key: 'expressionTypeHolder')]
final class ExpressionTypeHolder
{

	public function __construct(
		private readonly Expr $expr,
		private readonly Type $type,
		private readonly TrinaryLogic $certainty,
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
		if ($this->type === $other->type || $this->type->equals($other->type)) {
			if ($this->certainty->and($other->certainty)->yes()) {
				return $this;
			}

			if ($this->certainty->maybe()) {
				return $this;
			}

			return $other;
		}

		return new self(
			$this->expr,
			TypeCombinator::union($this->type, $other->type),
			$this->certainty->and($other->certainty),
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

}
