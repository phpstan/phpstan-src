<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

/** @api */
class FunctionVariantWithPhpDocs extends FunctionVariant implements ParametersAcceptorWithPhpDocs
{

	private Type $phpDocReturnType;

	private Type $nativeReturnType;

	/**
	 * @api
	 * @param TemplateTypeMap $templateTypeMap
	 * @param array<int, \PHPStan\Reflection\ParameterReflectionWithPhpDocs> $parameters
	 * @param bool $isVariadic
	 * @param Type $returnType
	 * @param Type $phpDocReturnType
	 * @param Type $nativeReturnType
	 */
	public function __construct(
		TemplateTypeMap $templateTypeMap,
		?TemplateTypeMap $resolvedTemplateTypeMap,
		array $parameters,
		bool $isVariadic,
		Type $returnType,
		Type $phpDocReturnType,
		Type $nativeReturnType
	)
	{
		parent::__construct(
			$templateTypeMap,
			$resolvedTemplateTypeMap,
			$parameters,
			$isVariadic,
			$returnType
		);
		$this->phpDocReturnType = $phpDocReturnType;
		$this->nativeReturnType = $nativeReturnType;
	}

	/**
	 * @return array<int, \PHPStan\Reflection\ParameterReflectionWithPhpDocs>
	 */
	public function getParameters(): array
	{
		/** @var \PHPStan\Reflection\ParameterReflectionWithPhpDocs[] $parameters */
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
