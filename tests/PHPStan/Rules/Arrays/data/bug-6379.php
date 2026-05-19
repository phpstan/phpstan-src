<?php

namespace Bug6379;

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
			return sprintf('-c%s', $params['cr'] ?? $params['c']);
		}

		return '';
	}
}
