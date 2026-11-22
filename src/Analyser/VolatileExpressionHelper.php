<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SideEffects;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function array_key_exists;
use function array_keys;
use function count;
use function in_array;
use function ltrim;
use function str_starts_with;
use function strtolower;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/VolatileExpressionHelper.cpp')]
final class VolatileExpressionHelper
{

	private const VOLATILE_FUNCTION_NAMES = ['ob_get_level'];

	private const EXISTENCE_CHECK_FUNCTION_NAMES = [
		'class_exists',
		'interface_exists',
		'trait_exists',
		'enum_exists',
		'function_exists',
	];

	/**
	 * Forgets tracked argument-less volatile function-call expressions.
	 *
	 * The functions take no arguments, so the exact key lookups keep this O(1) in
	 * the common case where nothing is tracked.
	 *
	 * @param array<string, ExpressionTypeHolder> $expressionTypes
	 * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
	 * @return bool whether anything was removed
	 */
	public static function invalidateVolatileFunctionCalls(array &$expressionTypes, array &$nativeExpressionTypes): bool
	{
		$changed = false;
		foreach (self::VOLATILE_FUNCTION_NAMES as $functionName) {
			foreach ([$functionName . '()', '\\' . $functionName . '()'] as $exprString) {
				if (
					!array_key_exists($exprString, $expressionTypes)
					&& !array_key_exists($exprString, $nativeExpressionTypes)
				) {
					continue;
				}

				unset($expressionTypes[$exprString]);
				unset($nativeExpressionTypes[$exprString]);
				$changed = true;
			}
		}

		return $changed;
	}

