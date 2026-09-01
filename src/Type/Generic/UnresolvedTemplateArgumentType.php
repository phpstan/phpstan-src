<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PhpParser\Node\Expr;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClassNameToObjectTypeResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function spl_object_id;
use function sprintf;

/**
 * A template argument inferred from the arguments of a `new` expression or a
 * generic call, before the surrounding function body decided what it should be.
 *
 * Exists only during the observation pass of a function body (see
 * NodeScopeResolver::processBodyStmtNodesTwoPass()): every send of the object
 * to a declared type (property, parameter, return, @var) and every method call
 * on it is recorded against the site (the creating node) and the template name,
 * and the second pass substitutes the resolved type. Rules never see it.
 *
 * For every type question it behaves as its delegate - the inferred type, or the
 * template's default/bound when nothing could be inferred - except that it is
 * opaque to equals(): a marker equals only a marker of the same site and template
 * name, never its delegate. Invariant template positions compare arguments with
 * equals(), so `Foo<int>` does not accept `Foo<unresolved(1)>` and a union of
 * the two keeps both members - the marker survives until it is observed.
 *
 * Immutable: turbo's TypeCombinatorCache hashes the object structurally over
 * its properties (the site node by identity) and caches the hash per instance.
 */
final class UnresolvedTemplateArgumentType implements CompoundType
{

	public function __construct(
		private Expr $site,
		private TemplateType $templateType,
		private ?Type $initialType,
	)
	{
		if ($initialType instanceof self) {
			throw new ShouldNotHappenException('The initial type of an unresolved template argument is never itself unresolved.');
		}
	}

	public function getSite(): Expr
	{
		return $this->site;
	}

	public function getTemplateName(): string
	{
		return $this->templateType->getName();
	}

	public function getTemplate(): TemplateType
	{
		return $this->templateType;
	}

	public function getInitialType(): ?Type
	{
		return $this->initialType;
	}

	/**
	 * The type this marker behaves as: the inferred type, or the template's
	 * default/bound when nothing could be inferred.
	 */
	public function getDelegate(): Type
	{
		return $this->initialType ?? $this->templateType->getDefault() ?? $this->templateType->getBound();
	}

	public function withInitialType(?Type $initialType): self
	{
		return new self($this->site, $this->templateType, $initialType);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $type->site === $this->site
			&& $type->templateType->getName() === $this->templateType->getName();
	}

	public function describe(VerbosityLevel $level): string
	{
		if ($level->isCache()) {
			return sprintf('unresolved#%d(%s)', spl_object_id($this->site), $this->getDelegate()->describe($level));
		}

		return sprintf('unresolved(%s)', $this->getDelegate()->describe($level));
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		return $this->getDelegate()->accepts($type, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		return $this->getDelegate()->isSuperTypeOf($type);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return $acceptingType->accepts($this->getDelegate(), $strictTypes);
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		return $otherType->isSuperTypeOf($this->getDelegate());
	}

	public function isGreaterThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $otherType->isSmallerThan($this->getDelegate(), $phpVersion);
	}

