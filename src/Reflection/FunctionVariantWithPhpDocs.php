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
	 * @param array<int, ParameterReflectionWithPhpDocs> $parameters
	 */
	public function __construct(
		TemplateTypeMap $templateTypeMap,
		?TemplateTypeMap $resolvedTemplateTypeMap,
		array $parameters,
		bool $isVariadic,
		Type $returnType,
		Type $phpDocReturnType,
		Type $nativeReturnType,
	)
	{
		parent::__construct(
			$templateTypeMap,
			$resolvedTemplateTypeMap,
			$parameters,
			$isVariadic,
			$returnType,
		);
		$this->phpDocReturnType = $phpDocReturnType;
		$this->nativeReturnType = $nativeReturnType;
	}

	/**
	 * @return array<int, ParameterReflectionWithPhpDocs>
	 */
	public function getParameters(): array
	{
		/** @var ParameterReflectionWithPhpDocs[] $parameters */
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
