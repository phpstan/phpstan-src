<?php declare(strict_types = 1);

namespace PHPStan\Command;

use function getenv;
use function in_array;

final class Environment
{

	private const SENSITIVE_ENV_VARIABLES = [
		'GITHUB_TOKEN',
		'CI_JOB_TOKEN', // gitlab
		'PRIVATE-TOKEN', // gitlab
		'TIDEWAYS_APIKEY',
	];

	/**
	 * Prevents known sensitive env vars from being leaked, e.g. when container files committed in repositories
	 *
	 * @return array<string, string>
	 */
	public static function getCleanedArray(): array
	{
		$env = getenv();
		$cleanedArray = [];
		foreach ($env as $name => $value) {
			if (in_array($name, self::SENSITIVE_ENV_VARIABLES, true)) {
				continue;
			}
			$cleanedArray[$name] = $value;
		}
		return $cleanedArray;
	}

}
