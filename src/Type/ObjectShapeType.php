<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Broker\Broker;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
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
	 * @param array<string, Type> $properties
	 * @param list<string> $optionalProperties
	 */
	public function __construct(private array $properties, private array $optionalProperties)
	{
	}

	/**
	 * @return array<string, Type>
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return list<string>
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
		if (!array_key_exists($propertyName, $this->properties)) {
			return TrinaryLogic::createNo();
		}

		if (in_array($propertyName, $this->optionalProperties, true)) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createYes();
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		if (!array_key_exists($propertyName, $this->properties)) {
			throw new ShouldNotHappenException();
		}

		$property = new ObjectShapePropertyReflection($this->properties[$propertyName]);
		return new CallbackUnresolvedPropertyPrototypeReflection(
			$property,
			$property->getDeclaringClass(),
			false,
			static fn (Type $type): Type => $type,
		);
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

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		foreach ($type->getObjectClassReflections() as $classReflection) {
			if (!UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
				$reflectionProvider,
				Broker::getInstance()->getUniversalObjectCratesClasses(),
				$classReflection,
			)) {
				continue;
			}

			return AcceptsResult::createMaybe();
		}

		$result = AcceptsResult::createYes();
		$scope = new OutOfClassScope();
		foreach ($this->properties as $propertyName => $propertyType) {
			$typeHasProperty = $type->hasProperty($propertyName);
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
			if ($hasProperty->no()) {
				if (in_array($propertyName, $this->optionalProperties, true)) {
					continue;
				}
				return $hasProperty;
			}
			if ($hasProperty->maybe() && in_array($propertyName, $this->optionalProperties, true)) {
				$hasProperty = AcceptsResult::createYes();
			}

			$result = $result->and($hasProperty);

			try {
				$otherProperty = $type->getProperty($propertyName, $scope);
			} catch (MissingPropertyFromReflectionException) {
				return new AcceptsResult(
					$result->result,
					[
						sprintf(
							'%s %s not have property $%s.',
							$type->describe(VerbosityLevel::typeOnly()),
							$result->no() ? 'does' : 'might',
							$propertyName,
						),
					],
				);
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
			$acceptsValue = $propertyType->acceptsWithReason($otherPropertyType, $strictTypes)->decorateReasons(
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

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($type instanceof ObjectWithoutClassType) {
			return TrinaryLogic::createMaybe();
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		foreach ($type->getObjectClassReflections() as $classReflection) {
			if (!UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
				$reflectionProvider,
				Broker::getInstance()->getUniversalObjectCratesClasses(),
				$classReflection,
			)) {
				continue;
			}

			return TrinaryLogic::createMaybe();
		}

		$result = TrinaryLogic::createYes();
		$scope = new OutOfClassScope();
		foreach ($this->properties as $propertyName => $propertyType) {
			$hasProperty = $type->hasProperty($propertyName);
			if ($hasProperty->no()) {
				if (in_array($propertyName, $this->optionalProperties, true)) {
					continue;
				}
				return $hasProperty;
			}
			if ($hasProperty->maybe() && in_array($propertyName, $this->optionalProperties, true)) {
				$hasProperty = TrinaryLogic::createYes();
			}

			$result = $result->and($hasProperty);

			try {
				$otherProperty = $type->getProperty($propertyName, $scope);
			} catch (MissingPropertyFromReflectionException) {
				return $result;
			}

			if (!$otherProperty->isPublic()) {
				return TrinaryLogic::createNo();
			}

			if ($otherProperty->isStatic()) {
				return TrinaryLogic::createNo();
			}

			if (!$otherProperty->isReadable()) {
				return TrinaryLogic::createNo();
			}

			$otherPropertyType = $otherProperty->getReadableType();
			$isSuperType = $propertyType->isSuperTypeOf($otherPropertyType);
			if ($isSuperType->no()) {
				return $isSuperType;
			}
			$result = $result->and($isSuperType);
		}

		return $result->and($type->isObject());
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
			$optionalProperties = array_values(array_filter($this->optionalProperties, static fn (string $propertyName) => $propertyName !== $typeToRemove->getPropertyName()));

			return new self($properties, $optionalProperties);
		}

		return null;
	}

	public function makePropertyRequired(string $propertyName): self
	{
		if (array_key_exists($propertyName, $this->properties)) {
			$optionalProperties = array_values(array_filter($this->optionalProperties, static fn (string $currentPropertyName) => $currentPropertyName !== $propertyName));

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
				if ($receivedType->hasProperty($name)->no()) {
					continue;
				}

				try {
					$receivedProperty = $receivedType->getProperty($name, $scope);
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
			if (!$right->hasProperty($name)->yes()) {
				return $this;
			}
			$transformed = $cb($propertyType, $right->getProperty($name, $scope)->getReadableType());
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

	public function traverseWithVariance(TemplateTypeVariance $variance, callable $cb): Type
	{
		$composedVariance = $variance->compose(TemplateTypeVariance::createCovariant());

		$properties = [];
		$stillOriginal = true;

		foreach ($this->properties as $name => $propertyType) {
			$transformed = $cb($propertyType, $composedVariance);
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
		if (!$exponent instanceof NeverType && !$this->isSuperTypeOf($exponent)->no()) {
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
			if (ConstantArrayType::isValidIdentifier($name)) {
				$keyNode = new IdentifierTypeNode($name);
			} else {
				$keyPhpDocNode = (new ConstantStringType($name))->toPhpDocNode();
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

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['properties'], $properties['optionalProperties']);
	}

}
