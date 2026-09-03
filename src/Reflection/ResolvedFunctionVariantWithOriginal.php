<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
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
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function array_map;
use function spl_object_id;
use function sprintf;

final class ResolvedFunctionVariantWithOriginal implements ResolvedFunctionVariant
{

	/** @var list<ExtendedParameterReflection>|null */
	private ?array $parameters = null;

	private ?Type $returnTypeWithUnresolvableTemplateTypes = null;

	private ?Type $phpDocReturnTypeWithUnresolvableTemplateTypes = null;

	private ?Type $returnType = null;

	private ?Type $phpDocReturnType = null;

	/** @var array{string, Type}|null memo key => type */
	private ?array $returnTypeWithUnresolvedTemplateArguments = null;

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
				$this->resolveResolvableTemplateTypes($this->parametersAcceptor->getReturnType(), TemplateTypeVariance::createCovariant()),
			);
	}

	private function getPhpDocReturnTypeWithUnresolvableTemplateTypes(): Type
	{
		return $this->phpDocReturnTypeWithUnresolvableTemplateTypes ??=
			$this->resolveConditionalTypesForParameter(
				$this->resolveResolvableTemplateTypes($this->parametersAcceptor->getPhpDocReturnType(), TemplateTypeVariance::createCovariant()),
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

	public function getReturnTypeWithUnresolvedTemplateArguments(Expr $site, TemplateArgumentFrame $frame, bool $allowUnresolved): Type
	{
		// a lazily asked callback can ask the same variant during the observation
		// pass and again once the frame resolved - the memo tells them apart
		$memoKey = sprintf('%d/%s/%s', spl_object_id($site), $allowUnresolved ? 'u' : 'n', $frame->isObserving() ? 'o' : 'r');
		if ($this->returnTypeWithUnresolvedTemplateArguments !== null && $this->returnTypeWithUnresolvedTemplateArguments[0] === $memoKey) {
			return $this->returnTypeWithUnresolvedTemplateArguments[1];
		}

		$type = TypeUtils::resolveLateResolvableTypes(
			TemplateTypeHelper::resolveTemplateTypes(
				$this->resolveConditionalTypesForParameter(
					$this->resolveResolvableTemplateTypes($this->parametersAcceptor->getReturnType(), TemplateTypeVariance::createCovariant(), $site, $frame, $allowUnresolved),
				),
				$this->resolvedTemplateTypeMap,
				$this->callSiteVarianceMap,
				TemplateTypeVariance::createCovariant(),
			),
			false,
		);
		$this->returnTypeWithUnresolvedTemplateArguments = [$memoKey, $type];

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

	private function resolveResolvableTemplateTypes(Type $type, TemplateTypeVariance $positionVariance, ?Expr $site = null, ?TemplateArgumentFrame $frame = null, bool $allowUnresolved = true): Type
	{
		$references = $type->getReferencedTemplateTypes($positionVariance);

		$objectCb = function (Type $type, callable $traverse) use ($references, $site, $frame, $allowUnresolved): Type {
			if (
				$type instanceof TemplateType
				&& !$type->isArgument()
				&& $type->getScope()->getFunctionName() !== null
			) {
				$newType = $this->resolvedTemplateTypeMap->getType($type->getName());
				if ($newType === null || $newType instanceof ErrorType) {
					return $traverse($type);
				}

				if ($site !== null && $frame !== null) {
					$newType = $this->unresolvedOrResolvedTemplateArgument($type, $newType, $site, $frame, $allowUnresolved);
				} else {
					$newType = TemplateTypeHelper::generalizeInferredTemplateType($type, $newType);
				}
				$variance = TemplateTypeVariance::createInvariant();
				foreach ($references as $reference) {
					// this uses identity to distinguish between different occurrences of the same template type
					// see https://github.com/phpstan/phpstan-src/pull/2485#discussion_r1328555397 for details
					if ($reference->getType() === $type) {
						$variance = $reference->getPositionVariance();
						break;
					}
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
		};

		return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($references, $objectCb): Type {
			if ($type instanceof GenericObjectType || $type instanceof GenericStaticType) {
				return TypeTraverser::map($type, $objectCb);
			}

			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $this->resolvedTemplateTypeMap->getType($type->getName());
				if ($newType === null || $newType instanceof ErrorType) {
					return $traverse($type);
				}

				$variance = TemplateTypeVariance::createInvariant();
				foreach ($references as $reference) {
					// this uses identity to distinguish between different occurrences of the same template type
					// see https://github.com/phpstan/phpstan-src/pull/2485#discussion_r1328555397 for details
					if ($reference->getType() === $type) {
						$variance = $reference->getPositionVariance();
						break;
					}
				}

				if ($variance->covariant()) {
					// an unresolved template argument inferred from a generic argument
					// and returned bare is a derived value - see TemplateTypeHelper::resolveTemplateTypes()
					$newType = UnresolvedTemplateArgumentType::unwrapBare($newType);
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
	 * An inferred template argument inside a generic object of the return type:
	 * during the body's observation pass a marker keyed by the call (an inferred
	 * argument that already carries another site's marker passes through - the
	 * outer result then resolves the inner site), once the frame resolved its
	 * resolution, else the exact inferred type.
	 */
	private function unresolvedOrResolvedTemplateArgument(TemplateType $template, Type $inferred, Expr $site, TemplateArgumentFrame $frame, bool $allowUnresolved): Type
	{
		if ($allowUnresolved && $frame->isObserving()) {
			if ($inferred instanceof UnresolvedTemplateArgumentType) {
				return $inferred;
			}
			$marker = new UnresolvedTemplateArgumentType($site, $template, $inferred);
			$frame->noteSite($marker);

			return $marker;
		}

		return $frame->resolve($site, $template->getName()) ?? $inferred;
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
