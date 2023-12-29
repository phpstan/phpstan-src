<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function implode;
use function sprintf;
use function var_dump;

class GenericStaticType extends StaticType
{

	/**
	 * @param array<int, Type> $types
	 */
	public function __construct(ClassReflection $classReflection, private array $types, ?Type $subtractedType = null)
	{
		parent::__construct($classReflection, $subtractedType);
	}

	/** @return array<int, Type> */
	public function getTypes(): array
	{
		return $this->types;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf(
			'static(%s<%s>)',
			$this->getClassReflection()->getName(),
			implode(', ', array_map(static fn (Type $type): string => $type->describe($level), $this->types)),
		);
	}

	public function changeBaseClass(ClassReflection $classReflection): GenericStaticType
	{
		return new self($classReflection, $this->getTypes(), $this->getSubtractedType());
	}

	public function getStaticObjectType(): ObjectType
	{
		var_dump('GenericStaticType::getStaticObjectType');
		exit;
		if ($this->staticObjectType === null) {
			if ($this->getClassReflection()->isGeneric()) {
				return $this->staticObjectType = new GenericObjectType(
					$this->getClassReflection()->getName(),
					$this->getTypes(),
					$this->getSubtractedType(),
				);
			}

			return $this->staticObjectType = new ObjectType($this->getClassReflection()->getName(), $this->getSubtractedType(), $this->getClassReflection());
		}

		return $this->staticObjectType;
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		var_dump('GenericStaticType::inferTemplateTypes');
		exit;
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if (!$receivedType instanceof TypeWithClassName) {
			return TemplateTypeMap::createEmpty();
		}

		$ancestor = $receivedType->getAncestorWithClassName($this->getClassName());

		if ($ancestor === null) {
			return TemplateTypeMap::createEmpty();
		}
		$ancestorClassReflection = $ancestor->getClassReflection();
		if ($ancestorClassReflection === null) {
			return TemplateTypeMap::createEmpty();
		}

		$otherTypes = $ancestorClassReflection->typeMapToList($ancestorClassReflection->getActiveTemplateTypeMap());
		$typeMap = TemplateTypeMap::createEmpty();

		foreach ($this->getTypes() as $i => $type) {
			$other = $otherTypes[$i] ?? new ErrorType();
			$typeMap = $typeMap->union($type->inferTemplateTypes($other));
		}

		return $typeMap;
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		var_dump('GenericStaticType::getReferencedTemplateTypes');
		exit;
		$classReflection = $this->getClassReflection();

		if ($classReflection !== null) {
			$typeList = $classReflection->typeMapToList($classReflection->getTemplateTypeMap());
		} else {
			$typeList = [];
		}

		$references = [];

		foreach ($this->types as $i => $type) {
			$variance = $positionVariance->compose(
				isset($typeList[$i]) && $typeList[$i] instanceof TemplateType
					? $typeList[$i]->getVariance()
					: TemplateTypeVariance::createInvariant(),
			);
			foreach ($type->getReferencedTemplateTypes($variance) as $reference) {
				$references[] = $reference;
			}
		}

		return $references;
	}

	/**
	 * @param Type[] $types
	 */
	protected function recreate(ClassReflection $classReflection, array $types, ?Type $subtractedType): self
	{
		return new self(
			$classReflection,
			$types,
			$subtractedType,
		);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['classReflection'],
			$properties['types'],
			$properties['subtractedType'] ?? null,
		);
	}

}
