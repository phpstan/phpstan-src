<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use RuntimeException;

/**
 * The collected data in the result cache file could not be read back. The reader has discarded
 * the file, so the next run analyses everything.
 */
final class UnreadableCollectedDataException extends RuntimeException
{

}
