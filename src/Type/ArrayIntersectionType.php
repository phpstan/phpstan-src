<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;

class ArrayIntersectionType extends ArrayType
{

	private \PHPStan\Type\ArrayType $arrayType;
	private \PHPStan\Type\Constant\ConstantArrayType $constantArrayType;

	/** @api */
	public function __construct(ArrayType $arrayType, ConstantArrayType $constantArrayType)
	{
		$this->arrayType = $arrayType;
		$this->constantArrayType = $constantArrayType;

		parent::__construct($this->getKeyType(), $this->getItemType());
	}

	public function getKeyType(): Type
	{
		return TypeCombinator::union(
			$this->constantArrayType->getKeyType(),
			$this->arrayType->getKeyType()
		);
	}

	public function getItemType(): Type
	{
		return TypeCombinator::union(
			$this->constantArrayType->getItemType(),
			$this->arrayType->getItemType()
		);
	}

	public function getArrayType(): ArrayType
	{
		return $this->arrayType;
	}

	public function getConstantArrayType(): ConstantArrayType
	{
		return $this->constantArrayType;
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->getConstantArrayType()->equals($type->getConstantArrayType())
			&& $this->getArrayType()->equals($type->getArrayType());
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return CompoundTypeHelper::accepts($type, $this, $strictTypes);
		}

		if ($type instanceof self) {
			return $this->accepts($type->getArrayType(), $strictTypes)
				->and($this->accepts($type->getConstantArrayType(), $strictTypes));
		}

		if ($type instanceof ConstantArrayType) {
			$result = TrinaryLogic::createYes();
			foreach ($type->getKeyTypes() as $i => $keyType) {
				$valueType = $type->getValueTypes()[$i];
				$result->and(
					$this->getConstantArrayType()->accepts(new ConstantArrayType([$keyType], [$valueType]), $strictTypes)
					->or($this->getArrayType()->accepts(new ConstantArrayType([$keyType], [$valueType]), $strictTypes))
				);
			}

			return $result;
		}

		if ($type instanceof ArrayType) {
			return $this->getArrayType()->accepts($type, $strictTypes);
		}

		return TrinaryLogic::createNo();
	}

}
