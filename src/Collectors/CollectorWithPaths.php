<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;

/**
 * Implement this next to Collector when the collected data contains filesystem paths.
 *
 * The result cache stores paths relative to the install location so that it survives a change of
 * the project's absolute path prefix - a fresh CI checkout directory, a git worktree. Collected data
 * is opaque to the cache, so paths inside it can only be rewritten by the collector that produced
 * it. Without this hook they stay absolute and make the cache non-portable.
 *
 * The method is static because the cache only ever holds the collector's class name
 * (CollectedData::getCollectorType()), never an instance.
 *
 * @api
 * @template-covariant TNodeType of Node
 * @template TValue
 * @extends Collector<TNodeType, TValue>
 */
interface CollectorWithPaths extends Collector
{

	/**
	 * Returns the collected data with every filesystem path in it passed through $transformPath.
	 * Called once per collected value when the result cache is saved, and again with the inverse
	 * transformation when it is loaded, so it has to be symmetric.
	 *
	 * @param TValue $data
	 * @param callable(string): string $transformPath
	 * @return TValue
	 */
	public static function transformCollectedDataPaths($data, callable $transformPath);

}
