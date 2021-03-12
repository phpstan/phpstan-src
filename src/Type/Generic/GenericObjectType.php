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

class GenericObjectType extends ObjectType
{

	/** @var array<int, Type> */
	private array $types;

	private ?ClassReflection $classReflection;

	/**
	 * @param array<int, Type> $types
	 */
	public function __construct(
		string $mainType,
		array $types,
		?Type $subtractedType = null,
		?ClassReflection $classReflection = null
	)
	{
		parent::__construct($mainType, $subtractedType, $classReflection);
		$this->types = $types;
		$this->classReflection = $classReflection;
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

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if (!parent::equals($type)) {
			return false;
		}

		if (count($this->types) !== count($type->types)) {
			return false;
		}

		foreach ($this->types as $i => $genericType) {
			$otherGenericType = $type->types[$i];
			if (!$genericType->equals($otherGenericType)) {
				return false;
			}
		}

		return true;
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
			return $type->isAcceptedBy($this, $strictTypes);
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
			return $nakedSuperTypeOf;
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
		$results = [];
		foreach ($typeList as $i => $templateType) {
			if (!isset($ancestor->types[$i])) {
				continue;
			}
			if (!isset($this->types[$i])) {
				continue;
			}
			if ($templateType instanceof ErrorType) {
				continue;
			}
			if (!$templateType instanceof TemplateType) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$results[] = $templateType->isValidVariance($this->types[$i], $ancestor->types[$i]);
		}

		if (count($results) === 0) {
			return $nakedSuperTypeOf;
		}

		return $nakedSuperTypeOf->and(...$results);
	}

	public function getClassReflection(): ?ClassReflection
	{
		if ($this->classReflection !== null) {
			return $this->classReflection;
		}

		$broker = Broker::getInstance();
		if (!$broker->hasClass($this->getClassName())) {
			return null;
		}

		return $this->classReflection = $broker->getClass($this->getClassName())->withTypes($this->types);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		$reflection = $this->getPropertyWithoutTransformingStatic($propertyName, $scope);
		$ancestor = $this->getAncestorWithClassName($reflection->getDeclaringClass()->getName());
		$classReflection = null;
		if ($ancestor !== null) {
			$classReflection = $ancestor->getClassReflection();
		}
		if ($classReflection === null) {
			$classReflection = $reflection->getDeclaringClass();
		}

		return new ResolvedPropertyReflection(
			$this->transformPropertyWithStaticType($classReflection, $reflection),
			$classReflection->getActiveTemplateTypeMap()
		);
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		$reflection = $this->getMethodWithoutTransformingStatic($methodName, $scope);
		$ancestor = $this->getAncestorWithClassName($reflection->getDeclaringClass()->getName());
		$classReflection = null;
		if ($ancestor !== null) {
			$classReflection = $ancestor->getClassReflection();
		}
		if ($classReflection === null) {
			$classReflection = $reflection->getDeclaringClass();
		}

		return new ResolvedMethodReflection(
			$this->transformMethodWithStaticType($classReflection, $reflection),
			$classReflection->getActiveTemplateTypeMap()
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

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
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
					: TemplateTypeVariance::createInvariant()
			);
			foreach ($type->getReferencedTemplateTypes($variance) as $reference) {
				$references[] = $reference;
			}
		}

		return $references;
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
			return $this->recreate($this->getClassName(), $types, $subtractedType);
		}

		return $this;
	}

	/**
	 * @param string $className
	 * @param Type[] $types
	 * @param Type|null $subtractedType
	 * @return self
	 */
	protected function recreate(string $className, array $types, ?Type $subtractedType): self
	{
		return new self(
			$className,
			$types,
			$subtractedType
		);
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
