<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use function count;

/** @api */
class BenevolentUnionType extends UnionType
{

	/**
	 * @api
	 * @param list<Type> $types
	 */
	public function __construct(array $types, bool $normalized = false)
	{
		parent::__construct($types, $normalized);
	}

	public function filterTypes(callable $filterCb): Type
	{
		$result = parent::filterTypes($filterCb);
		if (!$result instanceof self && $result instanceof UnionType) {
			return TypeUtils::toBenevolentUnion($result);
		}

		return $result;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		$result = parent::tryRemove($typeToRemove);
		if ($result === null) {
			return null;
		}

		return TypeUtils::toBenevolentUnion($result);
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

	protected function pickFromTypes(
		callable $getValues,
		callable $criteria,
	): array
	{
		$values = [];
		foreach ($this->getTypes() as $type) {
			$innerValues = $getValues($type);
			if ($innerValues === [] && $criteria($type)) {
				return [];
			}

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

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		$result = AcceptsResult::createNo();
		foreach ($this->getTypes() as $innerType) {
			$result = $result->or($acceptingType->accepts($innerType, $strictTypes));
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
		$newType = parent::traverseSimultaneously($right, $cb);
		if ($newType === $this) {
			return $this;
		}

		return TypeUtils::toBenevolentUnion($newType);
	}

}
