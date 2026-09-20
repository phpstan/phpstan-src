<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use PHPStan\Reflection\GenericParametersAcceptorResolver;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
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

		// selectFromArgs() may hand back a variant that is not bound to this call's arguments
		// (either an unresolved acceptor, or a method variant whose passedArgs are empty),
		// so always resolve from this call's argument types against the original acceptor.
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

		$passedArgs = [];
		foreach (self::collectParameterNames($declaredType) as $parameterName) {
			$variableName = substr($parameterName, 1);
			if (!$scope->hasVariableType($variableName)->yes()) {
				continue;
			}

			$passedArgs[$parameterName] = $scope->getType(new Variable($variableName));
		}

		$declaredType = self::mapConditionalTypesForParameter($declaredType, $passedArgs);

		// A ConditionalType whose subject is a template type cannot be resolved to a single
		// branch inside the function body (the template is not bound to a concrete type there),
		// so it is conservatively collapsed to the union of its branches — the broadest type the
		// declaration permits — rather than left as a Maybe-certain conditional.
		return TypeUtils::resolveLateResolvableTypes($declaredType, true);
	}

	/**
	 * @param array<string, Type> $passedArgs
	 */
	private static function mapConditionalTypesForParameter(Type $declaredType, array $passedArgs): Type
	{
		if ($passedArgs === []) {
			return $declaredType;
		}

		return TypeTraverser::map($declaredType, static function (Type $type, callable $traverse) use ($passedArgs): Type {
			if ($type instanceof ConditionalTypeForParameter && array_key_exists($type->getParameterName(), $passedArgs)) {
				$type = $traverse($type);
				if ($type instanceof ConditionalTypeForParameter) {
					return $type->toConditional($passedArgs[$type->getParameterName()]);
				}

				return $type;
			}

			return $traverse($type);
		});
	}

	/**
	 * @return list<string>
	 */
	private static function collectParameterNames(Type $declaredType): array
	{
		$names = [];
		TypeTraverser::map($declaredType, static function (Type $type, callable $traverse) use (&$names): Type {
			if ($type instanceof ConditionalTypeForParameter) {
				$names[] = $type->getParameterName();
			}

			return $traverse($type);
		});

		return $names;
	}

}
