<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function count;
use function sprintf;

/** @api */
class ConditionalType implements CompoundType
{

	use NonGeneralizableTypeTrait;

	/** @api */
	public function __construct(
		private Type $subjectType,
		private Type $targetType,
		private Type $trueType,
		private Type $falseType,
		private bool $negated,
	)
	{
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return array_merge(
			$this->subjectType->getReferencedClasses(),
			$this->targetType->getReferencedClasses(),
			$this->trueType->getReferencedClasses(),
			$this->falseType->getReferencedClasses(),
		);
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		$results = [];
		foreach ($this->getResultTypes() as $innerType) {
			$results[] = $innerType->accepts($type, $strictTypes);
		}

		return TrinaryLogic::createNo()->or(...$results);
	}

	public function isSuperTypeOf(Type $otherType): TrinaryLogic
	{
		$results = [];
		foreach ($this->getResultTypes() as $innerType) {
			$results[] = $innerType->isSuperTypeOf($otherType);
		}

		return TrinaryLogic::createNo()->or(...$results);
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		$results = [];
		foreach ($this->getResultTypes() as $innerType) {
			$results[] = $otherType->isSuperTypeOf($innerType);
		}

		return TrinaryLogic::extremeIdentity(...$results);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		$results = [];
		foreach ($this->getResultTypes() as $innerType) {
			$results[] = $acceptingType->accepts($innerType, $strictTypes);
		}

		return TrinaryLogic::extremeIdentity(...$results);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof static) {
			return false;
		}

		return $this->subjectType->equals($type->subjectType)
			&& $this->targetType->equals($type->targetType)
			&& $this->trueType->equals($type->trueType)
			&& $this->falseType->equals($type->falseType)
			&& $this->negated === $type->negated;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf(
			'%s %s %s ? %s : %s',
			$this->subjectType->describe($level),
			$this->negated ? 'is not' : 'is',
			$this->targetType->describe($level),
			$this->trueType->describe($level),
			$this->falseType->describe($level),
		);
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $canCallback
	 * @param callable(Type $type): TrinaryLogic $hasCallback
	 */
	private function hasInternal(
		callable $canCallback,
		callable $hasCallback,
	): TrinaryLogic
	{
		$results = [];
		foreach ($this->getResultTypes() as $type) {
			if ($canCallback($type)->no()) {
				$results[] = TrinaryLogic::createNo();
				continue;
			}
			$results[] = $hasCallback($type);
		}

		return TrinaryLogic::extremeIdentity(...$results);
	}

