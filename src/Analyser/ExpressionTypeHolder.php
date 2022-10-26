<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ExpressionTypeHolder
{

	public function __construct(private Type $type, private TrinaryLogic $certainty)
	{
	}

	public static function createYes(Type $type): self
	{
		return new self($type, TrinaryLogic::createYes());
	}

	public static function createMaybe(Type $type): self
	{
		return new self($type, TrinaryLogic::createMaybe());
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
			$type,
			$this->getCertainty()->and($other->getCertainty()),
		);
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
