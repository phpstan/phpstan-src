<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;

/**
 * @api
 */
class FunctionVariant implements ParametersAcceptor
{

	private TemplateTypeVarianceMap $callSiteVarianceMap;

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
		?TemplateTypeVarianceMap $callSiteVarianceMap = null,
	)
	{
		$this->callSiteVarianceMap = $callSiteVarianceMap ?? TemplateTypeVarianceMap::createEmpty();
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->templateTypeMap;
	}

	public function getResolvedTemplateTypeMap(): TemplateTypeMap
	{
		return $this->resolvedTemplateTypeMap ?? TemplateTypeMap::createEmpty();
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
		return $this->isVariadic;
	}

	public function getReturnType(): Type
	{
		return $this->returnType;
	}

}
