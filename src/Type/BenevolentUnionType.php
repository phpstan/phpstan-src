<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use function count;

/** @api */
class BenevolentUnionType extends UnionType
{

	/**
	 * @api
	 * @param Type[] $types
	 */
	public function __construct(array $types)
	{
		parent::__construct($types);
	}

	public function describe(VerbosityLevel $level): string
	{
		return '(' . parent::describe($level) . ')';
	}

	protected function unionTypes(callable $getType): Type
	{
		$resultTypes = [];
		foreach ($this->getTypes() as $type) {
			$result = $getType($type);
			if ($result instanceof ErrorType) {
				continue;
			}

			$resultTypes[] = $result;
		}

		if (count($resultTypes) === 0) {
			return new ErrorType();
		}

		return TypeUtils::toBenevolentUnion(TypeCombinator::union(...$resultTypes));
	}

	protected function pickFromTypes(callable $getValues): array
	{
		$values = [];
		foreach ($this->getTypes() as $type) {
			$innerValues = $getValues($type);
			foreach ($innerValues as $innerType) {
				$values[] = $innerType;
			}
		}

		return $values;
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		$types = [];
		foreach ($this->getTypes() as $innerType) {
			$valueType = $innerType->getOffsetValueType($offsetType);
			if ($valueType instanceof ErrorType) {
				continue;
			}

			$types[] = $valueType;
		}

		if (count($types) === 0) {
			return new ErrorType();
		}

		return TypeUtils::toBenevolentUnion(TypeCombinator::union(...$types));
	}

	protected function unionResults(callable $getResult): TrinaryLogic
	{
		return TrinaryLogic::createNo()->lazyOr($this->getTypes(), $getResult);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
	}

	public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		$result = AcceptsResult::createNo();
		foreach ($this->getTypes() as $innerType) {
			$result = $result->or($acceptingType->acceptsWithReason($innerType, $strictTypes));
		}

		return $result;
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		$types = TemplateTypeMap::createEmpty();

		foreach ($this->getTypes() as $type) {
			$types = $types->benevolentUnion($type->inferTemplateTypes($receivedType));
		}

		return $types;
	}

	public function inferTemplateTypesOn(Type $templateType): TemplateTypeMap
	{
		$types = TemplateTypeMap::createEmpty();

		foreach ($this->getTypes() as $type) {
			$types = $types->benevolentUnion($templateType->inferTemplateTypes($type));
		}

		return $types;
	}

	public function traverse(callable $cb): Type
	{
		$types = [];
		$changed = false;

		foreach ($this->getTypes() as $type) {
			$newType = $cb($type);
			if ($type !== $newType) {
				$changed = true;
			}
			$types[] = $newType;
		}

		if ($changed) {
			return TypeUtils::toBenevolentUnion(TypeCombinator::union(...$types));
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		$types = [];
		$changed = false;

		if (!$right instanceof UnionType) {
			return $this;
		}

		if (count($this->getTypes()) !== count($right->getTypes())) {
			return $this;
		}

		foreach ($this->getSortedTypes() as $i => $leftType) {
			$rightType = $right->getSortedTypes()[$i];
			$newType = $cb($leftType, $rightType);
			if ($leftType !== $newType) {
				$changed = true;
			}
			$types[] = $newType;
		}

		if ($changed) {
			return TypeUtils::toBenevolentUnion(TypeCombinator::union(...$types));
		}

		return $this;
	}

	public function traverseWithVariance(TemplateTypeVariance $variance, callable $cb): Type
	{
		$types = [];
		$changed = false;

		foreach ($this->getTypes() as $type) {
			$newType = $cb($type, $variance);
			if ($type !== $newType) {
				$changed = true;
			}
			$types[] = $newType;
		}

		if ($changed) {
			return TypeUtils::toBenevolentUnion(TypeCombinator::union(...$types));
		}

		return $this;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['types']);
	}

}
