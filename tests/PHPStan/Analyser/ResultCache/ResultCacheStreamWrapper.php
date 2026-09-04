<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use AllowDynamicProperties;

/**
 * A result cache file that passes the is_file() check and then refuses to open, the way a file does
 * when something deletes it between the two. A real file cannot be made to do that without a race.
 *
 * PHP assigns the stream context to $context on every instance it creates, which is a dynamic
 * property nothing here reads.
 *
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
#[AllowDynamicProperties]
final class ResultCacheStreamWrapper
{

	public const SCHEME = 'phpstan-result-cache-test';

	public static bool $deleted = false;

	/**
	 * @return array<string, int>|false
	 */
	public function url_stat(string $path, int $flags)
	{
		if (self::$deleted) {
			return false;
		}

		return ['mode' => 0100644, 'size' => 8];
	}

	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
	{
		self::$deleted = true;

		return false;
	}

	public function unlink(string $path): bool
	{
		return true;
	}

}
