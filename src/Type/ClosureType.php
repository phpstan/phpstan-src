<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\ClosureCallUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Php\DummyParameter;
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
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function array_map;
use function array_merge;
use function count;

/** @api */
class ClosureType implements TypeWithClassName, CallableParametersAcceptor
{

	use NonArrayTypeTrait;
	use NonIterableTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonOffsetAccessibleTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @var array<int, ParameterReflection> */
	private array $parameters;

	private Type $returnType;

	private bool $isCommonCallable;

	private ObjectType $objectType;

	private TemplateTypeMap $templateTypeMap;

	private TemplateTypeMap $resolvedTemplateTypeMap;

	private TemplateTypeVarianceMap $callSiteVarianceMap;

	/** @var SimpleImpurePoint[] */
	private array $impurePoints;

	/**
	 * @api
	 * @param array<int, ParameterReflection>|null $parameters
	 * @param array<non-empty-string, TemplateTag> $templateTags
	 * @param SimpleThrowPoint[] $throwPoints
	 * @param ?SimpleImpurePoint[] $impurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 * @param string[] $usedVariables
	 */
	public function __construct(
		?array $parameters = null,
		?Type $returnType = null,
		private bool $variadic = true,
		?TemplateTypeMap $templateTypeMap = null,
		?TemplateTypeMap $resolvedTemplateTypeMap = null,
		?TemplateTypeVarianceMap $callSiteVarianceMap = null,
		private array $templateTags = [],
		private array $throwPoints = [],
		?array $impurePoints = null,
		private array $invalidateExpressions = [],
		private array $usedVariables = [],
		private bool $acceptsNamedArguments = true,
	)
	{
		$this->parameters = $parameters ?? [];
		$this->returnType = $returnType ?? new MixedType();
		$this->isCommonCallable = $parameters === null && $returnType === null;
		$this->objectType = new ObjectType(Closure::class);
		$this->templateTypeMap = $templateTypeMap ?? TemplateTypeMap::createEmpty();
		$this->resolvedTemplateTypeMap = $resolvedTemplateTypeMap ?? TemplateTypeMap::createEmpty();
		$this->callSiteVarianceMap = $callSiteVarianceMap ?? TemplateTypeVarianceMap::createEmpty();
		$this->impurePoints = $impurePoints ?? [new SimpleImpurePoint('functionCall', 'call to an unknown Closure', false)];
	}

	/**
	 * @return array<non-empty-string, TemplateTag>
	 */
	public function getTemplateTags(): array
	{
		return $this->templateTags;
	}

	public static function createPure(): self
	{
		return new self(null, null, true, null, null, null, [], [], []);
	}

