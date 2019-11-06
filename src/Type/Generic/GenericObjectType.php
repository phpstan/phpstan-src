<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

final class GenericObjectType extends ObjectType
{

	/** @var array<int, Type> */
	private $types;

	/**
	 * @param array<int, Type> $types
	 */
	public function __construct(
		string $mainType,
		array $types,
		?Type $subtractedType = null
	)
	{
		parent::__construct($mainType, $subtractedType);
		$this->types = $types;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf(
			'%s<%s>',
			parent::describe($level),
			implode(', ', array_map(static function (Type $type) use ($level): string {
				return $type->describe($level);
			}, $this->types))
		);
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		$classes = parent::getReferencedClasses();
		foreach ($this->types as $type) {
			foreach ($type->getReferencedClasses() as $referencedClass) {
				$classes[] = $referencedClass;
			}
		}

		return $classes;
	}

	/** @return array<int, Type> */
	public function getTypes(): array
	{
		return $this->types;
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return $this->isSuperTypeOfInternal($type, true);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		return $this->isSuperTypeOfInternal($type, false);
	}

	private function isSuperTypeOfInternal(Type $type, bool $acceptsContext): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		$nakedSuperTypeOf = parent::isSuperTypeOf($type);
		if ($nakedSuperTypeOf->no()) {
			return $nakedSuperTypeOf;
		}

		if (!$type instanceof ObjectType) {
			return $nakedSuperTypeOf;
		}

		$ancestor = $type->getAncestorWithClassName($this->getClassName());
		if ($ancestor === null) {
			return $nakedSuperTypeOf->and(TrinaryLogic::createMaybe());
		}
		if (!$ancestor instanceof self) {
			if ($acceptsContext) {
				return $nakedSuperTypeOf;
			}

			return $nakedSuperTypeOf->and(TrinaryLogic::createMaybe());
		}

		if (count($this->types) !== count($ancestor->types)) {
			return TrinaryLogic::createNo();
		}

		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return $nakedSuperTypeOf;
		}

		$typeList = $classReflection->typeMapToList($classReflection->getTemplateTypeMap());

		foreach ($typeList as $i => $templateType) {
			if (!isset($ancestor->types[$i])) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			if (!isset($this->types[$i])) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			if (!$templateType instanceof TemplateType) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			if (!$templateType->isValidVariance($this->types[$i], $ancestor->types[$i])) {
				return TrinaryLogic::createNo();
			}
		}

		return $nakedSuperTypeOf;
	}

	public function getClassReflection(): ?ClassReflection
	{
		$broker = Broker::getInstance();
		if (!$broker->hasClass($this->getClassName())) {
			return null;
		}

		return $broker->getClass($this->getClassName())->withTypes($this->types);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		$reflection = parent::getProperty($propertyName, $scope);

		return new ResolvedPropertyReflection(
			$reflection,
			$this->getClassReflection()->getActiveTemplateTypeMap()
		);
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		$reflection = parent::getMethod($methodName, $scope);

		return new ResolvedMethodReflection(
			$reflection,
			$this->getClassReflection()->getActiveTemplateTypeMap()
		);
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if (!$receivedType instanceof TypeWithClassName) {
			return TemplateTypeMap::createEmpty();
		}

		$ancestor = $receivedType->getAncestorWithClassName($this->getClassName());

		if ($ancestor === null || !$ancestor instanceof GenericObjectType) {
			return TemplateTypeMap::createEmpty();
		}

		$otherTypes = $ancestor->getTypes();
		$typeMap = TemplateTypeMap::createEmpty();

		foreach ($this->getTypes() as $i => $type) {
			$other = $otherTypes[$i] ?? new ErrorType();
			$typeMap = $typeMap->union($type->inferTemplateTypes($other));
		}

		return $typeMap;
	}

	public function traverse(callable $cb): Type
	{
		$subtractedType = $this->getSubtractedType() !== null ? $cb($this->getSubtractedType()) : null;

		$typesChanged = false;
		$types = [];
		foreach ($this->types as $type) {
			$newType = $cb($type);
			$types[] = $newType;
			if ($newType === $type) {
				continue;
			}

			$typesChanged = true;
		}

		if ($subtractedType !== $this->getSubtractedType() || $typesChanged) {
			return new static(
				$this->getClassName(),
				$types,
				$subtractedType
			);
		}

		return $this;
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		return new self($this->getClassName(), $this->types, $subtractedType);
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['className'],
			$properties['types'],
			$properties['subtractedType'] ?? null
		);
	}

}