	/**
	 * Forgets tracked calls of functions and methods whose result depends on
	 * global state (reads-global), such as ini_get('precision'): the
	 * invalidating code may write that state. Existence checks are left to
	 * invalidateNegativeExistenceChecks(): symbols are only ever added, so a
	 * positive result stays true.
	 *
	 * With $writtenStates, the invalidating code writes only those named parts
	 * of global state, such as ['stat-cache'] for writes-global.stat-cache, and
	 * only calls that read a named part they overlap with are forgotten:
	 * reads-global.error is forgotten for error.preg, reads-global alone is not.
	 *
	 * @param array<string, ExpressionTypeHolder> $expressionTypes
	 * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
	 * @param list<string>|null $writtenStates
	 * @return bool whether anything was removed
	 */
	public static function invalidateGlobalStateReads(Scope $scope, ReflectionProvider $reflectionProvider, array &$expressionTypes, array &$nativeExpressionTypes, ?array $writtenStates = null): bool
	{
		$changed = false;
		foreach ($expressionTypes as $exprString => $exprTypeHolder) {
			$expr = $exprTypeHolder->getExpr();
			if (!$expr instanceof CallLike) {
				continue;
			}

			$sideEffects = self::findCallSideEffects($scope, $reflectionProvider, $expr);
			if (
				$sideEffects === null
				|| !$sideEffects->has(SideEffects::READS_GLOBAL)->yes()
				|| ($writtenStates !== null && !self::readsAnyOf($sideEffects->getListedSubEffects(SideEffects::READS_GLOBAL), $writtenStates))
			) {
				continue;
			}

			unset($expressionTypes[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
			$changed = true;
		}

		return $changed;
	}

	/**
	 * The side effects of the function or method a call expression calls,
	 * null when it is not known or is an existence check.
	 */
	private static function findCallSideEffects(Scope $scope, ReflectionProvider $reflectionProvider, Expr $expr): ?SideEffects
	{
		if ($expr instanceof FuncCall) {
			if (
				$expr->isPartialFunctionApplication()
				|| !$expr->name instanceof Name
				|| !$reflectionProvider->hasFunction($expr->name, $scope)
			) {
				return null;
			}

			$function = $reflectionProvider->getFunction($expr->name, $scope);
			if (in_array(strtolower($function->getName()), self::EXISTENCE_CHECK_FUNCTION_NAMES, true)) {
				return null;
			}

			return $function->getSideEffects();
		}

		// on a variable only: the type of another receiver, such as a call, would be resolved again
		if ($expr instanceof MethodCall || $expr instanceof NullsafeMethodCall) {
			if ($expr->isPartialFunctionApplication() || !$expr->name instanceof Identifier || !$expr->var instanceof Variable) {
				return null;
			}

			return $scope->getMethodReflection($scope->getType($expr->var), $expr->name->toString())?->getSideEffects();
		}

		if ($expr instanceof StaticCall) {
			if ($expr->isPartialFunctionApplication() || !$expr->class instanceof Name || !$expr->name instanceof Identifier) {
				return null;
			}

			return $scope->getMethodReflection($scope->resolveTypeByName($expr->class), $expr->name->toString())?->getSideEffects();
		}

		return null;
	}

	/**
	 * @param list<string>|null $readStates
	 * @param list<string> $writtenStates
	 */
	private static function readsAnyOf(?array $readStates, array $writtenStates): bool
	{
		foreach ($readStates ?? [] as $readState) {
			foreach ($writtenStates as $writtenState) {
				if (
					$readState === $writtenState
					|| str_starts_with($readState, $writtenState . '.')
					|| str_starts_with($writtenState, $readState . '.')
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Forgets tracked superglobal variables and their offsets.
	 *
	 * The bare superglobal variables are looked up by exact key, keeping this O(1)
	 * in the common case where nothing is tracked. The unbounded set of superglobal
	 * offset keys ($_GET['x'], ...) is only scanned once a bare superglobal variable
	 * is known to be tracked, which is guaranteed whenever any of its offsets is
	 * tracked (narrowing an offset in specifyExpressionType() always also narrows the
	 * container variable).
	 *
	 * @param array<string, ExpressionTypeHolder> $expressionTypes
	 * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
	 * @return bool whether anything was removed
	 */
	public static function invalidateSuperglobals(array &$expressionTypes, array &$nativeExpressionTypes): bool
	{
		$hasTrackedSuperglobal = false;
		foreach (Scope::SUPERGLOBAL_VARIABLES as $superglobalName) {
			$variableString = '$' . $superglobalName;
			if (
				!array_key_exists($variableString, $expressionTypes)
				&& !array_key_exists($variableString, $nativeExpressionTypes)
			) {
				continue;
			}

			$hasTrackedSuperglobal = true;
			break;
		}

		if (!$hasTrackedSuperglobal) {
			return false;
		}

		$changed = false;
		foreach (array_keys($expressionTypes + $nativeExpressionTypes) as $exprString) {
			$isSuperglobal = false;
			foreach (Scope::SUPERGLOBAL_VARIABLES as $superglobalName) {
				$variableString = '$' . $superglobalName;
				if ($exprString === $variableString || str_starts_with($exprString, $variableString . '[')) {
					$isSuperglobal = true;
					break;
				}
			}

			if (!$isSuperglobal) {
				continue;
			}

			unset($expressionTypes[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
			$changed = true;
		}

		return $changed;
	}

	/**
	 * Removes negative existence-check narrowings (function_exists(), class_exists(), ...)
	 * from the given expression-type maps in place, returning whether anything was removed.
	 * With no filters given every negative existence check is removed; pass $functionNames
	 * and/or $declaredSymbolName to restrict removal to a specific check kind and symbol.
	 *
	 * @param array<string, ExpressionTypeHolder> $expressionTypes
	 * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
	 * @param list<'class_exists'|'interface_exists'|'trait_exists'|'enum_exists'|'function_exists'> $functionNames
	 * @return bool whether anything was removed
	 */
	public static function invalidateNegativeExistenceChecks(
		Scope $scope,
		array &$expressionTypes,
		array &$nativeExpressionTypes,
		array $functionNames = self::EXISTENCE_CHECK_FUNCTION_NAMES,
		?string $declaredSymbolName = null,
	): bool
	{
		$changed = false;
		foreach ($expressionTypes as $exprString => $exprTypeHolder) {
			$expr = $exprTypeHolder->getExpr();

			if (
				!$expr instanceof FuncCall
				|| $expr->isPartialFunctionApplication()
				|| !$expr->name instanceof Name
				|| !in_array($expr->name->toLowerString(), $functionNames, true)
				|| $exprTypeHolder->getType()->isTrue()->yes()
			) {
				continue;
			}

			// keep a check whose single constant-string argument names a symbol other than the declared one
			if ($declaredSymbolName !== null && isset($expr->getArgs()[0])) {
				$constantStrings = $scope->getType($expr->getArgs()[0]->value)->getConstantStrings();
				if (
					count($constantStrings) === 1
					&& ltrim(strtolower($constantStrings[0]->getValue()), '\\') !== ltrim(strtolower($declaredSymbolName), '\\')
				) {
					continue;
				}
			}

			unset($expressionTypes[$exprString]);
			unset($nativeExpressionTypes[$exprString]);
			$changed = true;
		}

		return $changed;
	}

}
