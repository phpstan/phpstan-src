<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\ClosureCallUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function array_map;
use function array_merge;
use function implode;
use function sprintf;

/** @api */
class ClosureType implements TypeWithClassName, ParametersAcceptor
{

	use NonGenericTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonOffsetAccessibleTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	private ObjectType $objectType;

	private TemplateTypeMap $templateTypeMap;

	private TemplateTypeMap $resolvedTemplateTypeMap;

	/**
	 * @api
	 * @param array<int, ParameterReflection> $parameters
	 */
	public function __construct(
		private array $parameters,
		private Type $returnType,
		private bool $variadic,
		?TemplateTypeMap $templateTypeMap = null,
		?TemplateTypeMap $resolvedTemplateTypeMap = null,
	)
	{
		$this->objectType = new ObjectType(Closure::class);
		$this->templateTypeMap = $templateTypeMap ?? TemplateTypeMap::createEmpty();
		$this->resolvedTemplateTypeMap = $resolvedTemplateTypeMap ?? TemplateTypeMap::createEmpty();
	}

	public function getClassName(): string
	{
		return $this->objectType->getClassName();
	}

	public function getClassReflection(): ?ClassReflection
	{
		return $this->objectType->getClassReflection();
	}

	public function getAncestorWithClassName(string $className): ?TypeWithClassName
	{
		return $this->objectType->getAncestorWithClassName($className);
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		$classes = $this->objectType->getReferencedClasses();
		foreach ($this->parameters as $parameter) {
			$classes = array_merge($classes, $parameter->getType()->getReferencedClasses());
		}

		return array_merge($classes, $this->returnType->getReferencedClasses());
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if (!$type instanceof ClosureType) {
			return $this->objectType->accepts($type, $strictTypes);
		}

		return $this->isSuperTypeOfInternal($type, true);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return $this->isSuperTypeOfInternal($type, false);
	}

	private function isSuperTypeOfInternal(Type $type, bool $treatMixedAsAny): TrinaryLogic
	{
		if ($type instanceof self) {
			return CallableTypeHelper::isParametersAcceptorSuperTypeOf(
				$this,
				$type,
				$treatMixedAsAny,
			);
		}

		if (
			$type instanceof TypeWithClassName
			&& $type->getClassName() === Closure::class
		) {
			return TrinaryLogic::createMaybe();
		}

		return $this->objectType->isSuperTypeOf($type);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		return $this->returnType->equals($type->returnType);
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'Closure',
			fn (): string => sprintf(
				'Closure(%s): %s',
				implode(', ', array_map(
					static fn (ParameterReflection $param): string => sprintf(
						'%s%s%s',
						$param->isVariadic() ? '...' : '',
						$param->getType()->describe($level),
						$param->isOptional() && !$param->isVariadic() ? '=' : '',
					),
					$this->parameters,
				)),
				$this->returnType->describe($level),
			),
		);
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->objectType->canAccessProperties();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->objectType->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->objectType->getProperty($propertyName, $scope);
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->objectType->getUnresolvedPropertyPrototype($propertyName, $scope);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->objectType->canCallMethods();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->objectType->hasMethod($methodName);
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		if ($methodName === 'call') {
			return new ClosureCallUnresolvedMethodPrototypeReflection(
				$this->objectType->getUnresolvedMethodPrototype($methodName, $scope),
				$this,
			);
		}

		return $this->objectType->getUnresolvedMethodPrototype($methodName, $scope);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->objectType->canAccessConstants();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->objectType->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->objectType->getConstant($constantName);
	}

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getIterableKeyType(): Type
	{
		return new ErrorType();
	}

	public function getIterableValueType(): Type
	{
		return new ErrorType();
	}

	public function isCallable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return [$this];
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function toBoolean(): BooleanType
	{
		return new ConstantBooleanType(true);
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new ErrorType();
	}

	public function toFloat(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
	{
		return new ErrorType();
	}

	public function toArray(): Type
	{
		return new ConstantArrayType(
			[new ConstantIntegerType(0)],
			[$this],
			[1],
		);
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->templateTypeMap;
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return $this->resolvedTemplateTypeMap;
	}

	/**
	 * @return array<int, ParameterReflection>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function isVariadic(): bool
	{
		return $this->variadic;
	}

	public function getReturnType(): Type
	{
		return $this->returnType;
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if ($receivedType->isCallable()->no() || ! $receivedType instanceof self) {
			return TemplateTypeMap::createEmpty();
		}

		$parametersAcceptors = $receivedType->getCallableParametersAcceptors(new OutOfClassScope());

		$typeMap = TemplateTypeMap::createEmpty();

		foreach ($parametersAcceptors as $parametersAcceptor) {
			$typeMap = $typeMap->union($this->inferTemplateTypesOnParametersAcceptor($parametersAcceptor));
		}

		return $typeMap;
	}

	private function inferTemplateTypesOnParametersAcceptor(ParametersAcceptor $parametersAcceptor): TemplateTypeMap
	{
		$typeMap = TemplateTypeMap::createEmpty();
		$args = $parametersAcceptor->getParameters();
		$returnType = $parametersAcceptor->getReturnType();

		foreach ($this->getParameters() as $i => $param) {
			$paramType = $param->getType();
			if (isset($args[$i])) {
				$argType = $args[$i]->getType();
			} elseif ($paramType instanceof TemplateType) {
				$argType = TemplateTypeHelper::resolveToBounds($paramType);
			} else {
				$argType = new NeverType();
			}

			$typeMap = $typeMap->union($paramType->inferTemplateTypes($argType)->convertToLowerBoundTypes());
		}

		return $typeMap->union($this->getReturnType()->inferTemplateTypes($returnType));
	}

	public function traverse(callable $cb): Type
	{
		return new self(
			array_map(static function (ParameterReflection $param) use ($cb): NativeParameterReflection {
				$defaultValue = $param->getDefaultValue();
				return new NativeParameterReflection(
					$param->getName(),
					$param->isOptional(),
					$cb($param->getType()),
					$param->passedByReference(),
					$param->isVariadic(),
					$defaultValue !== null ? $cb($defaultValue) : null,
				);
			}, $this->getParameters()),
			$cb($this->getReturnType()),
			$this->isVariadic(),
			$this->templateTypeMap,
			$this->resolvedTemplateTypeMap,
		);
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isList(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['parameters'],
			$properties['returnType'],
			$properties['variadic'],
			$properties['templateTypeMap'],
			$properties['resolvedTemplateTypeMap'],
		);
	}

}
