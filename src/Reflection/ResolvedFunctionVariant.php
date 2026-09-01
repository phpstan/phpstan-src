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

}
