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
 * Resolves conditional `@throws` types like `($x is 0 ? Exception : void)` and
 * `(TKey is int ? void : Exception)`.
 *
 * The same `ConditionalTypeForParameter` and `ConditionalType` representations
 * used for conditional return types are resolved here against either the
 * arguments passed at a call site (so callers see whether the call throws) or
 * against the parameter variables inside the function body (so the body's throw
 * points are matched against the declared `@throws` type).
 */
final class ConditionalThrowTypeResolver
{

	/**
	 * Resolves a conditional `@throws` type against a call site. A `ResolvedFunctionVariant`
	 * already holds the call's bound arguments and inferred template types and knows how to
	 * resolve a conditional type the same way it resolves a conditional return type — both
	 * `ConditionalTypeForParameter` (e.g. `($x is 0 ? Exception : void)`) and `ConditionalType`
	 * whose subject is a template type (e.g. `(TKey is int ? void : Exception)`).
	 *
	 * `ParametersAcceptorSelector::selectFromArgs()` only resolves the variant when the return
	 * or parameter types are conditional/generic — it does not know about the throws type — so
	 * when the throws type is the only conditional one, the variant is resolved here from the
	 * passed arguments via `GenericParametersAcceptorResolver`.
	 *
	 * @param Arg[] $args
	 */
	public static function resolveForCall(
		Type $throwType,
		ParametersAcceptor $parametersAcceptor,
		array $args,
		Scope $scope,
	): Type
	{
		if (!$throwType->hasTemplateOrLateResolvableType()) {
			return $throwType;
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
			return $throwType;
		}

		return $resolvedAcceptor->resolveConditionalTypes($throwType);
	}

	public static function resolveForScope(Type $throwType, Scope $scope): Type
	{
		if (!$throwType->hasTemplateOrLateResolvableType()) {
			return $throwType;
		}

		$passedArgs = [];
		foreach (self::collectParameterNames($throwType) as $parameterName) {
			$variableName = substr($parameterName, 1);
			if (!$scope->hasVariableType($variableName)->yes()) {
				continue;
			}

			$passedArgs[$parameterName] = $scope->getType(new Variable($variableName));
		}

		$throwType = self::mapConditionalTypesForParameter($throwType, $passedArgs);

		// A ConditionalType whose subject is a template type cannot be resolved to a single
		// branch inside the function body (the template is not bound to a concrete type there),
		// so it is conservatively collapsed to the union of its branches — the broadest set of
		// exceptions the declaration permits — rather than left as a Maybe-certain conditional.
		return TypeUtils::resolveLateResolvableTypes($throwType, true);
	}

	/**
	 * @param array<string, Type> $passedArgs
	 */
	private static function mapConditionalTypesForParameter(Type $throwType, array $passedArgs): Type
	{
		if ($passedArgs === []) {
			return $throwType;
		}

		return TypeTraverser::map($throwType, static function (Type $type, callable $traverse) use ($passedArgs): Type {
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
	private static function collectParameterNames(Type $throwType): array
	{
		$names = [];
		TypeTraverser::map($throwType, static function (Type $type, callable $traverse) use (&$names): Type {
			if ($type instanceof ConditionalTypeForParameter) {
				$names[] = $type->getParameterName();
			}

			return $traverse($type);
		});

		return $names;
	}

}
