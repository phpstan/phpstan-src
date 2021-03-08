<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;

class BenevolentUnionType extends UnionType
{

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

		return TypeCombinator::union(...$resultTypes);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		$results = [];
		foreach ($this->getTypes() as $innerType) {
			$results[] = $acceptingType->accepts($innerType, $strictTypes);
		}

		return TrinaryLogic::createNo()->or(...$results);
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

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['types']);
	}

}
