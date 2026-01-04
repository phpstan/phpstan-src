<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

interface ResolvedFunctionVariant extends ExtendedParametersAcceptor
{

	public function getOriginalParametersAcceptor(): ExtendedParametersAcceptor;

	public function getReturnTypeWithUnresolvableTemplateTypes(): Type;

}
