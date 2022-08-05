<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\MaybeIterableTypeTrait;
use PHPStan\Type\Traits\MaybeObjectTypeTrait;
use PHPStan\Type\Traits\MaybeOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use function array_map;
use function array_merge;
use function implode;
use function sprintf;

/** @api */
class CallableType implements CompoundType, ParametersAcceptor
{

	use MaybeIterableTypeTrait;
	use MaybeObjectTypeTrait;
	use MaybeOffsetAccessibleTypeTrait;
	use TruthyBooleanTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @var array<int, ParameterReflection> */
	private array $parameters;

	private Type $returnType;

	private bool $isCommonCallable;

	/**
	 * @api
	 * @param array<int, ParameterReflection> $parameters
	 */
	public function __construct(
		?array $parameters = null,
		?Type $returnType = null,
		private bool $variadic = true,
	)
	{
		$this->parameters = $parameters ?? [];
		$this->returnType = $returnType ?? new MixedType();
		$this->isCommonCallable = $parameters === null && $returnType === null;
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		$classes = [];
		foreach ($this->parameters as $parameter) {
			$classes = array_merge($classes, $parameter->getType()->getReferencedClasses());
		}

		return array_merge($classes, $this->returnType->getReferencedClasses());
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType && !$type instanceof self) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return $this->isSuperTypeOfInternal($type, true);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType && !$type instanceof self) {
			return $type->isSubTypeOf($this);
		}

		return $this->isSuperTypeOfInternal($type, false);
	}

	private function isSuperTypeOfInternal(Type $type, bool $treatMixedAsAny): TrinaryLogic
	{
		$isCallable = $type->isCallable();
		if ($isCallable->no() || $this->isCommonCallable) {
			return $isCallable;
		}

		static $scope;
		if ($scope === null) {
			$scope = new OutOfClassScope();
		}

		$variantsResult = null;
		foreach ($type->getCallableParametersAcceptors($scope) as $variant) {
			$isSuperType = CallableTypeHelper::isParametersAcceptorSuperTypeOf($this, $variant, $treatMixedAsAny);
			if ($variantsResult === null) {
				$variantsResult = $isSuperType;
			} else {
				$variantsResult = $variantsResult->or($isSuperType);
			}
		}

		if ($variantsResult === null) {
			throw new ShouldNotHappenException();
		}

		return $isCallable->and($variantsResult);
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof IntersectionType || $otherType instanceof UnionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return $otherType->isCallable()
			->and($otherType instanceof self ? TrinaryLogic::createYes() : TrinaryLogic::createMaybe());
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isSubTypeOf($acceptingType);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'callable',
			fn (): string => sprintf(
				'callable(%s): %s',
				implode(', ', array_map(
					static fn (ParameterReflection $param): string => sprintf('%s%s', $param->isVariadic() ? '...' : '', $param->getType()->describe($level)),
					$this->getParameters(),
				)),
				$this->returnType->describe($level),
			),
		);
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

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
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

	public function toArray(): Type
	{
		return new ArrayType(new MixedType(), new MixedType());
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return TemplateTypeMap::createEmpty();
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return TemplateTypeMap::createEmpty();
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

		if (! $receivedType->isCallable()->yes()) {
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

		$parameters = array_map(static function (ParameterReflection $param) use ($cb): NativeParameterReflection {
			$defaultValue = $param->getDefaultValue();
			return new NativeParameterReflection(
				$param->getName(),
				$param->isOptional(),
				$cb($param->getType()),
				$param->passedByReference(),
				$param->isVariadic(),
				$defaultValue !== null ? $cb($defaultValue) : null,
			);
		}, $this->getParameters());

		return new self(
			$parameters,
			$cb($this->getReturnType()),
			$this->isVariadic(),
		);
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isCommonCallable(): bool
	{
		return $this->isCommonCallable;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			(bool) $properties['isCommonCallable'] ? null : $properties['parameters'],
			(bool) $properties['isCommonCallable'] ? null : $properties['returnType'],
			$properties['variadic'],
		);
	}

}
