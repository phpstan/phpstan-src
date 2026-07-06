<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use function array_key_exists;
use function array_keys;
use function count;
use function in_array;
use function ltrim;
use function str_starts_with;
use function strtolower;

final class VolatileExpressionHelper
{

	private const VOLATILE_FUNCTION_NAMES = ['ob_get_level', 'openssl_error_string'];

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
				|| $expr->isFirstClassCallable()
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
