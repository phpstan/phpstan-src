<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function array_map;

class ResolvedFunctionVariant implements ParametersAcceptor, SingleParametersAcceptor
{

	/** @var ParameterReflection[]|null */
	private ?array $parameters = null;

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
			$parameters = array_map(fn (ParameterReflection $param): ParameterReflection => new DummyParameter(
				$param->getName(),
				TemplateTypeHelper::resolveTemplateTypes($param->getType(), $this->resolvedTemplateTypeMap),
				$param->isOptional(),
				$param->passedByReference(),
				$param->isVariadic(),
				$param->getDefaultValue(),
			), $this->parametersAcceptor->getParameters());

			$this->parameters = $parameters;
		}

		return $parameters;
	}

	public function isVariadic(): bool
	{
		return $this->parametersAcceptor->isVariadic();
	}

	public function getReturnType(): Type
	{
		$type = $this->returnType;

		if ($type === null) {
			$type = TemplateTypeHelper::resolveTemplateTypes(
				$this->parametersAcceptor->getReturnType(),
				$this->resolvedTemplateTypeMap,
			);

			$type = $this->resolveConditionalTypes($type);

			$this->returnType = $type;
		}

		return $type;
	}

	/**
	 * @return static
	 */
	public function flattenConditionalsInReturnType(): SingleParametersAcceptor
	{
		/** @var static $result */
		$result = new self(
			$this->parametersAcceptor,
			$this->resolvedTemplateTypeMap,
			$this->passedArgs,
		);
		$result->returnType = TypeUtils::flattenConditionals($this->getReturnType());

		return $result;
	}

	private function resolveConditionalTypes(Type $type): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			while ($type instanceof ConditionalTypeForParameter && array_key_exists($type->getParameterName(), $this->passedArgs)) {
				$type = $type->toConditional($this->passedArgs[$type->getParameterName()]);
			}

			return $traverse($type);
		});
	}

}
