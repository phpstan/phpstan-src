<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\NarrowedSubjectType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function array_map;

final class ResolvedFunctionVariantWithOriginal implements ResolvedFunctionVariant
{

	/** @var list<ExtendedParameterReflection>|null */
	private ?array $parameters = null;

	private ?Type $returnTypeWithUnresolvableTemplateTypes = null;

	private ?Type $phpDocReturnTypeWithUnresolvableTemplateTypes = null;

	private ?Type $returnType = null;

	private ?Type $phpDocReturnType = null;

	/**
	 * @param array<string, Type> $passedArgs
	 */
	public function __construct(
		private ExtendedParametersAcceptor $parametersAcceptor,
		private TemplateTypeMap $resolvedTemplateTypeMap,
		private TemplateTypeVarianceMap $callSiteVarianceMap,
		private array $passedArgs,
	)
	{
	}

	public function getOriginalParametersAcceptor(): ParametersAcceptor
	{
		return $this->parametersAcceptor;
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->parametersAcceptor->getTemplateTypeMap();
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return $this->resolvedTemplateTypeMap;
	}

	public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
	{
		return $this->callSiteVarianceMap;
	}

	public function getParameters(): array
	{
		$parameters = $this->parameters;

		if ($parameters === null) {
			$parameters = array_map(
				function (ExtendedParameterReflection $param): ExtendedParameterReflection {
					$paramType = TypeUtils::resolveLateResolvableTypes(
						TemplateTypeHelper::resolveTemplateTypes(
							$this->resolveConditionalTypesForParameter($param->getType()),
							$this->resolvedTemplateTypeMap,
							$this->callSiteVarianceMap,
							TemplateTypeVariance::createContravariant(),
						),
						false,
					);

					$paramOutType = $param->getOutType();
					if ($paramOutType !== null) {
						$paramOutType = TypeUtils::resolveLateResolvableTypes(
							TemplateTypeHelper::resolveTemplateTypes(
								$this->resolveConditionalTypesForParameter($paramOutType),
								$this->resolvedTemplateTypeMap,
								$this->callSiteVarianceMap,
								TemplateTypeVariance::createCovariant(),
							),
							false,
						);
					}

					$closureThisType = $param->getClosureThisType();
					if ($closureThisType !== null) {
						$closureThisType = TypeUtils::resolveLateResolvableTypes(
							TemplateTypeHelper::resolveTemplateTypes(
								$this->resolveConditionalTypesForParameter($closureThisType),
								$this->resolvedTemplateTypeMap,
								$this->callSiteVarianceMap,
								TemplateTypeVariance::createCovariant(),
							),
							false,
						);
					}

					return new ExtendedDummyParameter(
						$param->getName(),
						$paramType,
						$param->isOptional(),
						$param->passedByReference(),
						$param->isVariadic(),
						$param->getDefaultValue(),
						$param->getNativeType(),
						$param->getPhpDocType(),
						$paramOutType,
						$param->isImmediatelyInvokedCallable(),
						$closureThisType,
						$param->getAttributes(),
						$param->getAllowedConstants(),
						$param->isPureUnlessCallableIsImpureParameter(),
					);
				},
				$this->parametersAcceptor->getParameters(),
			);

			$this->parameters = $parameters;
		}

		return $parameters;
	}

	public function isVariadic(): bool
	{
		return $this->parametersAcceptor->isVariadic();
	}

	public function getReturnTypeWithUnresolvableTemplateTypes(): Type
	{
		return $this->returnTypeWithUnresolvableTemplateTypes ??=
			$this->resolveConditionalTypesForParameter(
				$this->resolveResolvableTemplateTypes(
					$this->narrowTemplateTypesInConditionalTypesForParameter($this->parametersAcceptor->getReturnType()),
					TemplateTypeVariance::createCovariant(),
				),
			);
	}

	private function getPhpDocReturnTypeWithUnresolvableTemplateTypes(): Type
	{
		return $this->phpDocReturnTypeWithUnresolvableTemplateTypes ??=
			$this->resolveConditionalTypesForParameter(
				$this->resolveResolvableTemplateTypes(
					$this->narrowTemplateTypesInConditionalTypesForParameter($this->parametersAcceptor->getPhpDocReturnType()),
					TemplateTypeVariance::createCovariant(),
				),
			);
	}

	public function getReturnType(): Type
	{
		$type = $this->returnType;

		if ($type === null) {
			$type = TypeUtils::resolveLateResolvableTypes(
				TemplateTypeHelper::resolveTemplateTypes(
					$this->getReturnTypeWithUnresolvableTemplateTypes(),
					$this->resolvedTemplateTypeMap,
					$this->callSiteVarianceMap,
					TemplateTypeVariance::createCovariant(),
				),
				false,
			);

			$this->returnType = $type;
		}

		return $type;
	}

	public function getPhpDocReturnType(): Type
	{
		$type = $this->phpDocReturnType;

		if ($type === null) {
			$type = TypeUtils::resolveLateResolvableTypes(
				TemplateTypeHelper::resolveTemplateTypes(
					$this->getPhpDocReturnTypeWithUnresolvableTemplateTypes(),
					$this->resolvedTemplateTypeMap,
					$this->callSiteVarianceMap,
					TemplateTypeVariance::createCovariant(),
				),
				false,
			);

			$this->phpDocReturnType = $type;
		}

		return $type;
	}

	public function getNativeReturnType(): Type
	{
		return $this->parametersAcceptor->getNativeReturnType();
	}

