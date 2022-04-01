<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class VariableTypeHolder
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
		$thisType = $this->getType();
		$otherType = $other->getType();

		if ($thisType->equals($otherType)) {
			$type = $thisType;
		} elseif ($thisType instanceof ConstantArrayType && $otherType instanceof ConstantArrayType && $thisType->getDepth() + $otherType->getDepth() > ConstantArrayTypeBuilder::ARRAY_DEPTH_LIMIT) {
			// TODO: this is just "cutting of" the type because generalization would trigger perf problems again.
			// is there a better way of handling this? or a better place for it?
			$type = $thisType;
		} else {
			$type = TypeCombinator::union($thisType, $otherType);
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
