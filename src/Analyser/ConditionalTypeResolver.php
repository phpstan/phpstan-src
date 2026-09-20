<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use PHPStan\Reflection\GenericParametersAcceptorResolver;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use function substr;

/**
 * Resolves conditional types like `($x is 0 ? Exception : void)` and
 * `(TKey is int ? void : Exception)` declared in PHPDoc tags that live on the
 * function or method reflection instead of on the ParametersAcceptor - `@throws`
 * and `@phpstan-self-out`.
 *
 * Tags carried by the ParametersAcceptor (`@return`, `@param`, `@param-out`,
 * `@param-closure-this`) do not need this: ParametersAcceptorSelector::selectFromArgs()
 * already hands back a ResolvedFunctionVariant that resolves them. `@phpstan-assert`
 * does its own resolution in TypeSpecifier because its subjects are argument
 * expressions rather than types.
 *
 * Either side of the resolution is supported: against the arguments passed at a
 * call site (so callers see the branch their arguments select), or against the
 * parameter variables inside the function body.
 */
final class ConditionalTypeResolver
{

	/**
	 * Resolves a conditional type against a call site. A `ResolvedFunctionVariant`
	 * already holds the call's bound arguments and inferred template types and knows how to
	 * resolve a conditional type the same way it resolves a conditional return type — both
	 * `ConditionalTypeForParameter` (e.g. `($x is 0 ? Exception : void)`) and `ConditionalType`
	 * whose subject is a template type (e.g. `(TKey is int ? void : Exception)`).
	 *
	 * `ParametersAcceptorSelector::selectFromArgs()` only resolves the variant when the return
	 * or parameter types are conditional/generic — it does not know about the types declared by
	 * the tags resolved here — so the variant is resolved from the passed arguments via
	 * `GenericParametersAcceptorResolver`.
	 *
	 * @param Arg[] $args
	 */
	public static function resolveForCall(
		Type $declaredType,
		ParametersAcceptor $parametersAcceptor,
		array $args,
		Scope $scope,
	): Type
	{
		if (!$declaredType->hasTemplateOrLateResolvableType()) {
			return $declaredType;
		}

		// A variant already bound to this call's arguments has the template types inferred
		// from everything the call knows - including a closure argument's return type, which
		// the argument type alone no longer tells - so it resolves the type as it is.
		if ($parametersAcceptor instanceof ResolvedFunctionVariant && $parametersAcceptor->hasBoundArgs()) {
			return $parametersAcceptor->resolveConditionalTypes($declaredType);
		}

		// Otherwise the acceptor is not bound to this call (an unresolved acceptor, or a method
		// variant resolved only against the generics of the class it is called on), so the
		// variant is resolved here from this call's argument types.
		$originalAcceptor = $parametersAcceptor instanceof ResolvedFunctionVariant
			? $parametersAcceptor->getOriginalParametersAcceptor()
			: $parametersAcceptor;

		$argTypes = [];
		foreach ($args as $i => $arg) {
			$argTypes[$arg->name !== null ? $arg->name->toString() : $i] = $scope->getType($arg->value);
		}

		$resolvedAcceptor = GenericParametersAcceptorResolver::resolve($argTypes, $originalAcceptor);
		if (!$resolvedAcceptor instanceof ResolvedFunctionVariant) {
			return $declaredType;
		}

		return $resolvedAcceptor->resolveConditionalTypes($declaredType);
	}

	public static function resolveForScope(Type $declaredType, Scope $scope): Type
	{
		if (!$declaredType->hasTemplateOrLateResolvableType()) {
			return $declaredType;
		}

		$declaredType = ConditionalTypeForParameter::resolveInType(
			$declaredType,
			static function (string $parameterName) use ($scope): ?Type {
				$variableName = substr($parameterName, 1);
				if (!$scope->hasVariableType($variableName)->yes()) {
					return null;
				}

				return $scope->getType(new Variable($variableName));
			},
		);

		// A ConditionalType whose subject is a template type cannot be resolved to a single
		// branch inside the function body (the template is not bound to a concrete type there),
		// so it is conservatively collapsed to the union of its branches — the broadest type the
		// declaration permits — rather than left as a Maybe-certain conditional.
		return TypeUtils::resolveLateResolvableTypes($declaredType, true);
	}

}
