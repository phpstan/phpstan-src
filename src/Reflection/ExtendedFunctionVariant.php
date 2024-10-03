<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;

/**
 * @api
 */
class ExtendedFunctionVariant extends FunctionVariant implements ExtendedParametersAcceptor
{

	/**
	 * @param list<ExtendedParameterReflection> $parameters
	 * @api
	 */
	public function __construct(
		TemplateTypeMap $templateTypeMap,
		?TemplateTypeMap $resolvedTemplateTypeMap,
		array $parameters,
		bool $isVariadic,
		Type $returnType,
		private Type $phpDocReturnType,
		private Type $nativeReturnType,
		?TemplateTypeVarianceMap $callSiteVarianceMap = null,
	)
	{
		parent::__construct(
			$templateTypeMap,
			$resolvedTemplateTypeMap,
			$parameters,
			$isVariadic,
			$returnType,
			$callSiteVarianceMap,
		);
	}

	/**
	 * @return list<ExtendedParameterReflection>
	 */
	public function getParameters(): array
	{
		/** @var list<ExtendedParameterReflection> $parameters */
		$parameters = parent::getParameters();

		return $parameters;
	}

	public function getPhpDocReturnType(): Type
	{
		return $this->phpDocReturnType;
	}

	public function getNativeReturnType(): Type
	{
		return $this->nativeReturnType;
	}

}
