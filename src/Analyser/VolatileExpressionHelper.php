<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use function array_key_exists;
use function array_keys;
use function str_starts_with;

/**
 * Removes tracked narrowings of mutable global state from an expression-type map.
 *
 * Two kinds of expressions are volatile in this sense: argument-less function-call
 * expressions whose value reflects mutable global/output-buffer state rather than
 * just their arguments (ob_get_level(), openssl_error_string()), and superglobal
 * variables together with their offsets ($_GET, $_GET['x'], ...). Any call to code
 * PHPStan cannot inspect may change that state transitively, so these narrowings
 * must be forgotten afterwards.
 */
final class VolatileExpressionHelper
{

	private const VOLATILE_FUNCTION_NAMES = ['ob_get_level', 'openssl_error_string'];

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

}