	private function resolveResolvableTemplateTypes(Type $type, TemplateTypeVariance $positionVariance): Type
	{
		$objectCb = function (Type $type, TemplateTypeVariance $variance, callable $traverse): Type {
			if (
				$type instanceof TemplateType
				&& !$type instanceof NarrowedSubjectType
				&& !$type->isArgument()
				&& $type->getScope()->getFunctionName() !== null
			) {
				$newType = $this->resolvedTemplateTypeMap->getType($type->getName());
				if ($newType === null || $newType instanceof ErrorType) {
					return $traverse($type);
				}

				$newType = TemplateTypeHelper::generalizeInferredTemplateType($type, $newType);

				$callSiteVariance = $this->callSiteVarianceMap->getVariance($type->getName());
				if ($callSiteVariance === null || $callSiteVariance->invariant()) {
					return $newType;
				}

				if (!$callSiteVariance->covariant() && $variance->covariant()) {
					return $traverse($type->getBound());
				}

				if (!$callSiteVariance->contravariant() && $variance->contravariant()) {
					return new NonAcceptingNeverType();
				}

				return $newType;
			}

			return $traverse($type);
		};

		return TypeTraverser::mapWithVariance($type, $positionVariance, function (Type $type, TemplateTypeVariance $variance, callable $traverse) use ($objectCb): Type {
			if ($type instanceof GenericObjectType || $type instanceof GenericStaticType) {
				return TypeTraverser::mapWithVariance($type, $variance, $objectCb);
			}

			if ($type instanceof TemplateType && !$type instanceof NarrowedSubjectType && !$type->isArgument()) {
				$newType = $this->resolvedTemplateTypeMap->getType($type->getName());
				if ($newType === null || $newType instanceof ErrorType) {
					return $traverse($type);
				}

				$callSiteVariance = $this->callSiteVarianceMap->getVariance($type->getName());
				if ($callSiteVariance === null || $callSiteVariance->invariant()) {
					return $newType;
				}

				if (!$callSiteVariance->covariant() && $variance->covariant()) {
					return $traverse($type->getBound());
				}

				if (!$callSiteVariance->contravariant() && $variance->contravariant()) {
					return new NonAcceptingNeverType();
				}

				return $newType;
			}

			return $traverse($type);
		});
	}

	/**
	 * `($param is X ? A : B)` narrows the parameter, and when the parameter alone binds the
	 * template type it is declared with, that template type too: `@param T $param` makes the
	 * references to T in the branches `T & X` and `T ~ X` (see
	 * ConditionalTypeForParameter::narrowTemplateType()). Has to happen before the template
	 * types resolve, while the references are still recognizable.
	 */
	private function narrowTemplateTypesInConditionalTypesForParameter(Type $type): Type
	{
		if (!$type->hasTemplateOrLateResolvableType()) {
			return $type;
		}

		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if ($type instanceof ConditionalTypeForParameter) {
				$templateType = $this->getTemplateTypeBoundOnlyByParameter($type->getParameterName());
				if ($templateType !== null) {
					$type = $type->narrowTemplateType($templateType);
				}
			}

			return $traverse($type);
		});
	}

	/**
	 * The template type of the function the parameter is declared as, provided no other
	 * parameter references it - another parameter could bind it to values a condition on
	 * this parameter says nothing about. A reference through the bound of another template
	 * type counts too: `@param class-string<TFormType> $type` with
	 * `@template TFormType of FormTypeInterface<TData>` binds TData.
	 */
	private function getTemplateTypeBoundOnlyByParameter(string $parameterName): ?TemplateType
	{
		$templateType = null;
		foreach ($this->parametersAcceptor->getParameters() as $parameter) {
			if ('$' . $parameter->getName() !== $parameterName) {
				continue;
			}
			if ($parameter->isVariadic()) {
				return null;
			}

			$type = $parameter->getType();
			if (
				!$type instanceof TemplateType
				|| $type instanceof NarrowedSubjectType
				|| $type->getScope()->getFunctionName() === null
			) {
				return null;
			}

			$templateType = $type;
			break;
		}

		if ($templateType === null) {
			return null;
		}

		foreach ($this->parametersAcceptor->getParameters() as $parameter) {
			if ('$' . $parameter->getName() === $parameterName) {
				continue;
			}

			if (self::referencesTemplateType($parameter->getType(), $templateType)) {
				return null;
			}
		}

		return $templateType;
	}

	private static function referencesTemplateType(Type $type, TemplateType $templateType): bool
	{
		$references = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($templateType, &$references): Type {
			if (
				$type instanceof TemplateType
				&& $type->getName() === $templateType->getName()
				&& $type->getScope()->equals($templateType->getScope())
			) {
				$references = true;

				return $type;
			}

			// a template type traverses into its bound
			return $traverse($type);
		});

		return $references;
	}

	private function resolveConditionalTypesForParameter(Type $type): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if ($type instanceof ConditionalTypeForParameter && array_key_exists($type->getParameterName(), $this->passedArgs)) {
				// Traverse children first, then convert — avoids infinite loop when
				// the passed argument contains ConditionalTypeForParameter with a colliding parameter name.
				$type = $traverse($type);
				if ($type instanceof ConditionalTypeForParameter) {
					return $type->toConditional($this->passedArgs[$type->getParameterName()]);
				}
				return $type;
			}

			return $traverse($type);
		});
	}

}
