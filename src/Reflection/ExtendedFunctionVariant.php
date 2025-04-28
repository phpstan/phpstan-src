<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;

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
		private ?Type $returnType,
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
			$returnType ?? TypehintHelper::decideType(
				$nativeReturnType,
				$phpDocReturnType,
			),
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

	public function getReturnType(): Type
	{
		if ($this->returnType === null) {
			return $this->returnType = TypehintHelper::decideType(
				$this->nativeReturnType,
				$this->phpDocReturnType,
			);
		}

		return $this->returnType;
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
