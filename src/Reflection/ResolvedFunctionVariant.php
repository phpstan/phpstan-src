<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\Php\DummyParameterWithPhpDocs;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function array_map;

class ResolvedFunctionVariant implements ParametersAcceptor
{

	/** @var ParameterReflection[]|null */
	private ?array $parameters = null;

	private ?Type $returnTypeWithUnresolvableTemplateTypes = null;

	private ?Type $returnType = null;

	/**
	 * @param array<string, Type> $passedArgs
	 */
	public function __construct(
		private ParametersAcceptor $parametersAcceptor,
		private TemplateTypeMap $resolvedTemplateTypeMap,
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

	public function getParameters(): array
	{
		$parameters = $this->parameters;

		if ($parameters === null) {
			$parameters = array_map(
				function (ParameterReflection $param): ParameterReflection {
					$paramType = TypeUtils::resolveLateResolvableTypes(
						TemplateTypeHelper::resolveTemplateTypes(
							$this->resolveConditionalTypesForParameter($param->getType()),
							$this->resolvedTemplateTypeMap,
						),
						false,
					);

					if ($param instanceof ParameterReflectionWithPhpDocs) {
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
					}

					return new DummyParameter(
						$param->getName(),
						$paramType,
						$param->isOptional(),
						$param->passedByReference(),
						$param->isVariadic(),
						$param->getDefaultValue(),
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
				$this->resolveResolvableTemplateTypes($this->parametersAcceptor->getReturnType()),
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
				),
				false,
			);

			$this->returnType = $type;
		}

		return $type;
	}

	private function resolveResolvableTemplateTypes(Type $type): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $this->resolvedTemplateTypeMap->getType($type->getName());
				if ($newType !== null && !$newType instanceof ErrorType) {
					return $newType;
				}
			}

			return $traverse($type);
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
