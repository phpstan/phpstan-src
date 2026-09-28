<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use RuntimeException;

/**
 * The result cache file no longer holds what its index says - restore() discards such a cache.
 */
final class CachedExportedNodesUnreadableException extends RuntimeException
{

}
