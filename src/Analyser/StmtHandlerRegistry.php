<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\DependencyInjection\Container;
use function get_class;
use function spl_object_id;

/**
 * Resolves which StmtHandler handles a given Stmt, memoizing the result by
 * Stmt class so dispatch does not re-scan every tagged handler (a linear
 * supports() sweep) on each statement.
 *
 * supports() must therefore be class-deterministic: two Stmt instances of the
 * same class must resolve to the same handler. A handler that needs finer
 * dispatch has to add a discriminator to the cache key here, like the
 * CallLike cases in ExprHandlerRegistry.
 */
final class StmtHandlerRegistry
{

	/** @var array<int, array<class-string<Stmt>, StmtHandler<Stmt>|false>> */
	private static array $stmtHandlersByClass = [];

	/**
	 * @return StmtHandler<Stmt>|null
	 */
	public static function resolve(Stmt $stmt, Container $container): ?StmtHandler
	{
		$cacheKey = get_class($stmt);

		$containerId = spl_object_id($container);
		self::$stmtHandlersByClass[$containerId] ??= [];
		$cached = self::$stmtHandlersByClass[$containerId][$cacheKey] ?? null;
		if ($cached !== null) {
			return $cached === false ? null : $cached;
		}

		$matchedHandler = null;
		foreach ($container->getExtensionsCollection(StmtHandler::class)->getAll() as $stmtHandler) {
			if (!$stmtHandler->supports($stmt)) {
				continue;
			}

			$matchedHandler = $stmtHandler;
			break;
		}

		self::$stmtHandlersByClass[$containerId][$cacheKey] = $matchedHandler ?? false;

		return $matchedHandler;
	}

}
