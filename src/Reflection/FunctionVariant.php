<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

/** @api */
class FunctionVariant implements ParametersAcceptor, SingleParametersAcceptor
{

	/**
	 * @api
	 * @param array<int, ParameterReflection> $parameters
	 */
	public function __construct(
		private TemplateTypeMap $templateTypeMap,
		private ?TemplateTypeMap $resolvedTemplateTypeMap,
		private array $parameters,
		private bool $isVariadic,
		private Type $returnType,
	)
	{
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->templateTypeMap;
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return $this->resolvedTemplateTypeMap ?? TemplateTypeMap::createEmpty();
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
		return $this->isVariadic;
	}

	public function getReturnType(): Type
	{
		return $this->returnType;
	}

	/**
	 * @return static
	 */
	public function flattenConditionalsInReturnType(): SingleParametersAcceptor
	{
		/** @var static $result */
		$result = new self(
			$this->templateTypeMap,
			$this->resolvedTemplateTypeMap,
			$this->parameters,
			$this->isVariadic,
			TypeUtils::flattenConditionals($this->returnType),
		);

		return $result;
	}

}
