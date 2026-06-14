<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

interface ResolvedFunctionVariant extends ExtendedParametersAcceptor
{

	public function getOriginalParametersAcceptor(): ParametersAcceptor;

	public function getReturnTypeWithUnresolvableTemplateTypes(): Type;

	/**
	 * Resolves an arbitrary declared type (e.g. a conditional `@throws` type) against this
	 * call's bound arguments and inferred template types, the same way the return type is
	 * resolved at the call site.
	 */
	public function resolveConditionalTypes(Type $type): Type;

}
