<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function count;
use function implode;
use function sprintf;

/** @api */
class GenericObjectType extends ObjectType
{

	/**
	 * @api
	 * @param array<int, Type> $types
	 * @param array<int, TemplateTypeVariance> $variances
	 */
	public function __construct(
		string $mainType,
		private array $types,
		?Type $subtractedType = null,
		private ?ClassReflection $classReflection = null,
		private array $variances = [],
	)
	{
		parent::__construct($mainType, $subtractedType, $classReflection);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf(
			'%s<%s>',
			parent::describe($level),
			implode(', ', array_map(
				static fn (Type $type, ?TemplateTypeVariance $variance = null): string => TypeProjectionHelper::describe($type, $variance, $level),
				$this->types,
				$this->variances,
			)),
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

			$variance = $this->variances[$i] ?? TemplateTypeVariance::createInvariant();
			$otherVariance = $type->variances[$i] ?? TemplateTypeVariance::createInvariant();
			if (!$variance->equals($otherVariance)) {
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

	/** @return array<int, TemplateTypeVariance> */
	public function getVariances(): array
	{
		return $this->variances;
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return $this->isSuperTypeOfInternal($type, true);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return $this->isSuperTypeOfInternal($type, false)->result;
	}

	private function isSuperTypeOfInternal(Type $type, bool $acceptsContext): AcceptsResult
	{
		$nakedSuperTypeOf = new AcceptsResult(parent::isSuperTypeOf($type), []);
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

			return $nakedSuperTypeOf->and(AcceptsResult::createMaybe());
		}

		if (count($this->types) !== count($ancestor->types)) {
			return AcceptsResult::createNo();
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
				throw new ShouldNotHappenException();
			}

			$thisVariance = $this->variances[$i] ?? TemplateTypeVariance::createInvariant();
			$ancestorVariance = $ancestor->variances[$i] ?? TemplateTypeVariance::createInvariant();
			if (!$thisVariance->invariant()) {
				$results[] = $thisVariance->isValidVarianceWithReason($templateType, $this->types[$i], $ancestor->types[$i]);
			} else {
				$results[] = $templateType->isValidVarianceWithReason($this->types[$i], $ancestor->types[$i]);
			}

			$results[] = AcceptsResult::createFromBoolean($thisVariance->validPosition($ancestorVariance));
		}

		if (count($results) === 0) {
			return $nakedSuperTypeOf;
		}

		$result = AcceptsResult::createYes();
		foreach ($results as $innerResult) {
			$result = $result->and($innerResult);
		}

		return $result;
	}

	public function getClassReflection(): ?ClassReflection
	{
		if ($this->classReflection !== null) {
			return $this->classReflection;
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if (!$reflectionProvider->hasClass($this->getClassName())) {
			return null;
		}

		return $this->classReflection = $reflectionProvider->getClass($this->getClassName())
			->withTypes($this->types)
			->withVariances($this->variances);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$prototype = parent::getUnresolvedPropertyPrototype($propertyName, $scope);

		return $prototype->doNotResolveTemplateTypeMapToBounds();
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
	{
		return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		$prototype = parent::getUnresolvedMethodPrototype($methodName, $scope);

		return $prototype->doNotResolveTemplateTypeMapToBounds();
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
		$classReflection = $this->getClassReflection();
		if ($classReflection !== null) {
			$typeList = $classReflection->typeMapToList($classReflection->getTemplateTypeMap());
		} else {
			$typeList = [];
		}

		$references = [];

		foreach ($this->types as $i => $type) {
			$effectiveVariance = $this->variances[$i] ?? TemplateTypeVariance::createInvariant();
			if ($effectiveVariance->invariant() && isset($typeList[$i]) && $typeList[$i] instanceof TemplateType) {
				$effectiveVariance = $typeList[$i]->getVariance();
			}

			$variance = $positionVariance->compose($effectiveVariance);
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
			return $this->recreate($this->getClassName(), $types, $subtractedType, $this->variances);
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof TypeWithClassName) {
			return $this;
		}

		$ancestor = $right->getAncestorWithClassName($this->getClassName());
		if (!$ancestor instanceof self) {
			return $this;
		}

		if (count($this->types) !== count($ancestor->types)) {
			return $this;
		}

		$typesChanged = false;
		$types = [];
		foreach ($this->types as $i => $leftType) {
			$rightType = $ancestor->types[$i];
			$newType = $cb($leftType, $rightType);
			$types[] = $newType;
			if ($newType === $leftType) {
				continue;
			}

			$typesChanged = true;
		}

		if ($typesChanged) {
			return $this->recreate($this->getClassName(), $types, null);
		}

		return $this;
	}

	/**
	 * @param Type[] $types
	 * @param TemplateTypeVariance[] $variances
	 */
	protected function recreate(string $className, array $types, ?Type $subtractedType, array $variances = []): self
	{
		return new self(
			$className,
			$types,
			$subtractedType,
			null,
			$variances,
		);
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		return new self($this->getClassName(), $this->types, $subtractedType, null, $this->variances);
	}

	public function toPhpDocNode(): TypeNode
	{
		/** @var IdentifierTypeNode $parent */
		$parent = parent::toPhpDocNode();
		return new GenericTypeNode(
			$parent,
			array_map(static fn (Type $type) => $type->toPhpDocNode(), $this->types),
			array_map(static fn (TemplateTypeVariance $variance) => $variance->toPhpDocNodeVariance(), $this->variances),
		);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['className'],
			$properties['types'],
			$properties['subtractedType'] ?? null,
			null,
			$properties['variances'] ?? [],
		);
	}

}
