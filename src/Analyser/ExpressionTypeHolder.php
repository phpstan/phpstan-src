<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ExpressionTypeHolder
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

	public function and(self $other): self
	{
		if ($this->getType()->equals($other->getType())) {
			$type = $this->getType();
		} else {
			$type = TypeCombinator::union($this->getType(), $other->getType());
		}
		return new self(
			$this->expr,
			$type,
			$this->getCertainty()->and($other->getCertainty()),
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
