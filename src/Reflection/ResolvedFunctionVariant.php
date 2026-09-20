<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

interface ResolvedFunctionVariant extends ExtendedParametersAcceptor
{

	public function getOriginalParametersAcceptor(): ParametersAcceptor;

	public function getReturnTypeWithUnresolvableTemplateTypes(): Type;

	/**
	 * Whether the variant is bound to the arguments of a specific call, as opposed to being
	 * resolved only against the generics of the class the method is called on.
	 */
	public function hasBoundArgs(): bool;

	/**
	 * Resolves an arbitrary declared type (e.g. a conditional `@throws` or `@phpstan-self-out`
	 * type) against this call's bound arguments and inferred template types, the same way the
	 * return type is resolved at the call site.
	 */
	public function resolveConditionalTypes(Type $type): Type;

}
