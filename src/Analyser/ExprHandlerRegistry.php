<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use function get_class;
use function spl_object_id;

/**
 * Resolves which ExprHandler handles a given Expr, memoizing the result by
 * Expr class so dispatch does not re-scan every tagged handler (a linear
 * supports() sweep) on each call.
 */
#[AutowiredService]
final class ExprHandlerRegistry
{

	/** @var array<int, array<non-empty-string, ExprHandler<Expr>|false>> */
	private static array $exprHandlersByClass = [];

	/**
	 * @return ExprHandler<Expr>|null
	 */
	public static function resolve(Expr $expr, Container $container): ?ExprHandler
	{
		$cacheKey = get_class($expr);
		if ($expr instanceof Expr\CallLike) {
			$cacheKey .= '|' . $expr->isFirstClassCallable();
			if ($expr instanceof Expr\New_) {
				$cacheKey .= '|' . get_class($expr->class);
			}
		}

		$containerId = spl_object_id($container);
		self::$exprHandlersByClass[$containerId] ??= [];
		$cached = self::$exprHandlersByClass[$containerId][$cacheKey] ?? null;
		if ($cached !== null) {
			return $cached === false ? null : $cached;
		}

		$matchedHandler = null;
		foreach ($container->getExtensions(ExprHandler::class) as $exprHandler) {
			if (!$exprHandler->supports($expr)) {
				continue;
			}

			$matchedHandler = $exprHandler;
			break;
		}

		self::$exprHandlersByClass[$containerId][$cacheKey] = $matchedHandler ?? false;

		return $matchedHandler;
	}

}
