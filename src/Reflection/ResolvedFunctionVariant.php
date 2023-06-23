<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\DummyParameterWithPhpDocs;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VarianceAwareTypeTraverser;
use function array_key_exists;
use function array_map;

class ResolvedFunctionVariant implements ParametersAcceptorWithPhpDocs
{

	/** @var ParameterReflectionWithPhpDocs[]|null */
	private ?array $parameters = null;

	private ?Type $returnTypeWithUnresolvableTemplateTypes = null;

	private ?Type $phpDocReturnTypeWithUnresolvableTemplateTypes = null;

	private ?Type $returnType = null;

	private ?Type $phpDocReturnType = null;

	/**
	 * @param array<string, Type> $passedArgs
	 */
	public function __construct(
		private ParametersAcceptorWithPhpDocs $parametersAcceptor,
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
				function (ParameterReflectionWithPhpDocs $param): ParameterReflectionWithPhpDocs {
					$paramType = TypeUtils::resolveLateResolvableTypes(
						TemplateTypeHelper::resolveTemplateTypes(
							$this->resolveConditionalTypesForParameter($param->getType()),
							$this->resolvedTemplateTypeMap,
							$this->callSiteVarianceMap,
							TemplateTypeVariance::createContravariant(),
						),
						false,
					);

					return new DummyParameterWithPhpDocs(
						$param->getName(),
						$paramType,
						$param->isOptional(),
						$param->passedByReference(),
						$param->isVariadic(),
						$param->getDefaultValue(),
						$param->getNativeType(),
						$param->getPhpDocType(),
						$param->getOutType(),
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

	public function getPhpDocReturnTypeWithUnresolvableTemplateTypes(): Type
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
		return VarianceAwareTypeTraverser::map($type, $positionVariance, function (Type $type, TemplateTypeVariance $positionVariance, callable $traverse): Type {
			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $this->resolvedTemplateTypeMap->getType($type->getName());
				if ($newType === null || $newType instanceof ErrorType) {
					return $traverse($type, $positionVariance);
				}

				$callSiteVariance = $this->callSiteVarianceMap->getVariance($type->getName());
				if ($callSiteVariance === null || $callSiteVariance->invariant()) {
					return $newType;
				}

				if (!$callSiteVariance->covariant() && $positionVariance->covariant()) {
					return $traverse($type->getBound(), $positionVariance);
				}

				if (!$callSiteVariance->contravariant() && $positionVariance->contravariant()) {
					return new NonAcceptingNeverType();
				}

				return $newType;
			}

			return $traverse($type, $positionVariance);
		});
	}

	private function resolveConditionalTypesForParameter(Type $type): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if ($type instanceof ConditionalTypeForParameter && array_key_exists($type->getParameterName(), $this->passedArgs)) {
				$type = $type->toConditional($this->passedArgs[$type->getParameterName()]);
			}

			return $traverse($type);
		});
	}

}
