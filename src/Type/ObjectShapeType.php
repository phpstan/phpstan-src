<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\ObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function array_filter;
use function array_key_exists;
use function array_values;
use function count;
use function implode;
use function in_array;
use function sprintf;

/** @api */
class ObjectShapeType implements Type
{

	use ObjectTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonGeneralizableTypeTrait;

	/**
	 * @api
	 * @param array<int|string, Type> $properties
	 * @param list<int|string> $optionalProperties
	 */
	public function __construct(private array $properties, private array $optionalProperties)
	{
	}

	/**
	 * @return array<int|string, Type>
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return list<int|string>
	 */
	public function getOptionalProperties(): array
	{
		return $this->optionalProperties;
	}

	public function getReferencedClasses(): array
	{
		$classes = [];
		foreach ($this->properties as $propertyType) {
			foreach ($propertyType->getReferencedClasses() as $referencedClass) {
				$classes[] = $referencedClass;
			}
		}

		return $classes;
	}

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->hasInstanceProperty($propertyName);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->getInstanceProperty($propertyName, $scope);
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->getUnresolvedInstancePropertyPrototype($propertyName, $scope);
	}

	public function hasInstanceProperty(string $propertyName): TrinaryLogic
	{
		if (!array_key_exists($propertyName, $this->properties)) {
			return TrinaryLogic::createNo();
		}

		if (in_array($propertyName, $this->optionalProperties, true)) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createYes();
	}

	public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->getUnresolvedInstancePropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		if (!array_key_exists($propertyName, $this->properties)) {
			throw new ShouldNotHappenException();
		}

		$property = new ObjectShapePropertyReflection($propertyName, $this->properties[$propertyName]);
		return new CallbackUnresolvedPropertyPrototypeReflection(
			$property,
			$property->getDeclaringClass(),
			false,
			static fn (Type $type): Type => $type,
		);
	}

	public function hasStaticProperty(string $propertyName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		throw new ShouldNotHappenException();
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		foreach ($type->getObjectClassReflections() as $classReflection) {
			if (!UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
				$reflectionProvider,
				$classReflection,
			)) {
				continue;
			}

			return AcceptsResult::createMaybe();
		}

		$result = AcceptsResult::createYes();
		$scope = new OutOfClassScope();
		foreach ($this->properties as $propertyName => $propertyType) {
			$typeHasProperty = $type->hasInstanceProperty((string) $propertyName);
			$hasProperty = new AcceptsResult(
				$typeHasProperty,
				$typeHasProperty->yes() ? [] : [
					sprintf(
						'%s %s have property $%s.',
						$type->describe(VerbosityLevel::typeOnly()),
						$typeHasProperty->no() ? 'does not' : 'might not',
						$propertyName,
					),
				],
			);
			if (!$hasProperty->yes() && $type->hasStaticProperty((string) $propertyName)->yes()) {
				$result = $result->and(new AcceptsResult(TrinaryLogic::createNo(), [
					sprintf('Property %s::$%s is static.', $type->getStaticProperty((string) $propertyName, $scope)->getDeclaringClass()->getDisplayName(), $propertyName),
				]));
				continue;
			}
			if ($hasProperty->no()) {
				if (in_array($propertyName, $this->optionalProperties, true)) {
					continue;
				}
				$result = $result->and($hasProperty);
				continue;
			}
			if ($hasProperty->maybe()) {
				if (!in_array($propertyName, $this->optionalProperties, true)) {
					$result = $result->and($hasProperty);
					continue;

				}

				$hasProperty = AcceptsResult::createYes();
			}

			$result = $result->and($hasProperty);
			try {
				$otherProperty = $type->getInstanceProperty((string) $propertyName, $scope);
			} catch (MissingPropertyFromReflectionException) {
				continue;
			}

			if (!$otherProperty->isPublic()) {
				return new AcceptsResult(TrinaryLogic::createNo(), [
					sprintf('Property %s::$%s is not public.', $otherProperty->getDeclaringClass()->getDisplayName(), $propertyName),
				]);
			}

			if ($otherProperty->isStatic()) {
				return new AcceptsResult(TrinaryLogic::createNo(), [
					sprintf('Property %s::$%s is static.', $otherProperty->getDeclaringClass()->getDisplayName(), $propertyName),
				]);
			}

			if (!$otherProperty->isReadable()) {
				return new AcceptsResult(TrinaryLogic::createNo(), [
					sprintf('Property %s::$%s is not readable.', $otherProperty->getDeclaringClass()->getDisplayName(), $propertyName),
				]);
			}

			$otherPropertyType = $otherProperty->getReadableType();
			$verbosity = VerbosityLevel::getRecommendedLevelByType($propertyType, $otherPropertyType);
			$acceptsValue = $propertyType->accepts($otherPropertyType, $strictTypes)->decorateReasons(
				static fn (string $reason) => sprintf(
					'Property ($%s) type %s does not accept type %s: %s',
					$propertyName,
					$propertyType->describe($verbosity),
					$otherPropertyType->describe($verbosity),
					$reason,
				),
			);
			if (!$acceptsValue->yes() && count($acceptsValue->reasons) === 0) {
				$acceptsValue = new AcceptsResult($acceptsValue->result, [
					sprintf(
						'Property ($%s) type %s does not accept type %s.',
						$propertyName,
						$propertyType->describe($verbosity),
						$otherPropertyType->describe($verbosity),
					),
				]);
			}
			if ($acceptsValue->no()) {
				return $acceptsValue;
			}
			$result = $result->and($acceptsValue);
		}

		return $result->and(new AcceptsResult($type->isObject(), []));
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($type instanceof ObjectWithoutClassType) {
			return IsSuperTypeOfResult::createMaybe();
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		foreach ($type->getObjectClassReflections() as $classReflection) {
			if (!UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
				$reflectionProvider,
				$classReflection,
			)) {
				continue;
			}

			return IsSuperTypeOfResult::createMaybe();
		}

		$result = IsSuperTypeOfResult::createYes();
		$scope = new OutOfClassScope();
		foreach ($this->properties as $propertyName => $propertyType) {
			$hasProperty = new IsSuperTypeOfResult($type->hasInstanceProperty((string) $propertyName), []);
			if ($hasProperty->no()) {
				if (in_array($propertyName, $this->optionalProperties, true)) {
					continue;
				}
				$result = $result->and($hasProperty);
				continue;
			}
			if ($hasProperty->maybe()) {
				if (!in_array($propertyName, $this->optionalProperties, true)) {
					$result = $result->and($hasProperty);
					continue;
				}

				$hasProperty = IsSuperTypeOfResult::createYes();
			}

			$result = $result->and($hasProperty);
			try {
				$otherProperty = $type->getInstanceProperty((string) $propertyName, $scope);
			} catch (MissingPropertyFromReflectionException) {
				continue;
			}

			if (!$otherProperty->isPublic()) {
				return IsSuperTypeOfResult::createNo();
			}

			if ($otherProperty->isStatic()) {
				return IsSuperTypeOfResult::createNo();
			}

			if (!$otherProperty->isReadable()) {
				return IsSuperTypeOfResult::createNo();
			}

			$otherPropertyType = $otherProperty->getReadableType();
			$isSuperType = $propertyType->isSuperTypeOf($otherPropertyType);
			if ($isSuperType->no()) {
				return $isSuperType;
			}
			$result = $result->and($isSuperType);
		}

		return $result->and(new IsSuperTypeOfResult($type->isObject(), []));
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if (count($this->properties) !== count($type->properties)) {
			return false;
		}

		foreach ($this->properties as $name => $propertyType) {
			if (!array_key_exists($name, $type->properties)) {
				return false;
			}

			if (!$propertyType->equals($type->properties[$name])) {
				return false;
			}
		}

		if (count($this->optionalProperties) !== count($type->optionalProperties)) {
			return false;
		}

		foreach ($this->optionalProperties as $name) {
			if (in_array($name, $type->optionalProperties, true)) {
				continue;
			}

			return false;
		}

		return true;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove instanceof HasPropertyType) {
			$properties = $this->properties;
			unset($properties[$typeToRemove->getPropertyName()]);
			$optionalProperties = array_values(array_filter($this->optionalProperties, static fn (int|string $propertyName) => $propertyName !== $typeToRemove->getPropertyName()));

			return new self($properties, $optionalProperties);
		}

		return null;
	}

	public function makePropertyRequired(string $propertyName): self
	{
		if (array_key_exists($propertyName, $this->properties)) {
			$optionalProperties = array_values(array_filter($this->optionalProperties, static fn (int|string $currentPropertyName) => $currentPropertyName !== $propertyName));

			return new self($this->properties, $optionalProperties);
		}

		return $this;
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if ($receivedType instanceof self) {
			$typeMap = TemplateTypeMap::createEmpty();
			$scope = new OutOfClassScope();
			foreach ($this->properties as $name => $propertyType) {
				if ($receivedType->hasInstanceProperty((string) $name)->no()) {
					continue;
				}

				try {
					$receivedProperty = $receivedType->getInstanceProperty((string) $name, $scope);
				} catch (MissingPropertyFromReflectionException) {
					continue;
				}
				if (!$receivedProperty->isPublic()) {
					continue;
				}
				if ($receivedProperty->isStatic()) {
					continue;
				}
				$receivedPropertyType = $receivedProperty->getReadableType();
				$typeMap = $typeMap->union($propertyType->inferTemplateTypes($receivedPropertyType));
			}

			return $typeMap;
		}

		return TemplateTypeMap::createEmpty();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());
		$references = [];
		foreach ($this->properties as $propertyType) {
			foreach ($propertyType->getReferencedTemplateTypes($variance) as $reference) {
				$references[] = $reference;
			}
		}

		return $references;
	}

	public function describe(VerbosityLevel $level): string
	{
		$callback = function () use ($level): string {
			$items = [];
			foreach ($this->properties as $name => $propertyType) {
				$optional = in_array($name, $this->optionalProperties, true);
				$items[] = sprintf('%s%s: %s', $name, $optional ? '?' : '', $propertyType->describe($level));
			}
			return sprintf('object{%s}', implode(', ', $items));
		};
		return $level->handle(
			$callback,
			$callback,
		);
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function traverse(callable $cb): Type
	{
		$properties = [];
		$stillOriginal = true;

		foreach ($this->properties as $name => $propertyType) {
			$transformed = $cb($propertyType);
			if ($transformed !== $propertyType) {
				$stillOriginal = false;
			}

			$properties[$name] = $transformed;
		}

		if ($stillOriginal) {
			return $this;
		}

		return new self($properties, $this->optionalProperties);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right->isObject()->yes()) {
			return $this;
		}

		$properties = [];
		$stillOriginal = true;

		$scope = new OutOfClassScope();
		foreach ($this->properties as $name => $propertyType) {
			if (!$right->hasInstanceProperty((string) $name)->yes()) {
				return $this;
			}
			$transformed = $cb($propertyType, $right->getInstanceProperty((string) $name, $scope)->getReadableType());
			if ($transformed !== $propertyType) {
				$stillOriginal = false;
			}

			$properties[$name] = $transformed;
		}

		if ($stillOriginal) {
			return $this;
		}

		return new self($properties, $this->optionalProperties);
	}

	public function exponentiate(Type $exponent): Type
	{
		if ($exponent->isNever()->no() && !$this->isSuperTypeOf($exponent)->no()) {
			return TypeCombinator::union($this, $exponent);
		}

		return new BenevolentUnionType([
			new FloatType(),
			new IntegerType(),
		]);
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function toPhpDocNode(): TypeNode
	{
		$items = [];
		foreach ($this->properties as $name => $type) {
			if (ConstantArrayType::isValidIdentifier((string) $name)) {
				$keyNode = new IdentifierTypeNode((string) $name);
			} else {
				$keyPhpDocNode = (new ConstantStringType((string) $name))->toPhpDocNode();
				if (!$keyPhpDocNode instanceof ConstTypeNode) {
					continue;
				}

				/** @var ConstExprStringNode $keyNode */
				$keyNode = $keyPhpDocNode->constExpr;
			}
			$items[] = new ObjectShapeItemNode(
				$keyNode,
				in_array($name, $this->optionalProperties, true),
				$type->toPhpDocNode(),
			);
		}

		return new ObjectShapeNode($items);
	}

}
