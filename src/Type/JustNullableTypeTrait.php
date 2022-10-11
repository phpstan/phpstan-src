<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use function get_class;

trait JustNullableTypeTrait
{

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof static) {
			return TrinaryLogic::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return TrinaryLogic::createNo();
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return TrinaryLogic::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function equals(Type $type): bool
	{
		return get_class($type) === static::class;
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

}
