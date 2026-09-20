<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Type\Type;

interface ResolvedFunctionVariant extends ExtendedParametersAcceptor
{

	public function getOriginalParametersAcceptor(): ParametersAcceptor;

	public function getReturnTypeWithUnresolvableTemplateTypes(): Type;

	/**
	 * The return type with the function's template arguments inferred from the
	 * arguments kept exact and, under a frame, marked as unresolved for the
	 * body to decide - where getReturnType() generalizes them (f(1) with
	 * `@return Foo<T>` is Foo<int>). Only the analyser's call handlers use it;
	 * $site is the call node the markers are keyed by.
	 */
	public function getReturnTypeWithUnresolvedTemplateArguments(Expr $site, TemplateArgumentFrame $frame, bool $allowUnresolved): Type;

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
