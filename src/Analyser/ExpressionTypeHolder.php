<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

final class ExpressionTypeHolder
{

	public function __construct(private Expr $expr, private Type $type, private TrinaryLogic $certainty)
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

	public function equals(self $other): bool
	{
		if (!$this->certainty->equals($other->certainty)) {
			return false;
		}

		return $this->type->equals($other->type);
	}

	public function and(self ...$others): self
	{
		if ($others === []) {
			return $this;
		}

		$types = [$this->type];
		$certainty = $this->certainty;
		foreach ($others as $other) {
			$certainty = $certainty->and($other->certainty);
			if ($types[0] === $other->type || $other->type->equals($types[0])) {
				continue;
			}
			$types[] = $other->type;
		}

		if (count($types) === 1) {
			return new self(
				$this->expr,
				$types[0],
				$certainty,
			);
		}

		return new self(
			$this->expr,
			TypeCombinator::union(...$types),
			$certainty,
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
