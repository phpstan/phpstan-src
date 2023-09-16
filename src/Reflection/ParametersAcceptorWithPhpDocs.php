<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;

/** @api */
interface ParametersAcceptorWithPhpDocs extends ParametersAcceptor
{

	/**
	 * @return array<int, ParameterReflectionWithPhpDocs>
	 */
	public function getParameters(): array;

	public function getPhpDocReturnType(): Type;

	public function getNativeReturnType(): Type;

	public function getCallSiteVarianceMap(): TemplateTypeVarianceMap;

}