	public function isGreaterThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $otherType->isSmallerThanOrEqual($this->getDelegate(), $phpVersion);
	}

	public function traverse(callable $cb): Type
	{
		if ($this->initialType === null) {
			return $this;
		}

		$newInitialType = $cb($this->initialType);
		if ($newInitialType === $this->initialType) {
			return $this;
		}

		return $this->withInitialType($newInitialType);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if ($this->initialType === null) {
			return $this;
		}

		$newInitialType = $cb($this->initialType, $right);
		if ($newInitialType === $this->initialType) {
			return $this;
		}

		return $this->withInitialType($newInitialType);
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		if ($this->initialType === null) {
			return $this;
		}

		return $this->withInitialType($this->initialType->generalize($precision));
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->getDelegate()->tryRemove($typeToRemove);
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		return $this->getDelegate()->toCoercedArgumentType($strictTypes);
	}

	public function hasTemplateOrLateResolvableType(): bool
	{
		return $this->getDelegate()->hasTemplateOrLateResolvableType();
	}

	public function toPhpDocNode(): TypeNode
	{
		return $this->getDelegate()->toPhpDocNode();
	}

	public function getReferencedClasses(): array
	{
		return $this->getDelegate()->getReferencedClasses();
	}

	public function getObjectClassNames(): array
	{
		return $this->getDelegate()->getObjectClassNames();
	}

	public function getObjectClassReflections(): array
	{
		return $this->getDelegate()->getObjectClassReflections();
	}

	public function getClassStringType(): Type
	{
		return $this->getDelegate()->getClassStringType();
	}

	public function getClassStringObjectType(): Type
	{
		return $this->getDelegate()->getClassStringObjectType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this->getDelegate()->getObjectTypeOrClassStringObjectType();
	}

	public function isObject(): TrinaryLogic
	{
		return $this->getDelegate()->isObject();
	}

	public function isEnum(): TrinaryLogic
	{
		return $this->getDelegate()->isEnum();
	}

	public function getArrays(): array
	{
		return $this->getDelegate()->getArrays();
	}

	public function getConstantArrays(): array
	{
		return $this->getDelegate()->getConstantArrays();
	}

	public function getConstantStrings(): array
	{
		return $this->getDelegate()->getConstantStrings();
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->getDelegate()->canAccessProperties();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->getDelegate()->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->getDelegate()->getProperty($propertyName, $scope);
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->getDelegate()->getUnresolvedPropertyPrototype($propertyName, $scope);
	}

	public function hasInstanceProperty(string $propertyName): TrinaryLogic
	{
		return $this->getDelegate()->hasInstanceProperty($propertyName);
	}

	public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->getDelegate()->getInstanceProperty($propertyName, $scope);
	}

	public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->getDelegate()->getUnresolvedInstancePropertyPrototype($propertyName, $scope);
	}

	public function hasStaticProperty(string $propertyName): TrinaryLogic
	{
		return $this->getDelegate()->hasStaticProperty($propertyName);
	}

	public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->getDelegate()->getStaticProperty($propertyName, $scope);
	}

	public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->getDelegate()->getUnresolvedStaticPropertyPrototype($propertyName, $scope);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->getDelegate()->canCallMethods();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->getDelegate()->hasMethod($methodName);
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
	{
		return $this->getDelegate()->getMethod($methodName, $scope);
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		return $this->getDelegate()->getUnresolvedMethodPrototype($methodName, $scope);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->getDelegate()->canAccessConstants();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->getDelegate()->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ClassConstantReflection
	{
		return $this->getDelegate()->getConstant($constantName);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->getDelegate()->isIterable();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->getDelegate()->isIterableAtLeastOnce();
	}

	public function getArraySize(): Type
	{
		return $this->getDelegate()->getArraySize();
	}

	public function getIterableKeyType(): Type
	{
		return $this->getDelegate()->getIterableKeyType();
	}

	public function getFirstIterableKeyType(): Type
	{
		return $this->getDelegate()->getFirstIterableKeyType();
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->getDelegate()->getLastIterableKeyType();
	}

	public function getIterableValueType(): Type
	{
		return $this->getDelegate()->getIterableValueType();
	}

	public function getFirstIterableValueType(): Type
	{
		return $this->getDelegate()->getFirstIterableValueType();
	}

	public function getLastIterableValueType(): Type
	{
		return $this->getDelegate()->getLastIterableValueType();
	}

	public function isArray(): TrinaryLogic
	{
		return $this->getDelegate()->isArray();
	}

	public function isConstantArray(): TrinaryLogic
	{
		return $this->getDelegate()->isConstantArray();
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return $this->getDelegate()->isOversizedArray();
	}

	public function isList(): TrinaryLogic
	{
		return $this->getDelegate()->isList();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->getDelegate()->isOffsetAccessible();
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return $this->getDelegate()->isOffsetAccessLegal();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->getDelegate()->hasOffsetValueType($offsetType);
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->getDelegate()->getOffsetValueType($offsetType);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->getDelegate()->setOffsetValueType($offsetType, $valueType, $unionValues);
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return $this->getDelegate()->setExistingOffsetValueType($offsetType, $valueType);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->getDelegate()->unsetOffset($offsetType);
	}

	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type
	{
		return $this->getDelegate()->getKeysArrayFiltered($filterValueType, $strict);
	}

	public function getKeysArray(): Type
	{
		return $this->getDelegate()->getKeysArray();
	}

	public function getValuesArray(): Type
	{
		return $this->getDelegate()->getValuesArray();
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this->getDelegate()->chunkArray($lengthType, $preserveKeys);
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return $this->getDelegate()->fillKeysArray($valueType);
	}

	public function flipArray(): Type
	{
		return $this->getDelegate()->flipArray();
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		return $this->getDelegate()->intersectKeyArray($otherArraysType);
	}

	public function popArray(): Type
	{
		return $this->getDelegate()->popArray();
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return $this->getDelegate()->reverseArray($preserveKeys);
	}

	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type
	{
		return $this->getDelegate()->searchArray($needleType, $strict);
	}

	public function shiftArray(): Type
	{
		return $this->getDelegate()->shiftArray();
	}

	public function shuffleArray(): Type
	{
		return $this->getDelegate()->shuffleArray();
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this->getDelegate()->sliceArray($offsetType, $lengthType, $preserveKeys);
	}

	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type
	{
		return $this->getDelegate()->spliceArray($offsetType, $lengthType, $replacementType);
	}

	public function truncateListToSize(Type $sizeType): Type
	{
		return $this->getDelegate()->truncateListToSize($sizeType);
	}

	public function makeListMaybe(): Type
	{
		return $this->getDelegate()->makeListMaybe();
	}

	public function mapValueType(callable $cb): Type
	{
		return $this->getDelegate()->mapValueType($cb);
	}

	public function mapKeyType(callable $cb): Type
	{
		return $this->getDelegate()->mapKeyType($cb);
	}

	public function makeAllArrayKeysOptional(): Type
	{
		return $this->getDelegate()->makeAllArrayKeysOptional();
	}

	public function changeKeyCaseArray(?int $case): Type
	{
		return $this->getDelegate()->changeKeyCaseArray($case);
	}

	public function filterArrayRemovingFalsey(): Type
	{
		return $this->getDelegate()->filterArrayRemovingFalsey();
	}

	public function getEnumCases(): array
	{
		return $this->getDelegate()->getEnumCases();
	}

	public function getEnumCaseObject(): ?EnumCaseObjectType
	{
		return $this->getDelegate()->getEnumCaseObject();
	}

	public function getFiniteTypes(): array
	{
		return $this->getDelegate()->getFiniteTypes();
	}

	public function exponentiate(Type $exponent): Type
	{
		return $this->getDelegate()->exponentiate($exponent);
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->getDelegate()->isCallable();
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return $this->getDelegate()->getCallableParametersAcceptors($scope);
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->getDelegate()->isCloneable();
	}

	public function toBoolean(): BooleanType
	{
		return $this->getDelegate()->toBoolean();
	}

	public function toNumber(): Type
	{
		return $this->getDelegate()->toNumber();
	}

	public function toBitwiseNotType(): Type
	{
		return $this->getDelegate()->toBitwiseNotType();
	}

	public function toGetClassResultType(): Type
	{
		return $this->getDelegate()->toGetClassResultType();
	}

	public function toClassConstantType(ReflectionProvider $reflectionProvider): Type
	{
		return $this->getDelegate()->toClassConstantType($reflectionProvider);
	}

	public function toObjectTypeForInstanceofCheck(): ClassNameToObjectTypeResult
	{
		return $this->getDelegate()->toObjectTypeForInstanceofCheck();
	}

	public function toObjectTypeForIsACheck(Type $objectOrClassType, bool $allowString, bool $allowSameClass): ClassNameToObjectTypeResult
	{
		return $this->getDelegate()->toObjectTypeForIsACheck($objectOrClassType, $allowString, $allowSameClass);
	}

	public function toInteger(): Type
	{
		return $this->getDelegate()->toInteger();
	}

	public function toFloat(): Type
	{
		return $this->getDelegate()->toFloat();
	}

	public function toString(): Type
	{
		return $this->getDelegate()->toString();
	}

	public function toArray(): Type
	{
		return $this->getDelegate()->toArray();
	}

	public function toArrayKey(): Type
	{
		return $this->getDelegate()->toArrayKey();
	}

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->getDelegate()->isSmallerThan($otherType, $phpVersion);
	}

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->getDelegate()->isSmallerThanOrEqual($otherType, $phpVersion);
	}

	public function isConstantValue(): TrinaryLogic
	{
		return $this->getDelegate()->isConstantValue();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return $this->getDelegate()->isConstantScalarValue();
	}

	public function getConstantScalarTypes(): array
	{
		return $this->getDelegate()->getConstantScalarTypes();
	}

	public function getConstantScalarValues(): array
	{
		return $this->getDelegate()->getConstantScalarValues();
	}

	public function isNull(): TrinaryLogic
	{
		return $this->getDelegate()->isNull();
	}

	public function isTrue(): TrinaryLogic
	{
		return $this->getDelegate()->isTrue();
	}

	public function isFalse(): TrinaryLogic
	{
		return $this->getDelegate()->isFalse();
	}

	public function isBoolean(): TrinaryLogic
	{
		return $this->getDelegate()->isBoolean();
	}

	public function isFloat(): TrinaryLogic
	{
		return $this->getDelegate()->isFloat();
	}

	public function isInteger(): TrinaryLogic
	{
		return $this->getDelegate()->isInteger();
	}

	public function isString(): TrinaryLogic
	{
		return $this->getDelegate()->isString();
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->getDelegate()->isNumericString();
	}

	public function isDecimalIntegerString(): TrinaryLogic
	{
		return $this->getDelegate()->isDecimalIntegerString();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->getDelegate()->isNonEmptyString();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return $this->getDelegate()->isNonFalsyString();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->getDelegate()->isLiteralString();
	}

	public function isLowercaseString(): TrinaryLogic
	{
		return $this->getDelegate()->isLowercaseString();
	}

	public function isUppercaseString(): TrinaryLogic
	{
		return $this->getDelegate()->isUppercaseString();
	}

	public function isClassString(): TrinaryLogic
	{
		return $this->getDelegate()->isClassString();
	}

	public function isVoid(): TrinaryLogic
	{
		return $this->getDelegate()->isVoid();
	}

	public function isScalar(): TrinaryLogic
	{
		return $this->getDelegate()->isScalar();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return $this->getDelegate()->looseCompare($type, $phpVersion);
	}

	public function getSmallerType(PhpVersion $phpVersion): Type
	{
		return $this->getDelegate()->getSmallerType($phpVersion);
	}

	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
	{
		return $this->getDelegate()->getSmallerOrEqualType($phpVersion);
	}

	public function getGreaterType(PhpVersion $phpVersion): Type
	{
		return $this->getDelegate()->getGreaterType($phpVersion);
	}

	public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
	{
		return $this->getDelegate()->getGreaterOrEqualType($phpVersion);
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return $this->getDelegate()->getTemplateType($ancestorClassName, $templateTypeName);
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		return $this->getDelegate()->inferTemplateTypes($receivedType);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return $this->getDelegate()->getReferencedTemplateTypes($positionVariance);
	}

	public function toAbsoluteNumber(): Type
	{
		return $this->getDelegate()->toAbsoluteNumber();
	}

}
