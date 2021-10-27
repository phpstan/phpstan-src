<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\DI\Helpers;

/**
 * A speed optimized version of BaselineNeonErrorFormatter
 * which does not use regular expressions and therefore prevents the PCRE overhead.
 */
class BaselineNeonV2ErrorFormatter extends BaselineNeonErrorFormatter
{
	/**
	 * @return array{rawMessage: string, count: int, path: string}
	 */
	protected function formatError(string $message, int $count, string $file)
	{
		return [
			'rawMessage' => Helpers::escape($message),
			'count' => $count,
			'path' => Helpers::escape($file),
		];
	}

}