	/**
	 * @template TReturn of object
	 * @param callable(Type $type): TrinaryLogic $hasCallback
	 * @param callable(Type $type): TReturn $getCallback
	 * @return TReturn
	 */
	private function getInternal(
		callable $hasCallback,
		callable $getCallback,
	): object
	{
		$result = null;
		$object = null;

		foreach ($this->getResultTypes() as $type) {
			$has = $hasCallback($type);
			if (!$has->yes()) {
				continue;
			}
			if ($result !== null && $result->compareTo($has) !== $has) {
				continue;
			}

			$get = $getCallback($type);
			$result = $has;
			$object = $get;
		}

		if ($object === null) {
			throw new ShouldNotHappenException();
		}

		return $object;
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->canAccessProperties());
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->hasProperty($propertyName));
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$propertyPrototypes = [];
		foreach ($this->getResultTypes() as $type) {
			if (!$type->hasProperty($propertyName)->yes()) {
				continue;
			}

			$propertyPrototypes[] = $type->getUnresolvedPropertyPrototype($propertyName, $scope)->withFechedOnType($this);
		}

		$propertiesCount = count($propertyPrototypes);
		if ($propertiesCount === 0) {
			throw new ShouldNotHappenException();
		}

		if ($propertiesCount === 1) {
			return $propertyPrototypes[0];
		}

		return new UnionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->canCallMethods());
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->hasMethod($methodName));
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		$methodPrototypes = [];
		foreach ($this->getResultTypes() as $type) {
			if (!$type->hasMethod($methodName)->yes()) {
				continue;
			}

			$methodPrototypes[] = $type->getUnresolvedMethodPrototype($methodName, $scope)->withCalledOnType($this);
		}

		$methodsCount = count($methodPrototypes);
		if ($methodsCount === 0) {
			throw new ShouldNotHappenException();
		}

		if ($methodsCount === 1) {
			return $methodPrototypes[0];
		}

		return new UnionTypeUnresolvedMethodPrototypeReflection($methodName, $methodPrototypes);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->canAccessConstants());
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->hasInternal(
			static fn (Type $type): TrinaryLogic => $type->canAccessConstants(),
			static fn (Type $type): TrinaryLogic => $type->hasConstant($constantName),
		);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->getInternal(
			static fn (Type $type): TrinaryLogic => $type->hasConstant($constantName),
			static fn (Type $type): ConstantReflection => $type->getConstant($constantName),
		);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isIterable());
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isIterableAtLeastOnce());
	}

	public function getIterableKeyType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getIterableKeyType());
	}

	public function getIterableValueType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getIterableValueType());
	}

	public function isArray(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isArray());
	}

	public function isString(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isString());
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isNumericString());
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isNonEmptyString());
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isLiteralString());
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isOffsetAccessible());
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->hasOffsetValueType($offsetType));
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		$types = [];

		foreach ($this->getResultTypes() as $innerType) {
			$valueType = $innerType->getOffsetValueType($offsetType);
			if ($valueType instanceof ErrorType) {
				continue;
			}

			$types[] = $valueType;
		}

		if (count($types) === 0) {
			return new ErrorType();
		}

		return TypeCombinator::union(...$types);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->setOffsetValueType($offsetType, $valueType, $unionValues));
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->unsetOffset($offsetType));
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isCallable());
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		$acceptors = [];

		foreach ($this->getResultTypes() as $type) {
			if ($type->isCallable()->no()) {
				continue;
			}

			$acceptors = array_merge($acceptors, $type->getCallableParametersAcceptors($scope));
		}

		if (count($acceptors) === 0) {
			throw new ShouldNotHappenException();
		}

		return $acceptors;
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isCloneable());
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isSmallerThan($otherType));
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isSmallerThanOrEqual($otherType));
	}

	public function getSmallerType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getSmallerType());
	}

	public function getSmallerOrEqualType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getSmallerOrEqualType());
	}

	public function getGreaterType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getGreaterType());
	}

	public function getGreaterOrEqualType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getGreaterOrEqualType());
	}

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $otherType->isSmallerThan($type));
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $otherType->isSmallerThanOrEqual($type));
	}

	public function toBoolean(): BooleanType
	{
		return $this->unionTypes(static fn (Type $type): BooleanType => $type->toBoolean());
	}

	public function toNumber(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->toNumber());
	}

	public function toString(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->toString());
	}

	public function toInteger(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->toInteger());
	}

	public function toFloat(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->toFloat());
	}

	public function toArray(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->toArray());
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		return $this->trueType->inferTemplateTypes($receivedType)
			->union($this->falseType->inferTemplateTypes($receivedType));
	}

	public function inferTemplateTypesOn(Type $templateType): TemplateTypeMap
	{
		return $templateType->inferTemplateTypes($this->trueType)
			->union($templateType->inferTemplateTypes($this->falseType));
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$references = [];

		foreach ($this->getAllTypes() as $type) {
			foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
				$references[] = $reference;
			}
		}

		return $references;
	}

	public function traverse(callable $cb): Type
	{
		$subject = $cb($this->subjectType);
		$targetType = $cb($this->targetType);
		$trueType = $cb($this->trueType);
		$falseType = $cb($this->falseType);

		if ($this->subjectType !== $subject || $this->targetType !== $targetType || $this->trueType !== $trueType || $this->falseType !== $falseType) {
			return self::create($subject, $targetType, $trueType, $falseType, $this->negated);
		}

		return $this;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->unionTypes(static fn (Type $type): Type => TypeCombinator::remove($type, $typeToRemove));
	}

	public static function create(Type $subject, Type $targetType, Type $trueType, Type $falseType, bool $negated): Type
	{
		$isTrue = $targetType->isSuperTypeOf($subject);

		if ($isTrue->yes()) {
			return !$negated ? $trueType : $falseType;
		} elseif ($isTrue->no()) {
			return !$negated ? $falseType : $trueType;
		}

		$type = new self($subject, $targetType, $trueType, $falseType, $negated);

		$containsTemplate = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$containsTemplate): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType || $type instanceof self) {
				return $traverse($type);
			}

			if ($type instanceof TemplateType) {
				$containsTemplate = true;
			}

			return $type;
		});

		if ($containsTemplate) {
			return $type;
		}

		return TypeCombinator::union($trueType, $falseType);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['subject'],
			$properties['targetType'],
			$properties['trueType'],
			$properties['falseType'],
			$properties['negated'],
		);
	}

	/**
	 * @return non-empty-list<Type>
	 */
	private function getResultTypes(): array
	{
		return [
			$this->trueType,
			$this->falseType,
		];
	}

	/**
	 * @return non-empty-list<Type>
	 */
	private function getAllTypes(): array
	{
		return [
			$this->subjectType,
			$this->targetType,
			$this->trueType,
			$this->falseType,
		];
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $getResult
	 */
	private function unionResults(callable $getResult): TrinaryLogic
	{
		return TrinaryLogic::extremeIdentity(
			$getResult($this->trueType),
			$getResult($this->falseType),
		);
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $getResult
	 */
	private function notBenevolentUnionResults(callable $getResult): TrinaryLogic
	{
		return TrinaryLogic::extremeIdentity(
			$getResult($this->trueType),
			$getResult($this->falseType),
		);
	}

	/**
	 * @template TResult of Type
	 * @param callable(Type $type): TResult $getType
	 * @return TResult
	 */
	protected function unionTypes(callable $getType): Type
	{
		/** @var TResult */
		return TypeCombinator::union(
			$getType($this->trueType),
			$getType($this->falseType),
		);
	}

}