	public function isPure(): TrinaryLogic
	{
		$impurePoints = $this->getImpurePoints();
		if (count($impurePoints) === 0) {
			return TrinaryLogic::createYes();
		}

		$certainCount = 0;
		foreach ($impurePoints as $impurePoint) {
			if (!$impurePoint->isCertain()) {
				continue;
			}

			$certainCount++;
		}

		return $certainCount > 0 ? TrinaryLogic::createNo() : TrinaryLogic::createMaybe();
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

	public function getObjectClassNames(): array
	{
		return $this->objectType->getObjectClassNames();
	}

	public function getObjectClassReflections(): array
	{
		return $this->objectType->getObjectClassReflections();
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

		if (!$type instanceof ClosureType) {
			return $this->objectType->acceptsWithReason($type, $strictTypes);
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

	private function isSuperTypeOfInternal(Type $type, bool $treatMixedAsAny): AcceptsResult
	{
		if ($type instanceof self) {
			return CallableTypeHelper::isParametersAcceptorSuperTypeOf(
				$this,
				$type,
				$treatMixedAsAny,
			);
		}

		if ($type->getObjectClassNames() === [Closure::class]) {
			return AcceptsResult::createMaybe();
		}

		return new AcceptsResult($this->objectType->isSuperTypeOf($type), []);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		return $this->describe(VerbosityLevel::precise()) === $type->describe(VerbosityLevel::precise());
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'Closure',
			function (): string {
				if ($this->isCommonCallable) {
					return $this->isPure()->yes() ? 'pure-Closure' : 'Closure';
				}

				$printer = new Printer();
				$selfWithoutParameterNames = new self(
					array_map(static fn (ParameterReflection $p): ParameterReflection => new DummyParameter(
						'',
						$p->getType(),
						$p->isOptional() && !$p->isVariadic(),
						PassedByReference::createNo(),
						$p->isVariadic(),
						$p->getDefaultValue(),
					), $this->parameters),
					$this->returnType,
					$this->variadic,
					$this->templateTypeMap,
					$this->resolvedTemplateTypeMap,
					$this->callSiteVarianceMap,
					$this->templateTags,
					$this->throwPoints,
					$this->impurePoints,
					$this->invalidateExpressions,
					$this->usedVariables,
				);

				return $printer->print($selfWithoutParameterNames->toPhpDocNode());
			},
		);
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isObject(): TrinaryLogic
	{
		return $this->objectType->isObject();
	}

	public function isEnum(): TrinaryLogic
	{
		return $this->objectType->isEnum();
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return $this->objectType->getTemplateType($ancestorClassName, $templateTypeName);
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

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
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

	public function getConstantStrings(): array
	{
		return [];
	}

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isCallable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function isCommonCallable(): bool
	{
		return $this->isCommonCallable;
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return [$this];
	}

	public function getThrowPoints(): array
	{
		return $this->throwPoints;
	}

	public function getImpurePoints(): array
	{
		return $this->impurePoints;
	}

	public function getInvalidateExpressions(): array
	{
		return $this->invalidateExpressions;
	}

	public function getUsedVariables(): array
	{
		return $this->usedVariables;
	}

	public function acceptsNamedArguments(): bool
	{
		return $this->acceptsNamedArguments;
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

	public function toAbsoluteNumber(): Type
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
			[],
			TrinaryLogic::createYes(),
		);
	}

	public function toArrayKey(): Type
	{
		return new ErrorType();
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->templateTypeMap;
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return $this->resolvedTemplateTypeMap;
	}

	public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
	{
		return $this->callSiteVarianceMap;
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

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$references = $this->getReturnType()->getReferencedTemplateTypes(
			$positionVariance->compose(TemplateTypeVariance::createCovariant()),
		);

		$paramVariance = $positionVariance->compose(TemplateTypeVariance::createContravariant());

		foreach ($this->getParameters() as $param) {
			foreach ($param->getType()->getReferencedTemplateTypes($paramVariance) as $reference) {
				$references[] = $reference;
			}
		}

		return $references;
	}

	public function traverse(callable $cb): Type
	{
		if ($this->isCommonCallable) {
			return $this;
		}

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
			$this->callSiteVarianceMap,
			$this->templateTags,
			$this->throwPoints,
			$this->impurePoints,
			$this->invalidateExpressions,
			$this->usedVariables,
			$this->acceptsNamedArguments,
		);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if ($this->isCommonCallable) {
			return $this;
		}

		if (!$right instanceof self) {
			return $this;
		}

		$rightParameters = $right->getParameters();
		if (count($this->getParameters()) !== count($rightParameters)) {
			return $this;
		}

		$parameters = [];
		foreach ($this->getParameters() as $i => $leftParam) {
			$rightParam = $rightParameters[$i];
			$leftDefaultValue = $leftParam->getDefaultValue();
			$rightDefaultValue = $rightParam->getDefaultValue();
			$defaultValue = $leftDefaultValue;
			if ($leftDefaultValue !== null && $rightDefaultValue !== null) {
				$defaultValue = $cb($leftDefaultValue, $rightDefaultValue);
			}
			$parameters[] = new NativeParameterReflection(
				$leftParam->getName(),
				$leftParam->isOptional(),
				$cb($leftParam->getType(), $rightParam->getType()),
				$leftParam->passedByReference(),
				$leftParam->isVariadic(),
				$defaultValue,
			);
		}

		return new self(
			$parameters,
			$cb($this->getReturnType(), $right->getReturnType()),
			$this->isVariadic(),
			$this->templateTypeMap,
			$this->resolvedTemplateTypeMap,
			$this->callSiteVarianceMap,
			$this->templateTags,
			$this->throwPoints,
			$this->impurePoints,
			$this->invalidateExpressions,
			$this->usedVariables,
			$this->acceptsNamedArguments,
		);
	}

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getConstantScalarTypes(): array
	{
		return [];
	}

	public function getConstantScalarValues(): array
	{
		return [];
	}

	public function isTrue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFalse(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isBoolean(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFloat(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInteger(): TrinaryLogic
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

	public function isClassStringType(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getClassStringObjectType(): Type
	{
		return new ErrorType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this;
	}

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return new BooleanType();
	}

	public function exponentiate(Type $exponent): Type
	{
		return new ErrorType();
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function toPhpDocNode(): TypeNode
	{
		if ($this->isCommonCallable) {
			return new IdentifierTypeNode($this->isPure()->yes() ? 'pure-Closure' : 'Closure');
		}

		$parameters = [];
		foreach ($this->parameters as $parameter) {
			$parameters[] = new CallableTypeParameterNode(
				$parameter->getType()->toPhpDocNode(),
				!$parameter->passedByReference()->no(),
				$parameter->isVariadic(),
				$parameter->getName() === '' ? '' : '$' . $parameter->getName(),
				$parameter->isOptional(),
			);
		}

		$templateTags = [];
		foreach ($this->templateTags as $templateName => $templateTag) {
			$templateTags[] = new TemplateTagValueNode(
				$templateName,
				$templateTag->getBound()->toPhpDocNode(),
				'',
			);
		}

		return new CallableTypeNode(
			new IdentifierTypeNode('Closure'),
			$parameters,
			$this->returnType->toPhpDocNode(),
			$templateTags,
		);
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
			$properties['callSiteVarianceMap'],
			$properties['templateTags'],
			$properties['throwPoints'],
			$properties['impurePoints'],
			$properties['invalidateExpressions'],
			$properties['usedVariables'],
			$properties['acceptsNamedArguments'],
		);
	}

}
