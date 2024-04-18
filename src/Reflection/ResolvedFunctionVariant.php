<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

interface ResolvedFunctionVariant extends ParametersAcceptorWithPhpDocs
{

	public function getOriginalParametersAcceptor(): ParametersAcceptor;

	public function getReturnTypeWithUnresolvableTemplateTypes(): Type;

	public function getPhpDocReturnTypeWithUnresolvableTemplateTypes(): Type;

}
