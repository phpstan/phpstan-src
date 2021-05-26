<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

/** @api */
interface ParametersAcceptorWithPhpDocs extends ParametersAcceptor
{

	/**
	 * @return array<int, \PHPStan\Reflection\ParameterReflectionWithPhpDocs>
	 */
	public function getParameters(): array;

	public function getPhpDocReturnType(): Type;

	public function getNativeReturnType(): Type;

}
