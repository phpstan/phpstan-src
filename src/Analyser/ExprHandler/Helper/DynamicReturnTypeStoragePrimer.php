<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Closure;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure as ClosureExpr;
use PHPStan\Analyser\ArgsResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * Re-exposes a call's already-processed arguments in the current storage frame
 * around a dynamic-return-type extension dispatch, so the extension's
 * Scope::getType($arg->value) reads the stored result instead of re-walking the
 * argument on demand. The call's own argument storage frame is no longer current
 * when the return type is asked lazily (from a rule, a parent expression, a later
 * statement), so those arguments would otherwise miss and be re-priced.
 */
#[AutowiredService]
final class DynamicReturnTypeStoragePrimer
{

	/**
	 * Push a transient storage carrying the argument results (current storage as
	 * fallback, so every non-argument getType is unchanged) and return the matching
	 * pop - always call it, in a finally. Closures/arrow functions are excluded:
	 * the bridge computes their type directly on the asking scope (getClosureType),
	 * which a processArgs-time stored result would shadow with a stale type.
	 *
	 * @param Arg[] $args
	 * @return Closure(): void
	 */
	public function pushPrimedStorage(MutatingScope $scope, array $args, ?ArgsResult $argsResult): Closure
	{
		$noop = static function (): void {
		};
		if ($argsResult === null) {
			return $noop;
		}

		$current = $scope->getCurrentExpressionResultStorage();
		$primed = $current !== null ? $current->duplicate() : new ExpressionResultStorage();
		$primedAny = false;
		foreach ($args as $arg) {
			if ($arg->value instanceof ClosureExpr || $arg->value instanceof ArrowFunction) {
				continue;
			}
			$argResult = $argsResult->getArgResult($arg->value);
			if ($argResult === null) {
				continue;
			}
			$primed->storeExpressionResult($arg->value, $argResult);
			$primedAny = true;
		}

		if (!$primedAny) {
			return $noop;
		}

		$scope->pushExpressionResultStorage($primed);

		return static function () use ($scope): void {
			$scope->popExpressionResultStorage();
		};
	}

}
