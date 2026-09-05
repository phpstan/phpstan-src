<?php declare(strict_types = 1);

namespace Bug6379Types;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param array{
	 *    cr?: string,
	 *    c?: string
	 * } $params
	 */
	public static function paramsToString(array $params): string
	{
		if (isset($params['cr']) === true || isset($params['c']) === true) {
			assertType('non-empty-array{cr?: string, c?: string}', $params);
			if (!isset($params['cr'])) {
				assertType('array{c: string}', $params);
			}

			return sprintf('-c%s', $params['cr'] ?? $params['c']);
		}

		return '';
	}

}
